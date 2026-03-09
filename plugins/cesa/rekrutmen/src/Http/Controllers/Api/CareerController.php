<?php

namespace Cesa\Rekrutmen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CareerController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = JobPosting::query()
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('closing_date')
                    ->orWhere('closing_date', '>=', now());
            })
            ->latest()
            ->get([
                'title',
                'slug',
                'location',
                'closing_date',
            ]);

        return response()->json([
            'success' => true,
            'message' => __('rekrutmen::app.api.messages.job_listed'),
            'data'    => $jobs,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $job = JobPosting::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first([
                'title',
                'slug',
                'description',
                'requirements',
                'location',
                'closing_date',
            ]);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => __('rekrutmen::app.api.messages.job_not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('rekrutmen::app.api.messages.job_detail_retrieved'),
            'data'    => [
                ...$job->toArray(),
                'application_form' => $this->resolveApplicationFormFields($job->slug),
            ],
        ]);
    }

    public function apply(Request $request, string $slug): JsonResponse
    {
        $job = JobPosting::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('closing_date')
                    ->orWhere('closing_date', '>=', now());
            })
            ->first();

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => __('rekrutmen::app.api.messages.job_not_open'),
            ], 404);
        }

        $validated = Validator::make($request->all(), [
            'full_name'             => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255'],
            'phone'                 => ['required', 'string', 'max:30'],
            'cover_letter'          => ['nullable', 'string'],
            'portfolio_url'         => ['nullable', 'url', 'max:255'],
            'resume'                => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'additional_answers'    => ['nullable', 'array'],
            'additional_answers.*'  => ['nullable'],
        ], trans('rekrutmen::app.api.validation.messages'), trans('rekrutmen::app.api.validation.attributes'))->validate();

        $additionalAnswers = $request->input('additional_answers', []);
        $dynamicRules = $this->buildAdditionalAnswersValidationRules($job->slug);

        if ($dynamicRules !== []) {
            $validator = Validator::make(
                ['additional_answers' => $additionalAnswers],
                $dynamicRules,
                ['required' => __('rekrutmen::app.api.validation.messages.required')],
                $this->buildAdditionalAnswersValidationAttributes($job->slug),
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        $resumePath = null;

        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store(
                JobApplication::RESUME_DIRECTORY,
                JobApplication::resumeDisk()
            );
        }

        $coverLetter = $validated['cover_letter'] ?? null;

        if ($additionalAnswers !== []) {
            $answers = json_encode($additionalAnswers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $coverLetter = trim(implode("\n\n", array_filter([
                $coverLetter,
                __('rekrutmen::app.api.application.additional_answers_prefix').' '.$answers,
            ])));
        }

        $firstStageId = $job->rekrutmen_pipeline_id
            ? $job->rekrutmenPipeline?->stages()->orderBy('order_column')->value('id')
            : null;

        $application = JobApplication::query()->create([
            'job_posting_id'   => $job->getKey(),
            'current_stage_id' => $firstStageId,
            'full_name'        => $validated['full_name'],
            'email'            => $validated['email'],
            'phone'            => $validated['phone'],
            'resume_path'      => $resumePath,
            'cover_letter'     => $coverLetter,
            'portfolio_url'    => $validated['portfolio_url'] ?? null,
            'status'           => JobApplicationStatus::IN_PROGRESS,
        ]);

        JobApplicationHistory::query()->create([
            'job_application_id' => $application->getKey(),
            'from_stage_id'      => null,
            'to_stage_id'        => $firstStageId,
            'status'             => JobApplicationStatus::IN_PROGRESS,
            'notes'              => __('rekrutmen::app.api.application.submitted_via_public_api'),
            'performed_by'       => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('rekrutmen::app.api.messages.application_submitted'),
            'data'    => [
                'job_slug'        => $job->slug,
                'applicant_name'  => $application->full_name,
                'applicant_email' => $application->email,
                'status'          => $application->status?->value ?? JobApplicationStatus::IN_PROGRESS->value,
            ],
        ], 201);
    }

    /**
     * @return array<int, array{name: string, label: string, type: string, required: bool}>
     */
    private function resolveApplicationFormFields(string $slug): array
    {
        $defaultFields = config('rekrutmen.application_form.default_fields', []);
        $slugFields = config("rekrutmen.application_form.by_slug.{$slug}", []);

        return array_map(function (array $field): array {
            $label = $field['label'] ?? null;

            if (is_string($label) && $label !== '') {
                $field['label'] = __($label);
            }

            return $field;
        }, [...$defaultFields, ...$slugFields]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildAdditionalAnswersValidationRules(string $slug): array
    {
        $slugFields = config("rekrutmen.application_form.by_slug.{$slug}", []);

        $rules = [];

        foreach ($slugFields as $field) {
            $fieldName = $field['name'] ?? null;
            $fieldType = $field['type'] ?? 'text';
            $required = (bool) ($field['required'] ?? false);

            if (! is_string($fieldName) || blank($fieldName)) {
                continue;
            }

            $fieldRules = [$required ? 'required' : 'nullable'];

            $fieldRules[] = match ($fieldType) {
                'email'   => 'email',
                'url'     => 'url',
                'number'  => 'numeric',
                'boolean' => 'boolean',
                'date'    => 'date',
                default   => 'string',
            };

            $rules["additional_answers.{$fieldName}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function buildAdditionalAnswersValidationAttributes(string $slug): array
    {
        $slugFields = config("rekrutmen.application_form.by_slug.{$slug}", []);

        $attributes = [];

        foreach ($slugFields as $field) {
            $fieldName = $field['name'] ?? null;
            $fieldLabel = $field['label'] ?? null;

            if (! is_string($fieldName) || blank($fieldName) || ! is_string($fieldLabel) || blank($fieldLabel)) {
                continue;
            }

            $attributes["additional_answers.{$fieldName}"] = __($fieldLabel);
        }

        return $attributes;
    }
}
