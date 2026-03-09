<?php

namespace Cesa\FormTransfer\Tests\Unit\Services;

use Cesa\FormTransfer\Services\RecaptchaValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaValidatorTest extends TestCase
{
    protected RecaptchaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new RecaptchaValidator;
    }

    public function test_is_enabled_returns_config_value(): void
    {
        Config::set('form-transfer.security.recaptcha.enabled', true);
        $this->assertTrue($this->validator->isEnabled());

        Config::set('form-transfer.security.recaptcha.enabled', false);
        $this->assertFalse($this->validator->isEnabled());
    }

    public function test_verify_returns_success_when_disabled(): void
    {
        Config::set('form-transfer.security.recaptcha.enabled', false);

        $result = $this->validator->verify('test-token', 'submit', '127.0.0.1');

        $this->assertTrue($result['success']);
        $this->assertEquals(1.0, $result['score']);
    }

    public function test_verify_returns_error_when_secret_key_not_configured(): void
    {
        Config::set('form-transfer.security.recaptcha.enabled', true);
        Config::set('form-transfer.security.recaptcha.secret_key', null);

        $result = $this->validator->verify('test-token', 'submit', '127.0.0.1');

        $this->assertFalse($result['success']);
        $this->assertEquals(0.0, $result['score']);
        $this->assertContains('reCAPTCHA not configured', $result['errors']);
    }

    public function test_verify_sends_correct_request_to_google_api(): void
    {
        Config::set('form-transfer.security.recaptcha.enabled', true);
        Config::set('form-transfer.security.recaptcha.secret_key', 'test-secret');
        Config::set('form-transfer.security.recaptcha.score_threshold', 0.5);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score'   => 0.9,
                'action'  => 'submit',
            ], 200),
        ]);

        $result = $this->validator->verify('test-token', 'submit', '127.0.0.1');

        $this->assertTrue($result['success']);
        $this->assertEquals(0.9, $result['score']);
        $this->assertEquals('submit', $result['action']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
                && $request['secret'] === 'test-secret'
                && $request['response'] === 'test-token'
                && $request['remoteip'] === '127.0.0.1';
        });
    }

    public function test_verify_rejects_action_mismatch(): void
    {
        Config::set('form-transfer.security.recaptcha.enabled', true);
        Config::set('form-transfer.security.recaptcha.secret_key', 'test-secret');

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score'   => 0.9,
                'action'  => 'wrong-action',
            ], 200),
        ]);

        $result = $this->validator->verify('test-token', 'submit', '127.0.0.1');

        $this->assertFalse($result['success']);
        $this->assertContains('Action mismatch', $result['errors']);
    }

    public function test_verify_rejects_low_score(): void
    {
        Config::set('form-transfer.security.recaptcha.enabled', true);
        Config::set('form-transfer.security.recaptcha.secret_key', 'test-secret');
        Config::set('form-transfer.security.recaptcha.score_threshold', 0.5);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score'   => 0.3,
                'action'  => 'submit',
            ], 200),
        ]);

        $result = $this->validator->verify('test-token', 'submit', '127.0.0.1');

        $this->assertFalse($result['success']);
        $this->assertEquals(0.3, $result['score']);
        $this->assertContains('Score below threshold', $result['errors']);
    }

    public function test_get_config_returns_configuration(): void
    {
        Config::set('form-transfer.security.recaptcha.enabled', true);
        Config::set('form-transfer.security.recaptcha.site_key', 'test-site-key');
        Config::set('form-transfer.security.recaptcha.score_threshold', 0.7);

        $config = $this->validator->getConfig();

        $this->assertTrue($config['enabled']);
        $this->assertEquals('test-site-key', $config['site_key']);
        $this->assertEquals(0.7, $config['min_score']);
    }
}
