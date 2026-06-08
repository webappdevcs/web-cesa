<?php

namespace Cesa\WhatsAppAuth\Tests\Feature;

use Cesa\WhatsAppAuth\Contracts\WhatsAppGateway;
use Cesa\WhatsAppAuth\Models\WhatsAppOtpCode;
use Cesa\WhatsAppAuth\Services\WhatsAppOtpManager;
use Cesa\WhatsAppAuth\Tests\WhatsAppAuthTestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WhatsAppOtpManagerTest extends WhatsAppAuthTestCase
{
    private function recordingGateway(): WhatsAppGateway
    {
        return new class implements WhatsAppGateway
        {
            /** @var array<int, array{phone: string, message: string}> */
            public array $sent = [];

            public function send(string $phone, string $message): void
            {
                $this->sent[] = ['phone' => $phone, 'message' => $message];
            }
        };
    }

    private function managerWithStubbedUser(WhatsAppGateway $gateway, ?Authenticatable $user): WhatsAppOtpManager
    {
        return new class($gateway, $user) extends WhatsAppOtpManager
        {
            public function __construct(WhatsAppGateway $gateway, private ?Authenticatable $stubUser)
            {
                parent::__construct($gateway);
            }

            public function resolveUserByPhone(string $phone): ?Authenticatable
            {
                return $this->stubUser;
            }
        };
    }

    private function findUser(int $id): Authenticatable
    {
        $model = config('auth.providers.users.model');

        return $model::query()->findOrFail($id);
    }

    public function test_request_generates_persisted_and_delivered_otp(): void
    {
        $userId = $this->createUser();
        $gateway = $this->recordingGateway();

        $manager = $this->managerWithStubbedUser($gateway, $this->findUser($userId));

        $manager->request('081234567890');

        $record = WhatsAppOtpCode::query()->firstOrFail();

        $this->assertSame('081234567890', $record->phone);
        $this->assertSame($userId, (int) $record->user_id);
        $this->assertSame(0, $record->attempts);
        $this->assertNull($record->consumed_at);
        $this->assertTrue($record->expires_at->isFuture());

        $this->assertCount(1, $gateway->sent);

        preg_match('/(\d{6})/', $gateway->sent[0]['message'], $matches);
        $this->assertTrue(Hash::check($matches[1], $record->code_hash));
    }

    public function test_request_throws_when_phone_is_not_registered(): void
    {
        $manager = new WhatsAppOtpManager($this->recordingGateway());

        $this->expectException(ValidationException::class);

        $manager->request('081234567890');
    }

    public function test_verify_returns_user_for_correct_code_and_consumes_it(): void
    {
        $userId = $this->createUser();

        $record = WhatsAppOtpCode::query()->create([
            'user_id'    => $userId,
            'phone'      => '081234567890',
            'code_hash'  => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $user = $this->managerWithStubbedUser($this->recordingGateway(), null)
            ->verify('081234567890', '123456');

        $this->assertSame($userId, (int) $user->getAuthIdentifier());
        $this->assertNotNull($record->fresh()->consumed_at);
    }

    public function test_verify_rejects_wrong_code_and_increments_attempts(): void
    {
        $userId = $this->createUser();

        $record = WhatsAppOtpCode::query()->create([
            'user_id'    => $userId,
            'phone'      => '081234567890',
            'code_hash'  => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $manager = new WhatsAppOtpManager($this->recordingGateway());

        try {
            $manager->verify('081234567890', '000000');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame(1, $record->fresh()->attempts);
        $this->assertNull($record->fresh()->consumed_at);
    }

    public function test_verify_rejects_expired_code(): void
    {
        $userId = $this->createUser();

        WhatsAppOtpCode::query()->create([
            'user_id'    => $userId,
            'phone'      => '081234567890',
            'code_hash'  => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->expectException(ValidationException::class);

        (new WhatsAppOtpManager($this->recordingGateway()))
            ->verify('081234567890', '123456');
    }

    public function test_verify_rejects_after_max_attempts(): void
    {
        config(['whatsapp-auth.otp.max_attempts' => 3]);

        $userId = $this->createUser();

        WhatsAppOtpCode::query()->create([
            'user_id'    => $userId,
            'phone'      => '081234567890',
            'code_hash'  => Hash::make('123456'),
            'attempts'   => 3,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->expectException(ValidationException::class);

        (new WhatsAppOtpManager($this->recordingGateway()))
            ->verify('081234567890', '123456');
    }

    public function test_request_invalidates_previous_unconsumed_codes(): void
    {
        $userId = $this->createUser();
        $user = $this->findUser($userId);

        $manager = $this->managerWithStubbedUser($this->recordingGateway(), $user);

        config(['whatsapp-auth.otp.resend_cooldown_seconds' => 0]);

        $manager->request('081234567890');
        $manager->request('081234567890');

        $this->assertSame(1, WhatsAppOtpCode::query()->whereNull('consumed_at')->count());
        $this->assertSame(2, WhatsAppOtpCode::query()->count());
    }
}
