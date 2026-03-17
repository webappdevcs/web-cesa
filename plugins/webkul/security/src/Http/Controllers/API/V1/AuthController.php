<?php

namespace Webkul\Security\Http\Controllers\API\V1;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\Subgroup;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Laravel\Sanctum\PersonalAccessToken;
use Webkul\Security\Http\Requests\LoginRequest;
use Webkul\Security\Models\User;

#[Group('Security API Management')]
#[Subgroup('Authentication', 'Handle user authentication')]
class AuthController extends Controller
{
    #[Endpoint('Login', 'Authenticate user and generate API token')]
    #[Unauthenticated]
    #[BodyParam('email', 'string', 'User email address', required: true, example: 'admin@example.com')]
    #[BodyParam('password', 'string', 'User password', required: true, example: 'password')]
    #[BodyParam('device_name', 'string', 'Optional mobile device name used for per-device session rotation.', required: false, example: 'iPhone 15 Pro')]
    #[Response(status: 200, description: 'Login successful', content: '{"message": "Login successful", "token": "1|abcd1234efgh5678ijkl...", "token_type": "Bearer", "user": {"id": 1, "name": "Admin User", "email": "admin@example.com", "is_active": true, "current_access_token": {"name": "iPhone 15 Pro"}}}')]
    #[Response(status: 422, description: 'Validation error', content: '{"message": "The given data was invalid.", "errors": {"email": ["The email field is required."], "password": ["The password field is required."]}}')]
    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::query()
            ->where('email', $request->string('email'))
            ->first();

        if (! $user || ! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive. Please contact admin.'],
            ]);
        }

        $deviceName = trim((string) $request->string('device_name'));

        if ($deviceName !== '') {
            $user->tokens()->where('name', $deviceName)->delete();
        } else {
            $deviceName = 'api-token';
        }

        $token = $user->createToken($deviceName);
        $user->loadMissing(['roles', 'defaultCompany', 'allowedCompanies']);

        return response()->json([
            'message'    => 'Login successful',
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user'       => $this->authenticatedUserPayload($user, $token->accessToken),
        ]);
    }

    #[Endpoint('Me', 'Get the authenticated admin API user context for mobile bootstrap')]
    #[Authenticated]
    #[Response(status: 200, description: 'Authenticated user retrieved successfully', content: '{"message": "Authenticated user retrieved successfully.", "data": {"id": 1, "name": "Admin User", "email": "admin@example.com", "permissions": [], "roles": [], "current_access_token": {"name": "iPhone 15 Pro"}}}')]
    #[Response(status: 401, description: 'Unauthenticated', content: '{"message": "Unauthenticated."}')]
    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->resolveUser($request);
        $user->loadMissing(['roles', 'defaultCompany', 'allowedCompanies']);

        return response()->json([
            'message' => 'Authenticated user retrieved successfully.',
            'data'    => $this->authenticatedUserPayload($user, $user->currentAccessToken()),
        ]);
    }

    #[Endpoint('Logout', 'Revoke current API token')]
    #[Authenticated]
    #[Response(status: 200, description: 'Logout successful', content: '{"message": "Logout successful"}')]
    #[Response(status: 401, description: 'Unauthenticated', content: '{"message": "Unauthenticated."}')]
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->resolveUser($request);
        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    #[Endpoint('Logout All', 'Revoke all API tokens for the authenticated user')]
    #[Authenticated]
    #[Response(status: 200, description: 'All sessions revoked successfully', content: '{"message": "Logged out from all devices successfully."}')]
    #[Response(status: 401, description: 'Unauthenticated', content: '{"message": "Unauthenticated."}')]
    public function logoutAll(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->resolveUser($request)->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices successfully.',
        ]);
    }

    protected function resolveUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! $user->is_active) {
            $accessToken = $user->currentAccessToken();

            if ($accessToken instanceof PersonalAccessToken) {
                $accessToken->delete();
            }

            throw new AuthenticationException('Unauthenticated.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function authenticatedUserPayload(User $user, mixed $accessToken = null): array
    {
        return [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'language'            => $user->language,
            'is_active'           => (bool) $user->is_active,
            'resource_permission' => $user->resource_permission?->value ?? $user->resource_permission,
            'avatar_url'          => $user->avatar_url,
            'roles'               => $user->roles
                ->map(fn ($role): array => [
                    'id'         => $role->id,
                    'name'       => $role->name,
                    'guard_name' => $role->guard_name,
                ])
                ->values()
                ->all(),
            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->values()
                ->all(),
            'default_company' => $user->defaultCompany
                ? [
                    'id'   => $user->defaultCompany->id,
                    'name' => $user->defaultCompany->name,
                ]
                : null,
            'allowed_companies' => $user->allowedCompanies
                ->map(fn ($company): array => [
                    'id'   => $company->id,
                    'name' => $company->name,
                ])
                ->values()
                ->all(),
            'current_access_token' => $accessToken instanceof PersonalAccessToken
                ? [
                    'name'         => $accessToken->name,
                    'last_used_at' => $accessToken->last_used_at,
                    'created_at'   => $accessToken->created_at,
                    'expires_at'   => $accessToken->expires_at,
                ]
                : null,
        ];
    }
}
