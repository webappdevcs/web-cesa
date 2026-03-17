<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Webkul\Security\Models\User;

require_once __DIR__.'/../../../../../support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensureERPInstalled();

    if (! Route::has('admin.api.v1.login')) {
        require base_path('plugins/webkul/security/routes/api.php');
    }
});

function authRoute(string $name): string
{
    return route("admin.api.v1.{$name}");
}

function createAuthUser(array $attributes = []): User
{
    return User::withoutEvents(fn (): User => User::factory()->create($attributes));
}

function testEmail(string $prefix = 'mobile'): string
{
    return sprintf('%s-%s@example.com', $prefix, str()->uuid()->toString());
}

function loginPayload(array $overrides = []): array
{
    return array_replace([
        'email'       => 'mobile@example.com',
        'password'    => 'secret-password',
        'device_name' => 'iPhone 15 Pro',
    ], $overrides);
}

it('authenticates an active user and returns mobile bootstrap data', function () {
    $email = testEmail();
    $password = 'secret-password';
    $user = createAuthUser([
        'email'      => $email,
        'password'   => Hash::make($password),
        'is_active'  => true,
        'language'   => 'id',
    ]);

    $response = $this->postJson(authRoute('login'), loginPayload([
        'email'    => $email,
        'password' => $password,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Login successful')
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.is_active', true)
        ->assertJsonPath('user.language', 'id')
        ->assertJsonPath('user.current_access_token.name', 'iPhone 15 Pro');

    expect($response->json('token'))->toBeString()->not->toBe('');
});

it('rejects inactive users during login', function () {
    $email = testEmail();

    createAuthUser([
        'email'     => $email,
        'password'  => Hash::make('secret-password'),
        'is_active' => false,
    ]);

    $this->postJson(authRoute('login'), loginPayload([
        'email' => $email,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonPath('errors.email.0', 'Your account is inactive. Please contact admin.');
});

it('throttles repeated failed login attempts', function () {
    $email = testEmail();

    createAuthUser([
        'email'    => $email,
        'password' => Hash::make('secret-password'),
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson(authRoute('login'), loginPayload([
            'email'    => $email,
            'password' => 'wrong-password',
        ]))->assertUnprocessable();
    }

    $this->postJson(authRoute('login'), loginPayload([
        'email'    => $email,
        'password' => 'wrong-password',
    ]))->assertStatus(429);
});

it('rotates an existing device token when logging in from the same device name', function () {
    $email = testEmail();
    $password = 'secret-password';
    $user = createAuthUser([
        'email'    => $email,
        'password' => Hash::make($password),
    ]);

    $oldToken = $user->createToken('iPhone 15 Pro')->accessToken;

    $this->postJson(authRoute('login'), loginPayload([
        'email'    => $email,
        'password' => $password,
    ]))->assertOk();

    expect($oldToken->fresh())->toBeNull();
    expect($user->tokens()->where('name', 'iPhone 15 Pro')->count())->toBe(1);
});

it('requires authentication to fetch the current mobile auth context', function () {
    $this->getJson(authRoute('me'))
        ->assertUnauthorized();
});

it('returns the current authenticated user context', function () {
    $email = testEmail();
    $user = createAuthUser([
        'email'    => $email,
        'password' => Hash::make('secret-password'),
    ]);
    $token = $user->createToken('Android Phone');

    $this->withToken($token->plainTextToken)
        ->getJson(authRoute('me'))
        ->assertOk()
        ->assertJsonPath('message', 'Authenticated user retrieved successfully.')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.current_access_token.name', 'Android Phone');
});

it('revokes the current access token on logout', function () {
    $user = createAuthUser();
    $token = $user->createToken('Pixel 9');

    $this->withToken($token->plainTextToken)
        ->postJson(authRoute('logout'))
        ->assertOk()
        ->assertJsonPath('message', 'Logout successful');

    expect($token->accessToken->fresh())->toBeNull();
});

it('revokes all access tokens on logout all', function () {
    $user = createAuthUser();
    $currentToken = $user->createToken('Current Device');
    $otherToken = $user->createToken('Other Device');

    $this->withToken($currentToken->plainTextToken)
        ->postJson(authRoute('logout-all'))
        ->assertOk()
        ->assertJsonPath('message', 'Logged out from all devices successfully.');

    expect($currentToken->accessToken->fresh())->toBeNull();
    expect($otherToken->accessToken->fresh())->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});

it('revokes existing tokens when a user is deactivated', function () {
    $user = createAuthUser();
    $token = $user->createToken('Current Device');

    $user->update([
        'is_active' => false,
    ]);

    expect($token->accessToken->fresh())->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});

it('revokes existing tokens when a user password changes', function () {
    $user = createAuthUser();
    $token = $user->createToken('Current Device');

    $user->forceFill([
        'password' => 'brand-new-secret',
    ])->save();

    expect($token->accessToken->fresh())->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});
