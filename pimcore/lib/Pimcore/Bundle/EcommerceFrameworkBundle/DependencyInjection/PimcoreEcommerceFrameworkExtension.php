<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class PimcoreEcommerceFrameworkExtension extends ConfigurableExtension
{
    const SERVICE_ID_ENVIRONMENT = 'pimcore_ecommerce.environment';
    const SERVICE_ID_VOUCHER_SERVICE = 'pimcore_ecommerce.voucher_service';
    const SERVICE_ID_TOKEN_MANAGER_FACTORY = 'pimcore_ecommerce.voucher_service.token_manager_factory';
    const SERVICE_ID_TRACKING_MANAGER = 'pimcore_ecommerce.tracking.tracking_manager';

    /**
     * The services below are defined as public as the Factory loads services via get() on
     * demand.
     *
     * @inheritDoc
     */
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('environment.yml');
        $loader->load('cart_manager.yml');
        $loader->load('order_manager.yml');
        $loader->load('price_systems.yml');
        $loader->load('availability_systems.yml');
        $loader->load('voucher_service.yml');
        $loader->load('tracking_manager.yml');

        $this->registerEnvironmentConfiguration($config['environment'], $container);
        $this->registerCartManagerConfiguration($config['cart_manager'], $container);
        $this->registerOrderManagerConfiguration($config['order_manager'], $container);
        $this->registerPriceSystemsConfiguration($config['price_systems'], $container);
        $this->registerAvailabilitySystemsConfiguration($config['availability_systems'], $container);
        $this->registerVoucherServiceConfig($config['voucher_service'], $container);
        $this->registerTrackingManagerConfiguration($config['tracking_manager'], $container);
    }

    private function registerEnvironmentConfiguration(array $config, ContainerBuilder $container)
    {
        $environment = new ChildDefinition($config['environment_id']);
        $environment->setPublic(true);

        $container->setDefinition(self::SERVICE_ID_ENVIRONMENT, $environment);
        $container->setParameter('pimcore_ecommerce.environment.options', $config['options']);
    }

    private function registerCartManagerConfiguration(array $config, ContainerBuilder $container)
    {
        $mapping = [];

        foreach ($config['tenants'] as $tenant => $tenantConfig) {
            $cartManager = new ChildDefinition($tenantConfig['cart_manager_id']);
            $cartManager->setPublic(true);

            $cartFactory = new ChildDefinition($tenantConfig['cart']['factory_id']);
            $cartFactory->setArgument('$options', $tenantConfig['cart']['factory_options']);

            $priceCalculatorFactory = new ChildDefinition($tenantConfig['price_calculator']['factory_id']);
            $priceCalculatorFactory->setArgument(
                '$modificatorConfig',
                $tenantConfig['price_calculator']['modificators']
            );
            $priceCalculatorFactory->setArgument(
                '$options',
                $tenantConfig['price_calculator']['factory_options']
            );

            $cartManager->setArgument('$cartFactory', $cartFactory);
            $cartManager->setArgument('$cartPriceCalculatorFactory', $priceCalculatorFactory);

            // order manager tenant defaults to the same tenant as the cart tenant but can be
            // configured on demand
            $orderManagerTenant = $tenantConfig['order_manager_tenant'] ?? $tenant;

            $cartManager->setArgument('$orderManager', new Reference('pimcore_ecommerce.order_manager.' . $orderManagerTenant));

            $aliasName = sprintf('pimcore_ecommerce.cart_manager.%s', $tenant);
            $container->setDefinition($aliasName, $cartManager);

            $mapping[$tenant] = $aliasName;
        }

        $this->setupLocator($container, 'cart_manager', $mapping);
    }

    private function registerOrderManagerConfiguration(array $config, ContainerBuilder $container)
    {
        $mapping = [];

        foreach ($config['tenants'] as $tenant => $tenantConfig) {
            $orderManager = new ChildDefinition($tenantConfig['order_manager_id']);
            $orderManager->setPublic(true);

            $orderManager->setArgument('$orderAgentFactory', new Reference($tenantConfig['agent_factory_id']));
            $orderManager->setArgument('$options', $tenantConfig['options']);

            $aliasName = sprintf('pimcore_ecommerce.order_manager.%s', $tenant);
            $container->setDefinition($aliasName, $orderManager);

            $mapping[$tenant] = $aliasName;
        }

        $this->setupLocator($container, 'order_manager', $mapping);
    }

    private function registerPriceSystemsConfiguration(array $config, ContainerBuilder $container)
    {
        $mapping = [];

        foreach ($config as $name => $cfg) {
            $aliasName = sprintf('pimcore_ecommerce.price_system.%s', $name);

            $container->setAlias($aliasName, $cfg['id']);
            $mapping[$name] = $aliasName;
        }

        $this->setupLocator($container, 'price_system', $mapping);
    }

    private function registerAvailabilitySystemsConfiguration(array $config, ContainerBuilder $container)
    {
        $mapping = [];

        foreach ($config as $name => $cfg) {
            $aliasName = sprintf('pimcore_ecommerce.availability_system.%s', $name);

            $container->setAlias($aliasName, $cfg['id']);
            $mapping[$name] = $aliasName;
        }

        $this->setupLocator($container, 'availability_system', $mapping);
    }

    private function registerVoucherServiceConfig(array $config, ContainerBuilder $container)
    {
        $voucherService = new ChildDefinition($config['voucher_service_id']);
        $voucherService->setPublic(true);
        $voucherService->setArgument('$options', $config['voucher_service_options']);

        $container->setDefinition(self::SERVICE_ID_VOUCHER_SERVICE, $voucherService);

        $container->setParameter(
            'pimcore_ecommerce.voucher_service.token_manager.mapping',
            $config['token_managers']['mapping']
        );

        $container->setAlias(
            self::SERVICE_ID_TOKEN_MANAGER_FACTORY,
            $config['token_managers']['factory_id']
        );
    }

    private function registerTrackingManagerConfiguration(array $config, ContainerBuilder $container)
    {
        $trackingManager = new ChildDefinition($config['tracking_manager_id']);
        $trackingManager->setPublic(true);

        $container->setDefinition(self::SERVICE_ID_TRACKING_MANAGER, $trackingManager);

        foreach ($config['trackers'] as $name => $trackerConfig) {
            if (!$trackerConfig['enabled']) {
                continue;
            }

            $tracker = new ChildDefinition($trackerConfig['id']);

            if (null !== $trackerConfig['item_builder_id']) {
                $tracker->setArgument('$trackingItemBuilder', new Reference($trackerConfig['item_builder_id']));
            }

            if (null !== $trackerConfig['template_extension']) {
                $tracker->setArgument('$templateExtension', $trackerConfig['template_extension']);
            }

            $trackingManager->addMethodCall('registerTracker', [$tracker]);
        }
    }

    private function setupLocator(ContainerBuilder $container, string $id, array $mapping)
    {
        foreach ($mapping as $name => $reference) {
            $mapping[$name] = new Reference($reference);
        }

        $locator = new Definition(ServiceLocator::class, [$mapping]);
        $locator->setPublic(false);
        $locator->addTag('container.service_locator');

        $container->setDefinition(sprintf('pimcore_ecommerce.locator.%s', $id), $locator);
    }
}
