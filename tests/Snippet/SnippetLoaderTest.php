<?php

namespace Shopware\tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Snippet\SnippetLoader;

#[CoversClass(SnippetLoader::class)]
class SnippetLoaderTest extends TestCase
{


    public function testItLoadsOnlySnippetFiles(): void
    {
        $loader = new SnippetLoader(__DIR__ . '/fixtures/snippet');

        static::assertEquals([
            'en-GB' => [
                'shopware.service-name.first-snippet' => 'First Snippet',
                'shopware.service-name.second-snippet' => 'Second Snippet',
            ],
            'de-DE' => [
                'shopware.service-name.first-snippet' => 'Erstes Snippet',
                'shopware.service-name.second-snippet' => 'Zweites Snippet',
            ],
        ], $loader->loadSnippets());
    }
}