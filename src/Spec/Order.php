<?php
namespace Quickshiftin\Pdf\Invoice\Spec;

use Quickshiftin\Pdf\Invoice\Spec\OrderItem;

interface Order
{
    public function addOrderItem(OrderItem $oOrderItem);
    public function addOrderItems(array $aOrderItems);
    public function getPriceBeforeShippingNoTax();
    public function getCustomerShipCharge();
    public function getSalesTaxAmount();
    public function getTotalCost();
    public function getFullBillingAddress();
    public function getPaymentMethod();
    public function getFullShippingAddress();
    public function getShipEcomHandle();
    public function getOrderItems();
    public function getClientAppOrderId();
    public function getSaleDate();
}
