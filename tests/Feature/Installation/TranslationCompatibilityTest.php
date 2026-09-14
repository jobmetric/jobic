<?php

declare(strict_types=1);

namespace Tests\Feature\Installation;

use Illuminate\Database\Eloquent\Collection;
use ReflectionFunction;
use Tests\TestCase;

class TranslationCompatibilityTest extends TestCase
{
    public function test_translation_helpers_have_explicit_nullable_locale_and_keep_behavior(): void
    {
        foreach (['translationResourceData', 'translationDataSelect'] as $function) {
            $parameters = (new ReflectionFunction($function))->getParameters();
            $locale = end($parameters);
            $this->assertSame('?string', (string) $locale->getType());
            $this->assertNull($locale->getDefaultValue());
        }
        $rows = [(object) ['locale' => 'en', 'field' => 'title', 'value' => 'Hello'], (object) ['locale' => 'fa', 'field' => 'title', 'value' => 'سلام']];
        $this->assertSame(['en' => ['title' => 'Hello']], translationResourceData($rows, 'en'));
        $this->assertCount(2, translationResourceData($rows));
        $this->assertTrue(translationDataSelect(new Collection, 'title')->isEmpty());
    }
}
