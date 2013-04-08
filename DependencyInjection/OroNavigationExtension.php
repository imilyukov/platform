<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroNavigationExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $entitiesConfig = array();
        $titlesConfig = array();

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            if (is_file($file = dirname($reflection->getFilename()).'/Resources/config/menu.yml')) {
                $bundleConfig = Yaml::parse(realpath($file));
                // merge entity configs
                if (isset($bundleConfig['oro_menu_config'])) {
                    foreach ($bundleConfig['oro_menu_config'] as $entity => $entityConfig) {
                        if (isset($entitiesConfig['oro_menu_config'][$entity])) {
                            $entitiesConfig['oro_menu_config'][$entity] =
                                array_replace_recursive($entitiesConfig['oro_menu_config'][$entity], $entityConfig);
                        } else {
                            $entitiesConfig['oro_menu_config'][$entity] = $entityConfig;
                        }
                    }
                }
            }

            if (is_file($file = dirname($reflection->getFilename()) . '/Resources/config/titles.yml')) {
                $bundleConfig = Yaml::parse(realpath($file));
                if (!is_null($bundleConfig)) {
                    $titlesConfig += $bundleConfig;
                }
            }

        }

        // process configurations to validate and merge
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $entitiesConfig);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $container->setParameter('oro_menu_config', $config);
        $container->setParameter('oro_titles', $titlesConfig);
    }
}
