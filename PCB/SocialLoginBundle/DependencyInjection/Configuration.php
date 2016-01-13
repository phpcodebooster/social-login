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
                ->scalarNode('login_path')->end()
                ->scalarNode('model_alias')->end()
                ->scalarNode('model_namespace')->end()
                ->arrayNode('facebook')
                    ->children()
                        ->scalarNode('api_key')->end()
                        ->scalarNode('api_secret')->end()
                    ->end()
                ->end()
                ->arrayNode('twitter')
                    ->children()
                        ->scalarNode('api_key')->end()
                        ->scalarNode('api_secret')->end()
                    ->end()
                ->end() 
            ->end()
        ;
        
        return $treeBuilder;
    }
}
