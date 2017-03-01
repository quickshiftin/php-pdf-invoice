<?php
namespace Quickshiftin\Pdf\Invoice\Spec;

use Quickshiftin\Pdf\Invoice\TestOrderItem;

interface Order
{
    public function addOrderItem(TestOrderItem $oOrderItem);
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
