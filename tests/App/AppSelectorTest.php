<?php declare(strict_types=1);

namespace Shopware\ServiceBundle\Test\Manifest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\App\NoSupportedAppException;

#[CoversClass(AppSelector::class)]
class ManifestSelectorTest extends TestCase
{
    public function testShopware66DevVersion(): void
    {
        $selector = new AppSelector(__DIR__ . '/manifests');
        $manifest = $selector->select('6.6.9999999.9999999-dev');
        
        static::assertSame('6.6.6.0', $manifest->version);
    }

    #[DataProvider('latestCompatibleManifestVersions')]
    public function testSelectsLatestCompatibleManifestVersion(string $shopwareVersion, string $selectedVersion): void
    {
        $selector = new AppSelector(__DIR__ . '/manifests');
        $manifest = $selector->select($shopwareVersion);
        
        static::assertSame($selectedVersion, $manifest->version);
    }

    public function testThrowsIfNoCompatibleManifestVersionCanBeSelected(): void
    {
        $selector = new AppSelector(__DIR__ . '/manifests');

        static::expectException(NoSupportedAppException::class);
        $selector->select('6.5');
    }

    public static function latestCompatibleManifestVersions(): \Generator
    {
        yield '6.6.0.0' => ['6.6.0.0', '6.6.0.0'];
        yield '6.6.5.0' => ['6.6.5.0', '6.6.0.0'];
        yield '6.6.6.0' => ['6.6.6.0', '6.6.6.0'];
        yield '6.6.9.0' => ['6.6.9.0', '6.6.6.0'];

        yield '6.7.0.0' => ['6.7.0.0', '6.7.0.0'];
        yield '6.7.5.0' => ['6.7.5.0', '6.7.0.0'];
        yield '6.7.7.0' => ['6.7.7.0', '6.7.7.0'];
        yield '6.7.9.0' => ['6.7.9.0', '6.7.7.0'];
    }
}
