<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('acme_sylius_example_plugin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('product_images')
                    ->ignoreExtraKeys()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')
                            ->defaultValue('main')
                            ->info('Type of the product image to send to Klarna. If none is specified or the type does not exists on current product then the first image will be used.')
                        ->end()
                        ->scalarNode('filter')
                            ->defaultValue('sylius_medium')
                            ->info('Liip filter to apply to the image. If none is specified then the original image will be used.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
