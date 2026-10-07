<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Rules\EditorContentRule;
use App\Support\EditorDriverRegistry;
use Illuminate\Container\Container;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;

class EditorDriverRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('translator', new Translator(new ArrayLoader, 'en'));
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_fluent_registration_exposes_client_tool_configuration(): void
    {
        $registry = new EditorDriverRegistry;
        $registry->register('callout')
            ->label('Callout')
            ->clientClass('Vendor.CalloutTool')
            ->script('https://example.test/callout.js')
            ->clientConfig(['inlineToolbar' => true])
            ->schema(['text' => 'string'])
            ->register();

        $this->assertSame([
            'callout' => [
                'class' => 'Vendor.CalloutTool',
                'label' => 'Callout',
                'script' => 'https://example.test/callout.js',
                'inlineToolbar' => true,
            ],
        ], $registry->clientTools());
        $this->assertTrue($registry->get('callout')->validate(['text' => 'hello']));
        $this->assertFalse($registry->get('callout')->validate(['text' => ['invalid']]));
    }

    public function test_editor_document_rejects_unknown_and_invalid_block_data(): void
    {
        $registry = new EditorDriverRegistry;
        $registry->register('header')->schema(['text' => 'string', 'level' => 'integer'])->register();
        $rule = new EditorContentRule($registry);

        $this->assertRulePasses($rule, ['blocks' => [['type' => 'header', 'data' => ['text' => 'Title', 'level' => 2]]]]);
        $this->assertRuleFails($rule, ['blocks' => [['type' => 'header', 'data' => ['level' => 2]]]]);
        $this->assertRuleFails($rule, ['blocks' => [['type' => 'unknown', 'data' => []]]]);
        $this->assertRuleFails($rule, ['blocks' => [['type' => 'header', 'data' => ['text' => ['bad'], 'level' => 2]]]]);
    }

    public function test_driver_specific_validators_can_enforce_tool_payloads(): void
    {
        $registry = new EditorDriverRegistry;
        $registry->register('image')->schema(['file' => 'array'])->validateUsing(static function (mixed $data): bool {
            if (! is_array($data) || ! is_array($data['file'] ?? null)) {
                return false;
            }
            $url = parse_url((string) ($data['file']['url'] ?? ''));
            return is_array($url) && in_array($url['scheme'] ?? null, ['http', 'https'], true);
        })->register();

        $image = $registry->get('image');
        $this->assertTrue($image->validate(['file' => ['url' => 'https://example.test/image.png', 'id' => 10]]));
        $this->assertFalse($image->validate(['file' => ['url' => 'javascript:alert(1)']]));
    }

    private function assertRulePasses(EditorContentRule $rule, array $document): void
    {
        $errors = [];
        $rule->validate('content', json_encode($document), static function (string $message) use (&$errors): void { $errors[] = $message; });
        $this->assertSame([], $errors);
    }

    private function assertRuleFails(EditorContentRule $rule, array $document): void
    {
        $errors = [];
        $rule->validate('content', json_encode($document), static function (string $message) use (&$errors): void { $errors[] = $message; });
        $this->assertNotEmpty($errors);
    }
}
