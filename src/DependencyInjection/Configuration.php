<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Initializes configuration tree for redirect-bundle.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sunsetbeat_sulu_linkedin_feed');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
        ->children()
            ->arrayNode('menu')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('main_menu')
                            ->defaultValue(10)
                        ->end()
                        ->arrayNode('sub_menu')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('linkedin_feed')
                                    ->defaultValue(10)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
