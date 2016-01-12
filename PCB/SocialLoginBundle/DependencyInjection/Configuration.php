<?php

namespace PCB\SocialLoginBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pcb_social_login');

		$rootNode
            ->children()
                ->arrayNode('facebook')
                    ->children()
                        ->scalarNode('app_id')->end()
                        ->scalarNode('secret_id')->end()
                    ->end()
                ->end() 
            ->end()
        ;
        
        return $treeBuilder;
    }
}
