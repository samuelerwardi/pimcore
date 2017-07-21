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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutStep as CheckoutManagerICheckoutStep;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckout;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckoutComplete;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckoutStep;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductActionAdd;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductActionRemove;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductImpression;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductView;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductAction;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductImpression;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Transaction;
use Pimcore\Google\Analytics;

class EnhancedEcommerce extends Tracker implements
    IProductView,
    IProductImpression,
    IProductActionAdd,
    IProductActionRemove,
    ICheckout,
    ICheckoutStep,
    ICheckoutComplete
{
    /**
     * Dependencies to include before any tracking actions
     *
     * @var array
     */
    protected $dependencies = ['ec'];

    /**
     * @var bool
     */
    private $dependenciesIncluded = false;

    protected function getViewScriptPrefix(): string
    {
        return 'analytics/enhanced';
    }

    /**
     * Track product view
     *
     * @param IProduct $product
     */
    public function trackProductView(IProduct $product)
    {
        $this->ensureDependencies();

        $item = $this->getTrackingItemBuilder()->buildProductViewItem($product);

        $parameterBag['productData'] = $this->transformProductAction($item);

        unset($parameterBag['productData']['price']);
        unset($parameterBag['productData']['quantity']);

        $result = $this->templatingEngine->render($this->getViewScript('product_view'), $parameterBag);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track product view
     *
     * @param IProduct $product
     */
    public function trackProductImpression(IProduct $product)
    {
        $this->ensureDependencies();

        $item = $this->getTrackingItemBuilder()->buildProductImpressionItem($product);

        $parameterBag['productData'] = $this->transformProductImpression($item);

        $result = $this->templatingEngine->render($this->getViewScript('product_impression'), $parameterBag);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track product action add
     *
     * @param IProduct $product
     * @param int|float $quantity
     */
    public function trackProductActionAdd(IProduct $product, $quantity = 1)
    {
        $this->ensureDependencies();

        $this->trackProductAction($product, 'add', $quantity);
    }

    /**
     * Track product remove from cart
     *
     * @param IProduct $product
     * @param int|float $quantity
     */
    public function trackProductActionRemove(IProduct $product, $quantity = 1)
    {
        $this->ensureDependencies();

        $this->trackProductAction($product, 'remove', $quantity);
    }

    /**
     * @param $product
     * @param $action
     * @param int|float $quantity
     */
    protected function trackProductAction($product, $action, $quantity = 1)
    {
        $item = $this->getTrackingItemBuilder()->buildProductActionItem($product);
        $item->setQuantity($quantity);

        $parameterBag['productData'] = $this->transformProductAction($item);
        $parameterBag['action'] = $action;

        $result = $this->templatingEngine->render($this->getViewScript('product_action'), $parameterBag);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track start checkout with first step
     *
     * @param ICart $cart
     */
    public function trackCheckout(ICart $cart)
    {
        $this->ensureDependencies();

        $items = $this->getTrackingItemBuilder()->buildCheckoutItemsByCart($cart);

        $parameterBag['items'] = $items;
        $parameterBag['calls'] = $this->buildCheckoutCalls($items);

        $parameterBag['actionData'] = ['step' => 1];

        $result = $this->templatingEngine->render($this->getViewScript('checkout'), $parameterBag);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param CheckoutManagerICheckoutStep $step
     * @param ICart $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(CheckoutManagerICheckoutStep $step, ICart $cart, $stepNumber = null, $checkoutOption = null)
    {
        $this->ensureDependencies();

        $items = $this->getTrackingItemBuilder()->buildCheckoutItemsByCart($cart);

        $parameterBag['items'] = $items;
        $parameterBag['calls'] = [];

        if (!is_null($stepNumber) || !is_null($checkoutOption)) {
            $actionData = ['step' => $stepNumber];

            if (!is_null($checkoutOption)) {
                $actionData['option'] = $checkoutOption;
            }

            $parameterBag['actionData'] = $actionData;
        }

        $result = $this->templatingEngine->render($this->getViewScript('checkout'), $parameterBag);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track checkout complete
     *
     * @param AbstractOrder $order
     */
    public function trackCheckoutComplete(AbstractOrder $order)
    {
        $this->ensureDependencies();

        $transaction = $this->getTrackingItemBuilder()->buildCheckoutTransaction($order);
        $items = $this->getTrackingItemBuilder()->buildCheckoutItems($order);

        $parameterBag['transaction'] = $this->transformTransaction($transaction);
        $parameterBag['items'] = $items;
        $parameterBag['calls'] = $this->buildCheckoutCompleteCalls($transaction, $items);

        $result = $this->templatingEngine->render($this->getViewScript('checkout_complete'), $parameterBag);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param Transaction $transaction
     * @param ProductAction[] $items
     *
     * @return mixed
     */
    protected function buildCheckoutCompleteCalls(Transaction $transaction, array $items)
    {
        $calls = [];
        foreach ($items as $item) {
            $calls[] = $this->transformProductAction($item);
        }

        return $calls;
    }

    /**
     * Transform transaction into classic analytics data array
     *
     * @note city, state, country were dropped as they were optional and never used
     *
     * @param Transaction $transaction
     *
     * @return array
     */
    protected function transformTransaction(Transaction $transaction)
    {
        return [
            'id'         => $transaction->getId(),                           // order ID - required
            'affilation' => $transaction->getAffiliation() ?: '',            // affiliation or store name
            'revenue'    => round($transaction->getTotal(), 2),     // total - required
            'tax'        => round($transaction->getTax(), 2),       // tax
            'shipping'   => round($transaction->getShipping(), 2),  // shipping
        ];
    }

    protected function buildCheckoutCalls(array $items): array
    {
        $calls = [];
        foreach ($items as $item) {
            $calls[] = $this->transformProductAction($item);
        }

        return $calls;
    }

    /**
     * Transform product action into enhanced data object
     *
     * @param ProductAction $item
     *
     * @return array
     */
    protected function transformProductAction(ProductAction $item)
    {
        return $this->filterNullValues([
            'id' => $item->getId(),
            'name' => $item->getName(),
            'category' => $item->getCategory(),
            'brand' => $item->getBrand(),
            'variant' => $item->getVariant(),
            'price' => round($item->getPrice(), 2),
            'quantity' => $item->getQuantity() ?: 1,
            'position' => $item->getPosition(),
            'coupon' => $item->getCoupon()
        ]);
    }

    /**
     * Transform product action into enhanced data object
     *
     * @param ProductImpression $item
     *
     * @return array
     */
    protected function transformProductImpression(ProductImpression $item)
    {
        return $this->filterNullValues([
            'id' => $item->getId(),
            'name' => $item->getName(),
            'category' => $item->getCategory(),
            'brand' => $item->getBrand(),
            'variant' => $item->getVariant(),
            'price' => round($item->getPrice(), 2),
            'list' => $item->getList(),
            'position' => $item->getPosition()
        ]);
    }

    protected function ensureDependencies()
    {
        if ($this->dependenciesIncluded || empty($this->dependencies)) {
            return;
        }

        $result = $this->templatingEngine->render($this->getViewScript('dependencies'), [
            'dependencies' => $this->dependencies
        ]);

        Analytics::addAdditionalCode($result, 'beforePageview');

        $this->dependenciesIncluded = true;
    }
}
