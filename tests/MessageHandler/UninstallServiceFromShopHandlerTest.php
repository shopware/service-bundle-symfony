<?php

namespace Shopware\ServiceBundle\Test\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\ServiceBundle\Message\UninstallServiceFromShop;
use Shopware\ServiceBundle\MessageHandler\UninstallServiceFromShopHandler;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Service\ShopServiceUninstaller;

#[CoversClass(UninstallServiceFromShop::class)]
#[CoversClass(UninstallServiceFromShopHandler::class)]
class UninstallServiceFromShopHandlerTest extends TestCase
{
    public function testHandlerDelegatesToService(): void
    {
        $updater = static::createMock(ShopServiceUninstaller::class);
        $handler = new UninstallServiceFromShopHandler($updater);

        $updater->expects(static::once())
            ->method('run')
            ->with('shopId');

        $message = new UninstallServiceFromShop('shopId');
        $handler->__invoke($message);
    }
}
