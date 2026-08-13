<?php

namespace Tests\Unit;

use App\Support\LocalizedDigits;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalizedDigitsTest extends TestCase
{
    #[Test]
    public function it_formats_farsi_and_arabic_digits(): void
    {
        $this->assertSame('۴۲', LocalizedDigits::format(42, 'fa'));
        $this->assertSame('٤٢', LocalizedDigits::format(42, 'ar'));
        $this->assertSame('42', LocalizedDigits::format(42, 'en'));
    }
}
