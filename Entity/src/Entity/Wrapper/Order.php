<?php
/**
 * Entity\Wrapper\Order
 *
 * @category Entity
 * @package Entity\Wrapper
 * @author Seo Yao
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2014 LERO9 Ltd.
 * @license Commercial - All Rights Reserved
 */

namespace Entity\Wrapper;

use Entity\Entity;
use Magelink\Exception\MagelinkException;


class Order extends AbstractWrapper
{

    /** @var \Entity\Wrapper\Order[] $_cachedAllOrders */
    protected $_cachedAllOrders = array();
    /** @var \Entity\Wrapper\Order[] $_cachedSegregatedOrders */
    protected $_cachedSegregatedOrders = array();

    /** @var \Entity\Wrapper\Orderitem[] $_cachedOrderitems */
    protected $_cachedOrderitems = array();
    /** @var \Entity\Wrapper\Creditmemo[] $_cachedCreditmemos */
    protected $_cachedCreditmemos = array();
    /** @var \Entity\Wrapper\Creditmemo[] $_cachedCreditmemos */
    protected $_cachedCreditmemoitems = array();

    /** @var float $_cachedOrderTotal */
    protected $_cachedOrderTotal = 0;


    /**
     * Alias of getOrderitems: Retrieve all the order items attached to this order
     * @return \Entity\Wrapper\Orderitem[]
     */
    public function getItems()
    {
        return $this->getOrderitems();
    }

    /**
     * Retrieve all the order items attached to this order
     * @return \Entity\Wrapper\Orderitem[]
     */
    public function getOrderitems($refresh = FALSE)
    {
        if (!$this->_cachedOrderitems || $refresh) {
            $this->_cachedOrderitems = $this->getChildren('orderitem');
        }

        return $this->_cachedOrderitems;
    }

    /**
     * Retrieve all direct assigned credit memos
     * @return \Entity\Wrapper\Creditmemo[]
     */
    public function getCreditmemos()
    {
        if (!$this->_cachedCreditmemos) {
            $this->_cachedCreditmemos = $this->getChildren('creditmemo');
        }

        return $this->_cachedCreditmemos;
    }

    /**
     * Retrieve all direct assigned credit memo items
     * @return \Entity\Wrapper\Creditmemoitems[]
     */
    public function getCreditmemoitems()
    {
        if (!$this->_cachedCreditmemoitems) {
            foreach ($this->getCreditmemos() as $creditmemo) {
                $this->_cachedCreditmemoitems =
                    array_merge($this->_cachedCreditmemoitems, $creditmemo->getCreditmemoitems());
            }
        }

        return $this->_cachedCreditmemoitems;
    }

    /**
     * Determine if this is a root original order
     * @return (bool) $isRootOriginal
     */
    public function isOriginalOrder()
    {
        if ($this->getData('original_order', FALSE)) {
            $isOriginal = FALSE;
        }else{
            /** @var \Entity\Service\EntityService $entityService */
            $entityService = $this->getServiceLocator()->get('entityService');
            $this->_cachedSegregatedOrders = $entityService->loadSegregatedOrders($this->getLoadedNodeId(), $this);
            $isOriginal = TRUE;
        }
        return $isOriginal;
    }

    /**
     * Is this an segregated order?
     * @return bool
     */
    public function isSegregated()
    {
        return !$this->isOriginalOrder();
    }

    /**
     * Get original order id
     * @return int|string
     */
    public function getOriginalOrderId()
    {
        if ($this->isOriginalOrder()) {
            $originalOrderId = $this->getId();
        }else{
            $originalOrderId = $this->getData('original_order');
        }

        return $originalOrderId;
    }

    /**
     * Get original order entity
     * @return Entity|Order|null
     */
    public function getOriginalOrder()
    {
        if ($this->isOriginalOrder()) {
            $originalOrder = $this;
        }else{
            /** @var \Entity\Service\EntityService $entityService */
            $entityService = $this->getServiceLocator()->get('entityService');
            $originalOrder = $entityService->loadEntityId($this->getLoadedNodeId(), $this->getOriginalOrderId());
        }

        return $originalOrder;
    }

