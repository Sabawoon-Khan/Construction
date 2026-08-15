<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_cookie_can_be_set_for_supported_locales(): void
    {
        foreach (['en', 'fa'] as $locale) {
            $response = $this->from('/')->post(route('locale.update', $locale));

            $response->assertRedirect('/');
            $response->assertCookie('locale', $locale, false);
        }
    }

    public function test_unsupported_locale_returns_not_found(): void
    {
        $this->from('/')->post(route('locale.update', 'xx'))->assertNotFound();
    }

    public function test_home_shares_only_english_and_dari(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('locales')
            ->where('locales', fn ($locales) => collect($locales)->pluck('code')->sort()->values()->all() === ['en', 'fa'])
        );
    }
}
