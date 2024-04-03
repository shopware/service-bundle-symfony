<?php declare(strict_types=1);

namespace Shopware\ServiceBundle\Test\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shopware\ServiceBundle\Feature\Feature;
use Shopware\ServiceBundle\Feature\FeatureInstruction;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Shopware\ServiceBundle\Feature\FeatureInstructionType;
use Shopware\ServiceBundle\Feature\Features\MockFeature;
use Shopware\ServiceBundle\Feature\ShopOperation;

#[CoversClass(FeatureInstruction::class)]
class FeatureInstructionTest extends TestCase
{
    #[DataProvider('operationProvider')]
    public function testMatch(ShopOperation $operation, bool $expectedResult): void
    {
        $feature = new FeatureInstruction(
            'SomeFeature',
            FeatureInstructionType::INSTALL,
            '6.7',
            new MockFeature(),
            []
        );

        static::assertEquals($expectedResult, $feature->match($operation));
    }

    public static function operationProvider(): array
    {
        return [
            'install-verison-lower-than-target' => [
                ShopOperation::install('6.6'),
                false,
            ],
            'install-verison-with-same-target' => [
                ShopOperation::install('6.7'),
                true,
            ],
            'install-verison-with-higher-target' => [
                ShopOperation::install('6.8'),
                true,
            ],
            'updating-verison-to-target' => [
                ShopOperation::update('6.6', '6.7'),
                true,
            ],
            'updating-verison-to-lower-than-target' => [
                ShopOperation::update('6.5', '6.6'),
                false,
            ],
            'updating-verison-to-higher-than-target' => [
                ShopOperation::update('6.5', '6.8'),
                true,
            ],
            'uninstallating' => [
                ShopOperation::uninstall(),
                false,
            ]
        ];
    }
}
