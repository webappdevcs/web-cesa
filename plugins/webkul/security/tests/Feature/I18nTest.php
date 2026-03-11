<?php

namespace Webkul\Security\Tests\Feature;

use Webkul\Security\Filament\Resources\UserResource;
use Webkul\Security\Tests\SecurityTestCase;

class I18nTest extends SecurityTestCase
{
    public function test_user_resource_exposes_localized_language_options_for_supported_locales(): void
    {
        $expectations = [
            'en' => [
                'en' => 'English',
                'id' => 'Indonesian',
            ],
            'id' => [
                'en' => 'Inggris',
                'id' => 'Bahasa Indonesia',
            ],
        ];

        foreach ($expectations as $locale => $labels) {
            app()->setLocale($locale);

            $this->assertSame($labels, UserResource::getLanguageOptions());
            $this->assertSame($labels['en'], UserResource::getLanguageLabel('en'));
            $this->assertSame($labels['id'], UserResource::getLanguageLabel('id'));
        }
    }
}