    /**
     * Retrieve all orders, if this is an original order
     * @return \Entity\Wrapper\Order[]
     */
    public function getSegregatedOrders()
    {
        $this->isOriginalOrder();
        return $this->_cachedSegregatedOrders;
    }

    /**
     * Get all order which belong to the same original order inclusive this one
     * @return Order[] $this->_cachedAllOrders
     */
    public function getAllOrders()
    {
        if (!$this->_cachedAllOrders) {
            $order = $this->getOriginalOrder();
            $this->_cachedAllOrders = array_merge(array($order), $order->getSegregatedOrders());
        }

        return $this->_cachedAllOrders;
    }

    /**
     * Retrieves all order items of the original order and the segregated orders
     * @return \Entity\Wrapper\Orderitem[]
     */
    public function getOriginalOrderitems()
    {
        $orderItems = array();
        foreach ($this->getAllOrders() as $order) {
            $orderItems = array_merge($orderItems, $order->getOrderitems());
        }

        return $orderItems;
    }

    /**
     * Retrieve all credit memos assigned to the original order and the segregated orders
     * @return \Entity\Wrapper\Creditmemo[]
     */
    public function getOriginalCreditmemos()
    {
        $creditmemos = array();
        foreach ($this->getAllOrders() as $order) {
            $creditmemos = array_merge($creditmemos, $order->getCreditmemos());
        }

        return $creditmemos;
    }

    /**
     * Retrieve all credit memo items assigned to the original order and the segregated orders
     * @return \Entity\Wrapper\Creditmemoitems[]
     */
    public function getOriginalCreditmemoitems()
    {
        $creditmemoitems = array();
        foreach ($this->getOriginalCreditmemos() as $creditmemo) {
            $creditmemoitems = array_merge($creditmemoitems, $creditmemo->getCreditmemoitems());
        }

        return $creditmemoitems;
    }

    /**
     * Get the entity class of the shipping address
     * @return \Entity\Wrapper\Address
     */
    public function getBillingAddressEntity()
    {
        $address = $this->resolve('billing_address', 'address');
        return $address;
    }

    /**
     * Get the entity class of the shipping address
     * @return \Entity\Wrapper\Address
     */
    public function getShippingAddressEntity()
    {
        $address = $this->resolve('shipping_address', 'address');
        return $address;
    }

    /**
     * Get short shipping address
     * @return string
     */
    public function getShippingAddressShort()
    {
        if ($address = $this->getShippingAddressEntity()) {
            $addressShort = $address->getAddressShort();
        }else{
            $addressShort = '';
        }

        return $addressShort;

    }

    /**
     * Get full shipping address
     * @return string
     */
    public function getShippingAddressFull($separator="<br/>")
    {
        if ($address = $this->getShippingAddressEntity()) {
            $addressFull = $address->getAddressFull($separator);
        }else{
            $addressFull = '';
        }

        return $addressFull;
    }

    /**
     * Get full billing address
     * @return string
     */
    public function getBillingAddressFull($separator="<br/>")
    {
        if ($address = $this->getBillingAddressEntity()) {
            $addressFull = $address->getAddressFull($separator);
        }else{
            $addressFull = '';
        }

        return $addressFull;
    }

    /**
     * Get the uppermost original order
     * @return \Entity\Wrapper\Order|NULL $order
     */
    public function getOriginalOrderRecursive()
    {
        $order = $this;
        while ($order->getData('original_order', FALSE)) {
            $order = $this->getServiceLocator()->get('entityService')
                ->loadEntityId($this->getLoadedNodeId(), $order->getData('original_order'));
        }

        return $order;
    }

