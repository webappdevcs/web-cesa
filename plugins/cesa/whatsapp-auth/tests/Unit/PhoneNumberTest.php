<?php

namespace Cesa\WhatsAppAuth\Tests\Unit;

use Cesa\WhatsAppAuth\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_it_extracts_national_number_from_various_formats(): void
    {
        $this->assertSame('81234567890', PhoneNumber::national('081234567890'));
        $this->assertSame('81234567890', PhoneNumber::national('+6281234567890'));
        $this->assertSame('81234567890', PhoneNumber::national('6281234567890'));
        $this->assertSame('81234567890', PhoneNumber::national('0812-3456-7890'));
    }

    public function test_it_builds_delivery_format_with_leading_zero(): void
    {
        $this->assertSame('081234567890', PhoneNumber::forDelivery('+6281234567890'));
        $this->assertSame('081234567890', PhoneNumber::forDelivery('81234567890'));
    }

    public function test_it_builds_unique_lookup_candidates(): void
    {
        $candidates = PhoneNumber::candidates('081234567890');

        $this->assertContains('81234567890', $candidates);
        $this->assertContains('081234567890', $candidates);
        $this->assertContains('6281234567890', $candidates);
        $this->assertSame($candidates, array_unique($candidates));
    }

    public function test_it_returns_empty_candidates_for_blank_input(): void
    {
        $this->assertSame([], PhoneNumber::candidates('   '));
        $this->assertSame('', PhoneNumber::forDelivery('abc'));
    }
}
