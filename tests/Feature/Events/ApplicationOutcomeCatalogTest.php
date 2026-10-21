<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use ReflectionClass;
use Tests\TestCase;

class ApplicationOutcomeCatalogTest extends TestCase
{
    public function test_application_outcomes_have_registered_keys_translations_and_scalar_payloads(): void
    {
        $entries = json_decode(file_get_contents(base_path('docs/application-event-catalog.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($entries as $name => $entry) {
            $class = 'App\\Events\\'.$entry['agg'].'\\'.$name;
            self::assertSame($entry['key'], $class::key());
            self::assertSame($class, app('EventRegistry')->forKey($class::key()));
            $reflection = new ReflectionClass($class);
            self::assertTrue($reflection->isReadOnly());
            self::assertSame(array_column($entry['fields'], 0), array_map(static fn ($property) => $property->name, $reflection->getProperties()));
            foreach (['en', 'fa'] as $locale) {
                foreach (['group', 'title', 'description'] as $field) {
                    $key = $class::definition()->$field;
                    self::assertNotSame($key, trans($key, [], $locale));
                }
            }
        }
    }
}
