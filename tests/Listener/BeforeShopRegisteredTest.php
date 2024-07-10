<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\Test\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\App\SDK\Event\BeforeRegistrationCompletedEvent;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Listener\BeforeShopRegistered;
use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Request;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppSelector;

#[CoversClass(BeforeShopRegistered::class)]
class BeforeShopRegisteredTest extends TestCase
{
    public function testShopAndAppHashIsSavedOnShop(): void
    {
        $request = new Request('POST', 'https://example.com', ['Content-Type' => 'application/json', 'sw-version' => '6.6.0.0'], '', );
        $shop = new Shop('shop-id', 'myshop.com', 'secret');

        $event = new BeforeRegistrationCompletedEvent($shop, $request, ['apiKey' => '', 'secretKey' => '']);

        static::assertNull($shop->shopVersion);
        static::assertNull($shop->selectedAppHash);
        static::assertNull($shop->selectedAppVersion);

        $app = new App(__DIR__, 'TestApp', '6.6.0.0', '83d66aff96d408c304345558691adf4cbf6b14738f928f8e8c131e767a891939');

        $manifestSelector = $this->createMock(AppSelector::class);
        $manifestSelector->expects(static::once())
            ->method('select')
            ->with('6.6.0.0')
            ->willReturn($app);

        $listener = new BeforeShopRegistered($manifestSelector);
        $listener->__invoke($event);

        static::assertEquals('6.6.0.0', $shop->shopVersion);
        static::assertEquals('83d66aff96d408c304345558691adf4cbf6b14738f928f8e8c131e767a891939', $shop->selectedAppHash);
        static::assertEquals('6.6.0.0', $shop->selectedAppVersion);
    }
}
