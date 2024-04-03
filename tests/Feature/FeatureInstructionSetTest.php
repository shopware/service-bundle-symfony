<?php declare(strict_types=1);

namespace Shopware\ServiceBundle\Test\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Shopware\ServiceBundle\Feature\ShopOperation;

#[CoversClass(FeatureInstructionSet::class)]
class FeatureInstructionSetTest extends TestCase
{
    public function testInstallDeltaForMultipleFeatures(): void
    {
        $features = [
            [
                'name' => 'Webhook1',
                'type' => 'Webhook',
                'minimumShopwareVersion' => '6.6',
                'config' => [
                    'url' => 'https://example.com/webhook',
                    'name' => 'OrderCreatedWebhook',
                    'event' => 'order.created',
                ],
            ],
            [
                'name' => 'Webhook2',
                'type' => 'Webhook',
                'minimumShopwareVersion' => '6.6',
                'config' => [
                    'url' => 'https://example.com/webhook-feature-2',
                    'name' => 'ProductCreatedWebhook',
                    'event' => 'product.created',
                ],
            ]
        ];

        $instructionSet = FeatureInstructionSet::fromArray($features);
        $delta = $instructionSet->getDelta(ShopOperation::install('6.6'));

        static::assertEquals(
            [
                'webhook' => [
                    [
                        'url' => 'https://example.com/webhook',
                        'name' => 'OrderCreatedWebhook',
                        'event' => 'order.created',
                    ],
                    [
                        'url' => 'https://example.com/webhook-feature-2',
                        'name' => 'ProductCreatedWebhook',
                        'event' => 'product.created',
                    ]
                ]
            ],
            $delta
        );
    }

    public function testDeltaDoesNotIncludeFeaturesWhichRequireNewerVersions(): void
    {
        $features = [
            [
                'name' => 'Webhook1',
                'type' => 'Webhook',
                'minimumShopwareVersion' => '6.6',
                'config' => [
                    'url' => 'https://example.com/webhook',
                    'name' => 'OrderCreatedWebhook',
                    'event' => 'order.created',
                ],
            ],
            [
                'name' => 'Webhook2',
                'type' => 'Webhook',
                'minimumShopwareVersion' => '6.7',
                'config' => [
                    'url' => 'https://example.com/webhook-feature-2',
                    'name' => 'ProductCreatedWebhook',
                    'event' => 'product.created',
                ],
            ]
        ];

        $instructionSet = FeatureInstructionSet::fromArray($features);
        $delta = $instructionSet->getDelta(ShopOperation::install('6.6'));

        static::assertEquals(
            [
                'webhook' => [
                    [
                        'url' => 'https://example.com/webhook',
                        'name' => 'OrderCreatedWebhook',
                        'event' => 'order.created',
                    ],
                ]
            ],
            $delta
        );
    }
}