    /**
     * Returns the sum quantity of all order items
     * @return int
     * @throws MagelinkException
     */
    public function getOrderitemsTotalQuantity()
    {
        /** @var \Entity\Service\EntityService $entityService */
        $entityService = $this->getServiceLocator()->get('entityService');

        $totalItemAggregate = $entityService->aggregateEntity(
            $this->getLoadedNodeId(), 'orderitem', FALSE,
            array('quantity'=>'SUM'),
            array('PARENT_ID'=>$this->getId()),
            array('PARENT_ID'=>'eq'));
        if (!array_key_exists('agg_quantity_sum', $totalItemAggregate)) {
            throw new MagelinkException('Invalid response from aggregateEntity');
            $quantity = NULL;
        }else{
            $quantity = (int) $totalItemAggregate['agg_quantity_sum'];
        }

        return $quantity;
    }

    /**
     * Returns the sum delivery quantity of all order items
     * @return int
     * @throws MagelinkException
     */
    public function getOrderitemsTotalDeliveryQuantity()
    {
        $quantities = $this->getOrderitemsDeliveryQuantities();
        $quantity = (int) array_sum($quantities);

        return $quantity;
    }

    /**
     * Returns the sum refunded quantity of all order items
     * @return int
     * @throws MagelinkException
     */
    public function getOrderitemsTotalRefundedQuantity()
    {
        $quantity = intval($this->getOrderitemsTotalQuantity() - $this->getOrderitemsTotalDeliveryQuantity());
        return $quantity;
    }

    /**
     * Get credit memo items quantities of order items
     * @return array
     */
    public function getCreditmemoitemsQuantityGroupedByOrderItemId()
    {
        $quantities = array();
        foreach ($this->getOrderitems() as $orderitem) {
            $alreadyRefundedQuantity = $orderitem->getQuantityRefunded();
            $quantities[$orderitem->getId()] = (int) $alreadyRefundedQuantity;
        }

        return $quantities;
    }

    /**
     * Get delivery quantities in an array[<item id>] = <quantity>
     * @return int[]
     */
    protected function getOrderitemsDeliveryQuantities()
    {
        $quantities = array();
        foreach ($this->getOrderitems() as $orderitem) {
            $quantities[$orderitem->getId()] = $orderitem->getDeliveryQuantity();
        }

        return $quantities;
    }

    /**
     * Get quantities in an array[<item id>] = <quantity>
     * @param array $items
     * @return int[]
     */
    protected function getQuantities(array $items)
    {
        $quantities = array();
        foreach ($items as $item) {
            $quantities[$item->getId()] = $item->getQuantity();
        }

        return $quantities;
    }

    /**
     * Get quantities of direct assigned credit memo items
     * @return int[]
     */
    public function getCreditmemoitemsQuantityGroupedByItemId()
    {
        $quantities = $this->getQuantities($this->getOriginalCreditmemoitems());
        return $quantities;
    }

    /**
     * Get quantities of all credit memo items assigned to the order
     * @return int[]
     */
    public function getOriginalCreditmemoitemsQuantityGroupedByItemId()
    {
        $quantities = $this->getQuantities($this->getOriginalCreditmemoitems());
        return $quantities;
    }

    /**
     * Get non-cash payments total
     * @return float
     */
    public static function getNonCashPaymentCodes()
    {
        $nonCashPaymentCodes = array(
            'Gift Card Total'=>'giftcard_total',
            'Reward Points Total'=>'reward_total',
            'Store Credit Total'=>'storecredit_total'
        );

        return $nonCashPaymentCodes;
    }

    /**
     * Get non-cash payments total on this order
     * @return float
     */
    public function getNonCashPayments()
    {
        $nonCash = 0;
        foreach (self::getNonCashPaymentCodes() as $code) {
            $nonCash += $this->getData($code, 0);
        }

        return $nonCash;
    }

    /**
     * Get non-cash payments total on the original order, alias of getOriginalNonCashPayments
     * @return float
     */
    public function getAllNonCashPayments()
    {
        return $this->getOriginalNonCashPayments();
    }

