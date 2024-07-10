<?php

namespace Shopware\ServiceBundle\Test\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\ServiceBundle\Message\ShopUpdated;
use Shopware\ServiceBundle\MessageHandler\ShopUpdatedHandler;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Service\ShopUpdater;

#[CoversClass(ShopUpdatedHandler::class)]
#[CoversClass(ShopUpdated::class)]
class ShopUpdatedHandlerTest extends TestCase
{
    public function testHandlerDelegatesToService(): void
    {
        $updater = static::createMock(ShopUpdater::class);
        $handler = new ShopUpdatedHandler($updater);

        $updater->expects(static::once())
            ->method('run')
            ->with('shopId', 'toVersion');

        $message = new ShopUpdated('shopId', 'toVersion');
        $handler->__invoke($message);
    }
}
