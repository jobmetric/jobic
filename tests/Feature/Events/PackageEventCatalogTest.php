<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use JobMetric\EventSystem\Contracts\DomainEvent;
use ReflectionClass;
use Tests\TestCase;

class PackageEventCatalogTest extends TestCase
{
    public function test_every_audited_business_outcome_is_discoverable_translated_and_minimal(): void
    {
        $catalog = json_decode(file_get_contents(base_path('docs/package-event-catalog.json')), true, 512, JSON_THROW_ON_ERROR);
        $keys = [];
        foreach ($catalog as $package => $events) {
            foreach ($events as $class => $expected) {
                $reflection = new ReflectionClass($class);
                self::assertTrue($reflection->isReadOnly(), $class);
                self::assertTrue($reflection->implementsInterface(DomainEvent::class), $class);
                self::assertSame($class, app('EventRegistry')->forKey($class::key()), $package);
                self::assertSame($expected['key'], $class::key());
                self::assertNotContains($class::key(), $keys, 'Duplicate outcome key');
                $keys[] = $class::key();
                foreach (['en', 'fa'] as $locale) {
                    foreach (['group', 'title', 'description'] as $field) {
                        $key = $class::definition()->$field;
                        self::assertNotSame($key, trans($key, [], $locale), "$class $locale $field");
                    }
                }
                self::assertSame(array_column($expected['fields'], 0), array_map(static fn ($property) => $property->name, $reflection->getProperties()));
                foreach ($reflection->getProperties() as $property) {
                    $type = $property->getType();
                    $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];
                    foreach ($types as $item) {
                        self::assertTrue($item->isBuiltin(), "$class::$property->name must not carry a model");
                        self::assertContains($item->getName(), ['string', 'int', 'null']);
                    }
                }
            }
        }
        self::assertGreaterThan(100, count($keys));
    }
}
