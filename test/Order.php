<?php
namespace Quickshiftin\Pdf\Invoice;

use Quickshiftin\Pdf\Invoice\Spec\Order as OrderSpec;

class TestOrder implements OrderSpec
{
    // todo add private properties

    // todo add setters

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
    public function getOrderItems();
}
