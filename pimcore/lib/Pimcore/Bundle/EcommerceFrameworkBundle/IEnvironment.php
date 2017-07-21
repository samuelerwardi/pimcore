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

namespace Pimcore\Bundle\EcommerceFrameworkBundle;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;

/**
 * Interface for environment implementations of online shop framework
 */
interface IEnvironment extends IComponent
{
    /**
     * Returns current user id
     *
     * @return int
     */
    public function getCurrentUserId();

    /**
     * Sets current user id
     *
     * @param int $userId
     *
     * @return void
     */
    public function setCurrentUserId($userId);

    /**
     * Checks if a user id is set
     *
     * @return bool
     */
    public function hasCurrentUserId(): bool;

    /**
     * Sets custom item to environment - which is saved to the session then
     * save()-call is needed to save the custom items
     *
     * @param $key
     * @param $value
     */
    public function setCustomItem($key, $value);

    /**
     * Removes custom item from the environment
     * save()-call is needed to save the custom items
     *
     * @param $key
     */
    public function removeCustomItem($key);

    /**
     * Returns custom saved item from environment
     *
     * @param $key
     *
     * @return mixed
     */
    public function getCustomItem($key);

    /**
     * Returns all custom items from environment
     *
     * @return array
     */
    public function getAllCustomItems(): array;

    /**
     * Resets environment
     * save()-call is needed to save changes
     */
    public function clearEnvironment();

    /**
     * Sets current assortment tenant which is used for indexing and product lists
     *
     * @param string $tenant
     */
    public function setCurrentAssortmentTenant($tenant);

    /**
     * Returns current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentTenant();

    /**
     * Sets current assortment sub tenant which is used for indexing and product lists
     *
     * TODO: is this mixed or string?
     *
     * @param mixed $subTenant
     */
    public function setCurrentAssortmentSubTenant($subTenant);

    /**
     * Returns current sub assortment tenant which is used for indexing and product lists
     *
     * @return mixed
     */
    public function getCurrentAssortmentSubTenant();

    /**
     * Sets current checkout tenant which is used for cart and checkout manager
     *
     * @param string $tenant
     * @param bool $persistent - if set to false, tenant is not stored to session and only valid for current process
     */
    public function setCurrentCheckoutTenant($tenant, bool $persistent = true);

    /**
     * Returns current assortment tenant which is used for cart and checkout manager
     *
     * @return string
     */
    public function getCurrentCheckoutTenant();

    /**
     * Returns instance of default currency
     *
     * @return Currency
     */
    public function getDefaultCurrency(): Currency;

    /**
     * @return bool
     */
    public function getUseGuestCart(): bool;

    /**
     * @param bool $useGuestCart
     */
    public function setUseGuestCart(bool $useGuestCart);

    /**
     * Returns current system locale
     *
     * @return null|string
     */
    public function getSystemLocale();


    /**
     * ===========================================
     *
     *  deprecated functions
     *
     * ===========================================
     */

    /**
     * @deprecated use setCurrentAssortmentTenant instead
     *
     * @param string $tenant
     *
     * @return mixed
     */
    public function setCurrentTenant($tenant);

    /**
     * @deprecated use getCurrentAssortmentTenant instead
     *
     * @return string
     */
    public function getCurrentTenant();

    /**
     * @deprecated use setCurrentAssortmentSubTenant instead
     *
     * @param mixed $tenant
     *
     * @return mixed
     */
    public function setCurrentSubTenant($tenant);

    /**
     * @deprecated use getCurrentAssortmentSubTenant instead
     *
     * @return mixed
     */
    public function getCurrentSubTenant();
}
