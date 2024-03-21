<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\DependencyInjection;

use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class ShopwareServiceExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.xml');

        $container->getDefinition(FeatureInstructionSet::class)
            ->setFactory([FeatureInstructionSet::class, 'fromArray'])
            ->setArguments([$config['features']]);

//        $container->getDefinition(ShopInterface::class)
//            ->replaceArgument(0, Shop::class);
    }
}
