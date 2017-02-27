<?php
namespace Quickshiftin\Pdf\Invoice\Spec;

interface Order
{
    public function getPriceBeforeShippingNoTax();
    public function getCustomerShipCharge();
    public function getPriceBeforeShippingNotax();
    public function getCustomerShipCharge();
    public function getSalesTaxAmount();
    public function getTotalCost();
    public function getFullBillingAddress();
    public function getPaymentMethod();
    public function getFullShippingAddress();
    public function getShipEcomHandle();
    public function getCustomerShipCharge();
}
