<?php

declare(strict_types=1);

namespace Tests\Feature\Presentation;

use App\Http\Middleware\Installation\Install;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_error_responses_are_localized_and_keep_their_status_without_database_access(): void
    {
        $this->withoutMiddleware(Install::class);
        config(['app.debug' => false]);
        DB::listen(fn () => $this->fail('Error pages must not query the database.'));

        foreach (['en' => 'ltr', 'fa' => 'rtl'] as $locale => $direction) {
            app()->setLocale($locale);
            foreach ([400, 401, 403, 404, 405, 408, 409, 410, 413, 419, 422, 429, 500, 502, 503, 504, 418, 507] as $code) {
                Route::get('/_error-test/'.$code, function () use ($code) {
                    if ($code === 500) {
                        throw new \RuntimeException('PRIVATE-EXCEPTION-DETAIL');
                    }
                    if ($code === 419) {
                        throw new \Illuminate\Session\TokenMismatchException('PRIVATE-EXCEPTION-DETAIL');
                    }
                    abort($code, 'PRIVATE-EXCEPTION-DETAIL');
                });
                $response = $this->get('/_error-test/'.$code);
                $response->assertStatus($code)
                    ->assertSee('dir="'.$direction.'"', false)
                    ->assertSee(trans('errors.'.(in_array($code, [418, 507], true) ? ($code >= 500 ? '5xx' : '4xx') : $code).'.title'))
                    ->assertSee('css/errors.css', false)
                    ->assertDontSee('PRIVATE-EXCEPTION-DETAIL')
                    ->assertDontSee('Take me Back');
                $this->assertSame(1, substr_count($response->getContent(), '<!DOCTYPE html>'));
            }
        }
    }

    public function test_json_errors_remain_json(): void
    {
        $this->withoutMiddleware(Install::class);
        config(['app.debug' => false]);
        foreach ([400, 401, 403, 404, 405, 408, 409, 410, 413, 419, 422, 429, 500, 502, 503, 504, 418, 507] as $code) {
            Route::get('/_error-json/'.$code, fn () => abort($code));
            $this->getJson('/_error-json/'.$code)->assertStatus($code)
                ->assertHeader('content-type', 'application/json');
        }
    }
    public function test_error_headers_and_recovery_links_are_preserved(): void
    {
        $this->withoutMiddleware(Install::class);
        config(['app.debug' => false]);
        Route::get('/_error-limited', fn () => abort(429, '', ['Retry-After' => '120']));
        Route::get('/_error-unavailable', fn () => abort(503, '', ['Retry-After' => '300']));
        Route::get('/_error-method', fn () => abort(405, '', ['Allow' => 'GET']));
        Route::get('/_error-unauthorized', fn () => abort(401, '', ['WWW-Authenticate' => 'Basic']));
        $this->get('/_error-limited')->assertStatus(429)->assertHeader('Retry-After', '120');
        $this->get('/_error-unavailable')->assertStatus(503)->assertHeader('Retry-After', '300');
        $this->get('/_error-method')->assertStatus(405)->assertHeader('Allow', 'GET');
        $this->get('/_error-unauthorized')->assertStatus(401)->assertHeader('WWW-Authenticate', 'Basic')
            ->assertSee('href="'.url('/login').'"', false)->assertSee(trans('errors.sign_in'));
    }

    public function test_authentication_and_validation_keep_their_existing_responses(): void
    {
        $this->withoutMiddleware(Install::class);
        Route::get('/_error-protected', fn () => 'private')->middleware('auth');
        $this->get('/_error-protected')->assertRedirect(route('login'));
        $this->getJson('/_error-protected')->assertUnauthorized()->assertJsonStructure(['message']);

        Route::post('/_error-validation', function () {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Required']);
        });
        $this->from('/')->post('/_error-validation')->assertRedirect('/');
        $this->postJson('/_error-validation')->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_persian_validation_preserves_nested_field_paths_and_translates_messages(): void
    {
        $this->withoutMiddleware(Install::class);
        Route::post('/_localized-validation', function (\Illuminate\Http\Request $request) {
            app()->setLocale('fa');
            return $request->validate([
                'entries.*.title' => ['required'],
                'entries.*.unlisted_field' => ['required'],
                'entries.*.custom_code' => ['required'],
            ], attributes: ['entries.*.custom_code' => 'کد اختصاصی']);
        });

        $response = $this->postJson('/_localized-validation', ['entries' => [[]]])->assertUnprocessable();
        $this->assertSame([
            'entries.0.title' => ['وارد کردن عنوان الزامی است.'],
            'entries.0.unlisted_field' => ['وارد کردن این فیلد الزامی است.'],
            'entries.0.custom_code' => ['وارد کردن کد اختصاصی الزامی است.'],
        ], $response->json('errors'));
        $this->assertStringContainsString('(و 2 خطای دیگر)', $response->json('message'));
    }
}
