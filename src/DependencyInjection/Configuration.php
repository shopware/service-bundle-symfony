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
                ->scalarNode('manifest_directory')
                    ->defaultValue('%kernel.project_dir%/manifest')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
