<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_pages_share_localized_login_scene_and_controls(): void
    {
        foreach (['en','fa'] as $locale) {
            $this->withSession(['auth_locale'=>$locale]);
            foreach ([route('password.request'),route('password.reset',['token'=>'fixture-token','email'=>'person@example.test'])] as $url) {
                $response = $this->get($url)->assertOk();
                $response->assertSee('data-login-universe',false)->assertSee('data-auth-language',false)->assertSee('auth-login-refined',false)->assertSee('lang="'.$locale.'"',false);
                $response->assertSee(trans('auth.recovery.back', [], $locale));
            }
        }
        $response = $this->get(route('password.reset',['token'=>'fixture-token','email'=>'person@example.test']));
        $response->assertSee('name="token" value="fixture-token"',false)->assertSee('autocomplete="new-password"',false);
        preg_match('/<script type="application\/json" id="jobic-page-data">(.*?)<\/script>/s', $response->getContent(), $matches);
        $this->assertStringNotContainsString('fixture-token', $matches[1]);
        $this->assertStringNotContainsString('person@example.test', $matches[1]);
    }
}
