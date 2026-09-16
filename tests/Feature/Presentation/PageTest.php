<?php

declare(strict_types=1);

namespace Tests\Feature\Presentation;

use App\Support\Page;
use Illuminate\Support\Facades\Blade;
use JobMetric\Domi\Facades\Domi;
use Tests\TestCase;

class PageTest extends TestCase
{
    public function test_domi_localization_is_page_scoped_and_translated(): void
    {
        Page::prepare('settings', 'fa');
        $this->assertSame('در حال بارگذاری…', Domi::localize()['ui']['loading']);
        Page::addLocalize('example', ['label' => '</script><script>alert(1)</script>']);
        $this->withoutVite();
        $html = Blade::render("@extends('common.base')");
        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $html);
        $this->assertStringContainsString('\\u003C', $html);
        Page::prepare('dashboard', 'en');
        $this->assertArrayNotHasKey('example', Domi::localize());
        $this->assertSame('Loading…', Domi::localize()['ui']['loading']);
    }

    public function test_package_layout_renders_registered_assets_and_footer_in_order(): void
    {
        $this->withoutVite();
        Page::prepare('assets');
        Page::addStyle('/test/page.css', 'print');
        Page::addScript('/test/top.js', 'top');
        Page::addScript('/test/bottom.js');
        Domi::setFooterContent('<aside id="package-footer">Footer</aside>');
        $html = Blade::render("@extends('common.base') @section('title', 'Asset test') @section('body')<main id=\"page-main\">Page</main>@endsection");
        $this->assertSame(1, substr_count(strtolower($html), '<!doctype html>'));
        $this->assertStringContainsString('page.css', $html);
        $this->assertStringContainsString('media="print"', $html);
        $this->assertStringContainsString('package-footer', $html);
        $this->assertLessThan(strpos($html, '</head>'), strpos($html, '/test/top.js'));
        $this->assertGreaterThan(strpos($html, 'window.localize'), strpos($html, '/test/bottom.js'));
        $this->assertStringContainsString('assets/vendor/domi/domi.js', $html);
    }
}
