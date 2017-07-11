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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct;
use Pimcore\Model\Element\ElementInterface;

interface ITrackingItemBuilder
{
    /**
     * Build a product view object
     *
     * @param IProduct|ElementInterface $product
     *
     * @return ProductAction
     */
    public function buildProductViewItem(IProduct $product): ProductAction;

    /**
     * Build a product action item object
     *
     * @param IProduct|ElementInterface $product
     *
     * @return ProductAction
     */
    public function buildProductActionItem(IProduct $product): ProductAction;

    /**
     * Build a product impression object
     *
     * @param IProduct|ElementInterface $product
     *
     * @return ProductImpression
     */
    public function buildProductImpressionItem(IProduct $product): ProductImpression;

    /**
     * Build a checkout transaction object
     *
     * @param AbstractOrder $order
     *
     * @return Transaction
     */
    public function buildCheckoutTransaction(AbstractOrder $order): Transaction;

    /**
     * Build checkout items
     *
     * @param AbstractOrder $order
     *
     * @return ProductAction[]
     */
    public function buildCheckoutItems(AbstractOrder $order): array;

    /**
     * Build checkout items by cart
     *
     * @param ICart $cart
     *
     * @return ProductAction[]
     */
    public function buildCheckoutItemsByCart(ICart $cart): array;

    /**
     * Build a checkout item object
     *
     * @param AbstractOrder $order
     * @param AbstractOrderItem $orderItem
     *
     * @return ProductAction
     */
    public function buildCheckoutItem(AbstractOrder $order, AbstractOrderItem $orderItem): ProductAction;
}