    /**
     * Get non-cash payments total on the original order (and segregated orders)
     * @return float
     */
    public function getOriginalNonCashPayments()
    {
        $nonCash = 0;
        foreach ($this->getAllOrders() as $order) {
            $nonCash += $order->getNonCashPayments();
        }

        return $nonCash;
    }

    /**
     * Get aggregated grand total of the order
     * @return float
     */
    public function getGrandTotal()
    {
        return $this->getData('grand_total', 0);
    }

    /**
     * Get aggregated grand total of all segregated orders (original grand total)
     * @return float
     */
    public function getOriginalGrandTotal()
    {
        $grandTotal = 0;
        foreach ($this->getAllOrders() as $order) {
            $grandTotal += $order->getGrandTotal();
        }

        return $grandTotal;
    }

    /**
     * Get discount total as a positive number
     * @return float
     */
    public function getDiscountTotal()
    {
        return abs($this->getData('discount_total', 0));
    }

    /**
     * Get total order discount excl. shipping
     * @return float $originalDiscountTotal
     */
    public function getOriginalDiscountTotal()
    {
        $orderitems = $this->getOriginalOrderitems();

        $originalDiscountTotal = 0;
        foreach ($orderitems as $orderitem) {
            $originalDiscountTotal += $orderitem->getTotalDiscount();
        }

        return $originalDiscountTotal;
    }

    /**
     * @return float
     */
    public function getShippingDiscount()
    {
        $shippingDiscount = max(0, $this->getDiscountTotal() - $this->getOriginalDiscountTotal());
        return $shippingDiscount;
    }

    /**
     * Get discounted shipping amount
     * @return float
     */
    public function getDiscountedShippingTotal()
    {
        $discountedShipping = $this->getData('shipping_total', 0) - $this->getShippingDiscount();
        return $discountedShipping;
    }

    /**
     * Get discounted shipping amount of the original order
     * @return float
     */
    public function getOriginalDiscountedShippingTotal()
    {
        $discountedShipping = 0;
        foreach ($this->getAllOrders() as $order) {
            $discountedShipping += $order->getDiscountedShippingTotal();
        }

        return $discountedShipping;
    }

    /**
     * Get order total excl. shipping
     * @return float
     */
    public function getOrderTotal()
    {
        if (!$this->_cachedOrderTotal) {
            foreach ($this->getOrderitems() as $item) {
                $this->_cachedOrderTotal += $item->getDiscountedPrice() * $item->getQuantity();
            }
        }

        return $this->_cachedOrderTotal;
    }

    /**
     * Get order total excl. shipping
     * @return float
     */
    public function getOriginalOrderTotal()
    {
        $orderTotal = 0;
        foreach ($this->getAllOrders() as $order) {
            $orderTotal += $order->getOrderTotal();
        }

        return $orderTotal;
    }

    /**
     * Get order total incl. shipping
     * @return float
     */
    public function getOrderTotalInclShipping()
    {
        $orderTotalInclShipping = $this->getOrderTotal() + $this->getDiscountedShippingTotal();
        return $orderTotalInclShipping;
    }

    /**
     * Get order total incl. shipping
     * @return float
     */
    public function getOriginalOrderTotalInclShipping()
    {
        $orderTotalInclShipping = 0;
        foreach ($this->getAllOrders() as $order) {
            $orderTotalInclShipping += $order->getOrderTotalInclShipping();
        }

        return $orderTotalInclShipping;
    }

    /**
     * @return array|null|string
     */
    public function getPayments()
    {
        return $this->getData('payment_method');
    }

    /**
     * @return array
     */
    public function getPaymentMethods()
    {
        /** @var \Entity\Service\EntityService */
        $entityService = $this->getServiceLocator()->get('entityService');

        return $entityService->getPaymentMethods($this);
    }

    /**
     * @return string $methodsString
     */
    public function getPaymentMethodsString()
    {
        $methodsString = trim(implode(', ', $this->getPaymentMethods()));
        return $methodsString;
    }

