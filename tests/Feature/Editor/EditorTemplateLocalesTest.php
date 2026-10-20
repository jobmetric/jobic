<?php

declare(strict_types=1);

namespace Tests\Feature\Editor;

use App\Support\EditorTemplateRegistry;
use Tests\TestCase;

class EditorTemplateLocalesTest extends TestCase
{
    public function test_patterns_include_english_content_independently_of_panel_language(): void
    {
        config(['installation.completed' => false, 'localization.bootstrap' => [
            'fa' => ['direction' => 'rtl'], 'en' => ['direction' => 'ltr'],
        ]]);
        app()->setLocale('fa');

        $patterns = app(EditorTemplateRegistry::class)->forType('post');

        $this->assertGreaterThanOrEqual(10, count($patterns));
        foreach ($patterns as $pattern) {
            $english = $pattern['localized']['en'];
            $persian = $pattern['localized']['fa'];
            $this->assertNotSame($english['document'], $persian['document']);
            $this->assertStringContainsString('This editable sample', $english['preview']);
            $this->assertDoesNotMatchRegularExpression('/[\x{0600}-\x{06FF}]/u', json_encode($english['document'], JSON_UNESCAPED_UNICODE));
        }
        $this->assertSame('fa', app()->getLocale());
    }
}
