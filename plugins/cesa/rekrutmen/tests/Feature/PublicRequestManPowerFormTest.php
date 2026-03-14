<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Tests\RekrutmenTestCase;

class PublicRequestManPowerFormTest extends RekrutmenTestCase
{
    public function test_public_request_man_power_form_uses_plain_textareas_for_long_text_fields(): void
    {
        $response = $this->get('/man-power');

        $response
            ->assertOk()
            ->assertSee('Kualifikasi yang Dibutuhkan', false)
            ->assertSee('Deskripsi Pekerjaan', false)
            ->assertDontSee('fi-fo-rich-editor', false);
    }
}
