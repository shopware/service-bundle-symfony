<?php

namespace Shopware\ServiceBundle\Test\App;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppHasher;
use Shopware\ServiceBundle\App\AppLoader;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(AppLoader::class)]
#[CoversClass(AppHasher::class)]
#[CoversClass(App::class)]
class AppLoaderTest extends TestCase
{
    public function testExceptionIsThrownIfNoManifestFile(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Could not find manifest.xml');

        $appLoader = new AppLoader(__DIR__ . '/invalid-app-no-manifest', $this->createMock(AppHasher::class), new ArrayAdapter());
        $appLoader->load();
    }

    public function testExceptionIsThrownIfManifestFileInvalid(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Could not parse manifest.xml');

        $appLoader = new AppLoader(__DIR__ . '/invalid-app-invalid-manifest', $this->createMock(AppHasher::class), new ArrayAdapter());
        $appLoader->load();
    }

    public function testLoadUsesCache(): void
    {
        $apps = [
            ['location' => '/my/app', 'name' => 'MyApp', 'hash' => 'abcded'],
        ];

        $cache = new ArrayAdapter();
        $item = $cache->getItem('app-list');
        $item->set($apps);
        $cache->save($item);

        $appHasher = $this->createMock(AppHasher::class);
        $appHasher->expects(static::never())->method('hash');
        $appLoader = new AppLoader(__DIR__ . '/apps', $appHasher, $cache);
        $result = $appLoader->load();

        static::assertCount(1, $result);
    }

    public function testLoadHashesAllApsInDirectoryAndCachesThem(): void
    {
        $cache = new ArrayAdapter();

        $appHasher = $this->createMock(AppHasher::class);

        $appHasher->expects(static::exactly(4))->method('hash')->willReturnCallback(function (string $path) {
            return match ($path) {
                __DIR__ . '/apps/6.6.0.0' => 'hash1',
                __DIR__ . '/apps/6.6.6.0' => 'hash2',
                __DIR__ . '/apps/6.7.0.0' => 'hash3',
                __DIR__ . '/apps/6.7.7.0' => 'hash4',
                default => null,
            };
        });
        $appLoader = new AppLoader(__DIR__ . '/apps', $appHasher, $cache);

        $result = $appLoader->load();

        static::assertCount(4, $result);

        static::assertSame(__DIR__ . '/apps/6.6.0.0', $result[0]->location);
        static::assertSame('MyCoolService', $result[0]->name);
        static::assertSame('6.6.0.0', $result[0]->version);
        static::assertSame('hash1', $result[0]->hash);

        static::assertSame(__DIR__ . '/apps/6.6.6.0', $result[1]->location);
        static::assertSame('MyCoolService', $result[1]->name);
        static::assertSame('6.6.6.0', $result[1]->version);
        static::assertSame('hash2', $result[1]->hash);

        static::assertSame(__DIR__ . '/apps/6.7.0.0', $result[2]->location);
        static::assertSame('MyCoolService', $result[2]->name);
        static::assertSame('6.7.0.0', $result[2]->version);
        static::assertSame('hash3', $result[2]->hash);

        static::assertSame(__DIR__ . '/apps/6.7.7.0', $result[3]->location);
        static::assertSame('MyCoolService', $result[3]->name);
        static::assertSame('6.7.7.0', $result[3]->version);
        static::assertSame('hash4', $result[3]->hash);

        static::assertTrue($cache->getItem('app-list')->isHit());
    }
}