    /**
     * @return mixed
     */
    public function getPaymentCcTypes()
    {
        /** @var \Entity\Service\EntityService */
        $entityService = $this->getServiceLocator()->get('entityService');

        return $entityService->getPaymentCcTypes($this);
    }

    /**
     * Get Aggregated Items Refunds
     * @return float
     */
    public function getItemsRefunds()
    {
        $creditmemoitems = $this->getCreditmemoitems();

        $itemsRefundAmount = 0;
        foreach ($creditmemoitems as $item) {
            $itemsRefundAmount += $item->getDiscountedRowTotal();
        }

        return $itemsRefundAmount;
    }

    /**
     * Get aggregated cash refunds
     * @return float
     */
    public function getCashRefunds()
    {
        $creditmemos = $this->getCreditmemos();

        $cashRefundsAmount = 0;
        foreach ($creditmemos as $creditmemo) {
            $cashRefundsAmount += $creditmemo->getCashRefund();
        }

        return $cashRefundsAmount;
    }

    /**
     * Get Aggregated Non Cash Refunds
     * @return float
     */
    public function getNonCashRefunds()
    {
        $creditmemos = $this->getCreditmemos();

        $nonCash = 0;
        foreach ($creditmemos as $creditmemo) {
            $nonCash += $creditmemo->getNonCashRefund();
        }

        return $nonCash;
    }

    /**
     * Get Aggregated Shipping Refunds
     * @return float
     */
    public function getShippingRefunds()
    {
        $creditmemos = $this->getOriginalCreditmemos();

        $shippingRefundAmount = 0;
        foreach ($creditmemos as $creditmemo) {
            $shippingRefundAmount += $creditmemo->getShippingRefund();
        }

        return $shippingRefundAmount;
    }

    /**
     * Get aggregated items refunds of the order and all segregated orders
     * @return float
     */
    public function getOriginalItemsRefunds()
    {
        $itemsRefundsAmount = 0;
        foreach ($this->getAllOrders() as $order) {
            $itemsRefundsAmount += $order->getItemsRefunds();
        }

        return $itemsRefundsAmount;
    }

    /**
     * Alias of getOriginalCashRefunds: Get aggregated cash refunds of the order and all segregated orders
     * @return float
     */
    public function getOriginalCashRefunds()
    {
        $cashRefundsAmount = 0;
        foreach ($this->getAllOrders() as $order) {
            $cashRefundsAmount += $order->getCashRefunds();
        }

        return $cashRefundsAmount;
    }

    /**
     * Get aggregated non cash refunds of the order and all segregated orders
     * @return float
     */
    public function getOriginalNonCashRefunds()
    {
        $nonCash = 0;
        foreach ($this->getAllOrders() as $order) {
            $nonCash += $order->getNonCashRefunds();
        }

        return $nonCash;
    }

    /**
     * Get aggregated shipping refunds of the order and all segregated orders
     * @return float
     */
    public function getOriginalShippingRefunds()
    {
        $shippingRefundAmount = 0;
        foreach ($this->getAllOrders() as $order) {
            $shippingRefundAmount += $order->getShippingRefunds();
        }

        return $shippingRefundAmount;
    }

    /**
     * Alias of getOriginalItemsRefunds: get aggregated items refunds of the order and all segregated orders
     * @return float
     */
    public function getAllItemsRefunds()
    {
        return $this->getOriginalItemsRefunds();
    }

    /**
     * Get aggregated cash refunds of the order and all segregated orders
     * @return float
     */
    public function getAllCashRefunds()
    {
        return $this->getOriginalCashRefunds();
    }

    /**
     * Alias of getOriginalNonCashRefunds: Get aggregated non cash refunds of the order and all segregated orders
     * @return float
     */
    public function getAllNonCashRefunds()
    {
        return $this->getOriginalNonCashRefunds();
    }

    /**
     * Alias of getOriginalShippingRefunds: Get aggregated shipping refunds of the order and all segregated orders
     * @return float
     */
    public function getAllShippingRefunds()
    {
        return $this->getOriginalShippingRefunds();
    }

}
