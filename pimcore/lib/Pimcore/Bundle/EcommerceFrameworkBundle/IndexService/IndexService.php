<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Exception\DefaultWorkerNotFoundException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Exception\WorkerNotFoundException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;

class IndexService
{
    /**
     * @var IEnvironment
     */
    protected $environment;

    /**
     * @var IWorker
     */
    protected $defaultWorker;

    /**
     * @var IWorker[]
     */
    protected $tenantWorkers = [];

    public function __construct(IWorker $defaultWorker = null, array $tenantWorkers = [])
    {
        $this->defaultWorker = $defaultWorker;

        foreach ($tenantWorkers as $tenant => $worker) {
            $this->registerTenantWorker($tenant, $worker);
        }
    }

    protected function registerTenantWorker(string $tenant, IWorker $worker)
    {
        $this->tenantWorkers[$tenant] = $worker;
    }

//    public function __construct($config)
//    {
//        if (!(string)$config->disableDefaultTenant) {
//            $this->defaultWorker = new DefaultMysql(new Config\DefaultMysql('default', $config));
//        }
//
//        $this->tenantWorkers = [];
//        if ($config->tenants && $config->tenants instanceof Config) {
//            foreach ($config->tenants as $name => $tenant) {
//                $tenantConfigClass = (string) $tenant->class;
//                $tenantConfig = $tenant;
//                if ($tenant->file) {
//                    $tenantConfig = new Config(require PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . ((string)$tenant->file), true);
//                    $tenantConfig = $tenantConfig->tenant;
//                }
//
//                /**
//                 * @var $tenantConfig IConfig
//                 */
//                $tenantConfig = new $tenantConfigClass($name, $tenantConfig, $config);
//                $worker = $tenantConfig->getTenantWorker();
//                $this->tenantWorkers[$name] = $worker;
//            }
//        }
//    }

    /**
     * Returns a specific Tenant Worker
     *
     * @param string $tenant
     *
     * @return IWorker
     * @throws WorkerNotFoundException
     */
    public function getTenantWorker(string $tenant): IWorker
    {
        if (!array_key_exists($tenant, $this->tenantWorkers)) {
            throw new WorkerNotFoundException(sprintf('Tenant "%s" doesn\'t exist', $tenant));
        }

        return $this->tenantWorkers[$tenant];
    }

    /**
     * @deprecated
     *
     * @param string|null $tenant
     *
     * @return array
     */
    public function getGeneralSearchColumns(string $tenant = null)
    {
        return $this->getGeneralSearchAttributes($tenant);
    }

    /**
     * Returns all attributes marked as general search attributes for full text search
     *
     * @param string $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getGeneralSearchAttributes(string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getGeneralSearchAttributes();
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * @deprecated
     */
    public function createOrUpdateTable()
    {
        $this->createOrUpdateIndexStructures();
    }

    /**
     * Creates or updates necessary index structures (e.g. database tables)
     */
    public function createOrUpdateIndexStructures()
    {
        if ($this->defaultWorker) {
            $this->defaultWorker->createOrUpdateIndexStructures();
        }

        foreach ($this->tenantWorkers as $name => $tenant) {
            $tenant->createOrUpdateIndexStructures();
        }
    }

    /**
     * Deletes given element from index
     *
     * @param IIndexable $object
     */
    public function deleteFromIndex(IIndexable $object)
    {
        if ($this->defaultWorker) {
            $this->defaultWorker->deleteFromIndex($object);
        }

        foreach ($this->tenantWorkers as $name => $tenant) {
            $tenant->deleteFromIndex($object);
        }
    }

    /**
     * Updates given element in index
     *
     * @param IIndexable $object
     */
    public function updateIndex(IIndexable $object)
    {
        if ($this->defaultWorker) {
            $this->defaultWorker->updateIndex($object);
        }

        foreach ($this->tenantWorkers as $name => $tenant) {
            $tenant->updateIndex($object);
        }
    }

    /**
     * Returns all index attributes
     *
     * @param bool $considerHideInFieldList
     * @param string $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getIndexAttributes(bool $considerHideInFieldList = false, string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getIndexAttributes($considerHideInFieldList);
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * @deprecated
     *
     * @param bool $considerHideInFieldList
     * @param null $tenant
     *
     * @return mixed
     *
     * @throws InvalidConfigException
     */
    public function getIndexColumns($considerHideInFieldList = false, $tenant = null)
    {
        return $this->getIndexAttributes($considerHideInFieldList, $tenant);
    }

    /**
     * Returns all filter groups
     *
     * @param string $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getAllFilterGroups(string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getAllFilterGroups();
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * Returns all index attributes for a given filter group
     *
     * @param $filterType
     * @param string $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getIndexAttributesByFilterGroup($filterType, string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getIndexAttributesByFilterGroup($filterType);
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * @deprecated
     *
     * @param $filterType
     * @param null $tenant
     *
     * @return mixed
     *
     * @throws InvalidConfigException
     */
    public function getIndexColumnsByFilterGroup($filterType, $tenant = null)
    {
        return $this->getIndexAttributesByFilterGroup($filterType, $tenant);
    }

    /**
     * Returns current tenant configuration
     *
     * @return IConfig
     *
     * @throws InvalidConfigException
     */
    public function getCurrentTenantConfig()
    {
        return $this->getCurrentTenantWorker()->getTenantConfig();
    }

    public function getCurrentTenantWorker(): IWorker
    {
        return $this->resolveTenantWorker();
    }

    public function getProductListForCurrentTenant(): IProductList
    {
        $tenantWorker = $this->getCurrentTenantWorker();

        return $tenantWorker->getProductList();
    }

    public function getProductListForTenant(string $tenant): IProductList
    {
        $tenantWorker = $this->resolveTenantWorker($tenant);

        return $tenantWorker->getProductList();
    }

    /**
     * Resolve tenant worker either from given tenant name or from the current tenant
     *
     * @param string|null $tenant
     *
     * @return IWorker
     * @throws WorkerNotFoundException
     */
    protected function resolveTenantWorker(string $tenant = null): IWorker
    {
        if (null === $tenant) {
            $tenant = $this->environment->getCurrentAssortmentTenant();
        }

        if ($tenant) {
            if (!array_key_exists($tenant, $this->tenantWorkers)) {
                throw new WorkerNotFoundException(sprintf('Tenant "%s" doesn\'t exist', $tenant));
            }

            return $this->tenantWorkers[$tenant];
        }

        if (null === $this->defaultWorker) {
            throw new DefaultWorkerNotFoundException('Could not load worker as no tenant is set and no default worker is defined');
        }

        return $this->defaultWorker;
    }
}
