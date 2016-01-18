<?php

namespace PCB\SocialLoginBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PCBSocialLoginExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        /** Integrated **/
        $container->setParameter('twitter', $config['twitter']);
        $container->setParameter('linkedin', $config['linkedin']);
        $container->setParameter('facebook', $config['facebook']);
        $container->setParameter('google', 	 $config['google']);
        
        /** Integration Required **/
        $container->setParameter('bitbucket', $config['bitbucket']);
        $container->setParameter('dropbox', $config['dropbox']);
        $container->setParameter('flickr', $config['flickr']);
        $container->setParameter('foursquare', $config['foursquare']);
        $container->setParameter('github', $config['github']);
        $container->setParameter('instagram', $config['instagram']);
        $container->setParameter('microsoft', $config['microsoft']);
        $container->setParameter('openid', $config['openid']);
        $container->setParameter('pinterest', $config['pinterest']);
        $container->setParameter('reddit', $config['reddit']);
        $container->setParameter('tumblr', $config['tumblr']);
        $container->setParameter('vimeo', $config['vimeo']);
        $container->setParameter('yahoo', $config['yahoo']);
        
        $container->setParameter('login_path', $config['login_path']);
        $container->setParameter('model_alias', $config['model_alias']);
        $container->setParameter('model_namespace', $config['model_namespace']);
        
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
