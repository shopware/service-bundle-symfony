<?php

namespace Shopware\ServiceBundle\Test\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Message\ShopUpdated;

#[CoversClass(ShopUpdated::class)]
class ShopUpdatedTest extends TestCase
{
    public function testAccessors(): void
    {
        $message = new ShopUpdated('shopId', 'toVersion');

        static::assertSame('shopId', $message->shopId);
        static::assertSame('toVersion', $message->toVersion);
    }
}
