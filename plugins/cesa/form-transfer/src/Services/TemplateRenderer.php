<?php

namespace Cesa\FormTransfer\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Service for rendering email and WhatsApp notification templates.
 *
 * Handles template variable replacement, HTML generation, and table building.
 */
class TemplateRenderer
{
    /**
     * Render a template with variable replacement.
     *
     * @param  array<string, mixed>  $variables  Variables for placeholder replacement
     */
    public function render(?string $template, array $variables): ?string
    {
        if ($template === null || trim($template) === '') {
            return null;
        }

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function (array $matches) use ($variables): string {
                $key = $matches[1];

                if (! array_key_exists($key, $variables)) {
                    return $matches[0]; // Keep original if not found
                }

                $value = $variables[$key];

                return $this->formatVariableValue($value);
            },
            $template
        );
    }

    /**
     * Build an HTML action button for email templates.
     */
    public function buildActionButton(?string $url, string $text): string
    {
        if (! $url || trim($text) === '') {
            return '';
        }

        return sprintf(
            '<a href="%s" target="_blank" style="display:inline-block; padding:12px 24px; background-color:#2563eb; color:#ffffff; border-radius:6px; text-decoration:none; font-weight:500;">%s</a>',
            e($url),
            e($text)
        );
    }

    /**
     * Build a summary table from key-value pairs.
     *
     * @param  array<string, mixed>  $data  Key-value pairs for table rows
     */
    public function buildSummaryTable(array $data): string
    {
        $body = collect($data)
            ->filter(fn ($value, $key) => $key !== null && $value !== null)
            ->map(function ($value, $label): string {
                return sprintf(
                    '<tr><td style="padding:8px 12px; border:1px solid #d1d5db; background-color:#f9fafb; font-weight:600;">%s</td><td style="padding:8px 12px; border:1px solid #d1d5db;">%s</td></tr>',
                    e((string) $label),
                    $this->normalizeTableValue($value)
                );
            })
            ->implode('');

        if ($body === '') {
            return '';
        }

        return sprintf(
            '<table style="width:100%%; border-collapse:collapse; margin:16px 0; border-radius:6px; overflow:hidden;"><tbody>%s</tbody></table>',
            $body
        );
    }

    /**
     * Build an approvals status table.
     *
     * @param  array<int, array{name: ?string, title: ?string, status: ?string, notes: ?string, noted_at: ?string}>  $approvals  Approval steps
     */
    public function buildApprovalsTable(array $approvals): string
    {
        if (empty($approvals)) {
            return '';
        }

        $headers = [
            __('form-transfer::app.fields.approver_name'),
            __('form-transfer::app.fields.approver_title'),
            __('form-transfer::app.fields.approver_status'),
            __('form-transfer::app.fields.approver_notes'),
            __('form-transfer::app.fields.approver_noted_at'),
        ];

        $rows = collect($approvals)->map(function (array $approval): array {
            $status = $approval['status'] ?? '';

            return [
                $approval['name'] ?? '-',
                $approval['title'] ?? '-',
                $status !== '' ? ucfirst((string) $status) : '-',
                $approval['notes'] ?? ($approval['note'] ?? '-'),
                $this->formatTimestamp($approval['noted_at'] ?? null),
            ];
        })->all();

        return $this->buildTable($headers, $rows);
    }

    /**
     * Build a plain text approver list.
     *
     * @param  array<int, array{name: ?string, title: ?string, status: ?string}>  $approvals  Approval steps
     */
    public function buildApproverList(array $approvals): string
    {
        return collect($approvals)
            ->map(function (array $approval): string {
                $status = $approval['status'] ?? '-';
                $name = $approval['name'] ?? '-';
                $title = $approval['title'] ?? '';

                return sprintf('- %s%s (%s)', $name, $title ? " - {$title}" : '', ucfirst((string) $status));
            })
            ->implode("\n");
    }

    /**
     * Detect if content is HTML.
     */
    public function looksLikeHtml(string $content): bool
    {
        $trimmed = trim($content);

        return Str::startsWith($trimmed, '<')
            && Str::contains($trimmed, '</');
    }

    /**
     * Split template into lines for plain text rendering.
     *
     * @return array<int, string>
     */
    public function splitTemplateLines(string $template): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", $template)),
            fn ($line) => $line !== ''
        ));
    }

    /**
     * Build a generic HTML table.
     *
     * @param  array<int, string>  $headers  Table headers
     * @param  array<int, array<int, mixed>>  $rows  Table rows
     */
    protected function buildTable(array $headers, array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $headerHtml = collect($headers)
            ->map(fn ($header): string => sprintf(
                '<th style="padding:10px 12px; background-color:#2563eb; color:#ffffff; text-align:left; border:1px solid #1d4ed8;">%s</th>',
                e((string) $header)
            ))
            ->implode('');

        $bodyHtml = collect($rows)
            ->map(function (array $row): string {
                $cells = collect($row)
                    ->map(fn ($cell): string => sprintf(
                        '<td style="padding:8px 12px; border:1px solid #d1d5db; vertical-align:top;">%s</td>',
                        $this->normalizeTableValue($cell)
                    ))
                    ->implode('');

                return sprintf('<tr>%s</tr>', $cells);
            })
            ->implode('');

        return sprintf(
            '<table style="width:100%%; border-collapse:collapse; margin:16px 0; border-radius:6px; overflow:hidden;"><thead><tr>%s</tr></thead><tbody>%s</tbody></table>',
            $headerHtml,
            $bodyHtml
        );
    }

    /**
     * Format variable value for template output.
     */
    protected function formatVariableValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof HtmlString) {
            return $value->toHtml();
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => (string) $item, $value));
        }

        return (string) $value;
    }

    /**
     * Normalize table cell value for HTML rendering.
     */
    protected function normalizeTableValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if ($value instanceof HtmlString) {
            return $value->toHtml();
        }

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (is_bool($value)) {
            $value = $value ? __('Yes') : __('No');
        }

        if (is_array($value)) {
            $value = implode(', ', array_map(fn ($item) => (string) $item, $value));
        }

        $stringValue = (string) $value;

        if (trim($stringValue) === '') {
            return '—';
        }

        return nl2br(e($stringValue));
    }

    /**
     * Format timestamp for display.
     */
    protected function formatTimestamp(?string $timestamp): string
    {
        if (! $timestamp) {
            return '—';
        }

        try {
            return Carbon::parse($timestamp)
                ->setTimezone(config('app.timezone', 'UTC'))
                ->format('d M Y H:i');
        } catch (\Exception $e) {
            return $timestamp;
        }
    }
}
