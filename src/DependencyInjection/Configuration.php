<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\DependencyInjection;

use Shopware\AppBundle\Entity\AbstractShop;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('shopware_service');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('features')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('type')->isRequired()->end()
                            ->arrayNode('config')
                                ->children()
                                    ->scalarNode('url')->end()
                                    ->scalarNode('name')->end()
                                    ->scalarNode('event')->end()
                                    ->arrayNode('permissions')
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('minimumShopwareVersion')->isRequired()->end()
                            ->booleanNode('remove')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
