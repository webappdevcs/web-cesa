<?php

namespace Cesa\FormTransfer\Tests\Unit\Services;

use Cesa\FormTransfer\Services\RateLimitGuard;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitGuardTest extends TestCase
{
    protected RateLimitGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = new RateLimitGuard;
        RateLimiter::clear('test-key');
    }

    public function test_attempt_allows_within_limit(): void
    {
        $result = $this->guard->attempt('test-key', 5, 60);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(4, $result['remaining']);
        $this->assertEquals(0, $result['availableIn']);
    }

    public function test_attempt_blocks_after_exceeding_limit(): void
    {
        // Make 5 attempts
        for ($i = 0; $i < 5; $i++) {
            $this->guard->attempt('test-key', 5, 60);
        }

        // 6th attempt should be blocked
        $result = $this->guard->attempt('test-key', 5, 60);

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertGreaterThan(0, $result['availableIn']);
    }

    public function test_remaining_returns_correct_count(): void
    {
        $this->guard->attempt('test-key', 5, 60);
        $this->guard->attempt('test-key', 5, 60);

        $remaining = $this->guard->remaining('test-key', 5);

        $this->assertEquals(3, $remaining);
    }

    public function test_clear_resets_rate_limit(): void
    {
        // Exceed limit
        for ($i = 0; $i < 6; $i++) {
            $this->guard->attempt('test-key', 5, 60);
        }

        $this->assertTrue($this->guard->tooManyAttempts('test-key', 5));

        // Clear and verify
        $this->guard->clear('test-key');

        $this->assertFalse($this->guard->tooManyAttempts('test-key', 5));
    }

    public function test_too_many_attempts_detects_exceeded_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->guard->attempt('test-key', 5, 60);
        }

        $this->assertTrue($this->guard->tooManyAttempts('test-key', 5));
    }

    public function test_build_form_submission_key_formats_correctly(): void
    {
        $key = $this->guard->buildFormSubmissionKey('transfer-001', 'user@example.com');

        $this->assertEquals('form-transfer:transfer-001:submit:user@example.com', $key);
    }

    public function test_build_approval_key_formats_correctly(): void
    {
        $key = $this->guard->buildApprovalKey('task-123', '192.168.1.1');

        $this->assertEquals('form-transfer:approval:task-123:192.168.1.1', $key);
    }

    public function test_available_in_returns_wait_time(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->guard->attempt('test-key', 5, 60);
        }

        $availableIn = $this->guard->availableIn('test-key');

        $this->assertGreaterThan(0, $availableIn);
        $this->assertLessThanOrEqual(60, $availableIn);
    }
}
