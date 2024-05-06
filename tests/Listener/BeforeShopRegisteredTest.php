<?php

namespace Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\App\SDK\Event\BeforeRegistrationCompletedEvent;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Listener\BeforeShopRegistered;
use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Request;
use Shopware\ServiceBundle\Manifest\Manifest;
use Shopware\ServiceBundle\Manifest\ManifestSelector;

#[CoversClass(BeforeShopRegistered::class)]
class BeforeShopRegisteredTest extends TestCase
{
    public function testShopAndManifestHashIsSavedOnShop(): void
    {
        $request = new Request('POST', 'https://example.com', ['Content-Type' => 'application/json', 'sw-version' => '6.6.0.0'], '', );
        $shop = new Shop('shop-id', 'myshop.com', 'secret');

        $event = new BeforeRegistrationCompletedEvent($shop, $request, []);

        static::assertNull($shop->shopVersion);
        static::assertNull($shop->manifestHash);

        $manifest = new Manifest('6.6.0.0', 'my-manifest-content');

        $manifestSelector = $this->createMock(ManifestSelector::class);
        $manifestSelector->expects(static::once())
            ->method('select')
            ->with('6.6.0.0')
            ->willReturn($manifest);

        $listener = new BeforeShopRegistered($manifestSelector);
        $listener->__invoke($event);

        static::assertEquals('6.6.0.0', $shop->shopVersion);
        static::assertEquals('83d66aff96d408c304345558691adf4cbf6b14738f928f8e8c131e767a891939', $shop->manifestHash);
    }
}
