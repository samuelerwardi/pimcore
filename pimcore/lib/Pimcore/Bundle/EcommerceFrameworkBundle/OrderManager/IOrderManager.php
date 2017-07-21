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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Model\Object\Folder;

interface IOrderManager
{
    /**
     * @return IOrderList
     */
    public function createOrderList(): IOrderList;

    /**
     * @param AbstractOrder $order
     *
     * @return IOrderAgent
     */
    public function createOrderAgent(AbstractOrder $order): IOrderAgent;

    /**
     * @param int|Folder $orderParentFolder
     */
    public function setParentOrderFolder($orderParentFolder);

    /**
     * @param string $classname
     */
    public function setOrderClass(string $classname);

    /**
     * @param string $classname
     */
    public function setOrderItemClass(string $classname);

    /**
     * Looks if order object for given cart already exists, otherwise creates it
     *
     * move to ordermanagers
     *
     * @return AbstractOrder
     */
    public function getOrCreateOrderFromCart(ICart $cart);

    /**
     * Looks if order object for given cart exists and returns it - it does not create it!
     *
     * @param ICart $cart
     *
     * @return AbstractOrder
     */
    public function getOrderFromCart(ICart $cart);

    /**
     * Returns order based on given payment status
     *
     * @param IStatus $paymentStatus
     *
     * @return AbstractOrder
     */
    public function getOrderByPaymentStatus(IStatus $paymentStatus);

    /**
     * Builds order listing
     *
     * @return \Pimcore\Model\Object\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderList();

    /**
     * Build order item listing
     *
     * @return \Pimcore\Model\Object\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderItemList();
}
