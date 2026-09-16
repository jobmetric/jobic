<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LegalPageTest extends TestCase
{
    public function test_legal_pages_use_session_language_and_configured_cookie_lifetimes(): void
    {
        config(['auth.remember_days' => 17, 'session.lifetime' => 43]);
        $this->withSession(['auth_locale' => 'en'])->get(route('legal.cookies'))
            ->assertOk()->assertSee('17 days')->assertSee('43 minutes')
            ->assertSee('Cookie policy')->assertSee('lang="en"', false);
        $this->withSession(['auth_locale' => 'fa'])->get(route('legal.privacy'))
            ->assertOk()->assertSee('حریم خصوصی')->assertSee('dir="rtl"', false)
            ->assertDontSee('Jobic CMS')->assertSee('حذف خودکار');
    }

    public function test_legal_language_endpoint_validates_and_persists_locale(): void
    {
        $this->postJson(route('legal.language'), ['locale' => 'en'])->assertOk()->assertSessionHas('auth_locale', 'en');
        $this->postJson(route('legal.language'), ['locale' => 'xx'])->assertUnprocessable();
    }
}
