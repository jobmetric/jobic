<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Contracts\MenuLinkDriver;
use App\Support\MenuLinks\MenuLinkRegistry;
use InvalidArgumentException;
use JobMetric\Form\FormBuilder;
use PHPUnit\Framework\TestCase;

final class MenuLinkRegistryTest extends TestCase
{
    public function test_extensions_can_register_and_replace_a_driver(): void
    {
        $registry = new MenuLinkRegistry;
        $driver = new class implements MenuLinkDriver
        {
            public function key(): string
            {
                return 'post';
            }

            public function label(): string
            {
                return 'Post';
            }

            public function form(): FormBuilder
            {
                return new FormBuilder;
            }

            public function rules(): array
            {
                return ['post_id' => ['required', 'integer']];
            }
        };

        $this->assertSame($registry, $registry->register($driver));
        $this->assertTrue($registry->has('post'));
        $this->assertSame($driver, $registry->get('post'));
        $this->expectException(InvalidArgumentException::class);
        $registry->get('missing');
    }
}
