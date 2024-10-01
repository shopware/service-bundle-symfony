<?php

namespace Shopware\ServiceBundle\Test\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\ServiceBundle\Message\UninstallServiceFromShop;
use PHPUnit\Framework\TestCase;

#[CoversClass(UninstallServiceFromShop::class)]
class UninstallServiceFromShopTest extends TestCase
{
    public function testAccessors(): void
    {
        $message = new UninstallServiceFromShop('shopId');

        static::assertSame('shopId', $message->shopId);
    }
}
