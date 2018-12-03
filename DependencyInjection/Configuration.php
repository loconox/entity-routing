<?php

namespace Loconox\EntityRoutingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('loconox_entity_routing');

        $rootNode
            ->children()
                ->scalarNode('entity_manager')->cannotBeEmpty()->defaultValue('default')->end()
                ->arrayNode('class')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('slug')->cannotBeEmpty()->defaultValue('Loconox\\EntityRoutingBundle\\Entity\\Slug')->end()
                    ->end()
                ->end()
                ->arrayNode('routes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('default')
                            ->addDefaultsIfNotSet()
                            ->children()
                            ->scalarNode('type')->cannotBeEmpty()->defaultValue('annotation')->end()
                            ->scalarNode('resource')->cannotBeEmpty()->defaultValue('../src/Controller')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
