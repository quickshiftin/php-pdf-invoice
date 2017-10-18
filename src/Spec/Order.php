<?php
namespace Quickshiftin\Pdf\Invoice\Spec;

use Quickshiftin\Pdf\Invoice\Spec\OrderItem;

interface Order
{
    /**
     * Get the sub-total, eclusive of shipping and tax.
     * @return float
     */
    public function getPriceBeforeShippingNoTax();

    /**
     * Get the shipping charge if any
     * @return float
     */
    public function getCustomerShipCharge();

    /**
     * Get the sales tax amount, eg .08 for 8%
     * @return float
     */
    public function getSalesTaxAmount();

    /**
     * Get the total cost including shipping and tax
     * @return float
     */
    public function getTotalCost();

    /**
     * Get the full billing address for the customer
     * @return string
     */
    public function getFullBillingAddress();

    /**
     * Get the payment method, EG COD, Visa, PayPal etc
     * @return string
     */
    public function getPaymentMethod();

    /**
     * Get the full shipping address for the order
     * @return string
     */
    public function getFullShippingAddress();

    /**
     * Get the name of the shipping method, EG UPS, FedEx, etc
     * @return string
     */
    public function getShippingMethodName();

    /**
     * Get an array of OrderItem objects
     * @note This should return an array of instances of a class where you implement Quickshiftin\Pdf\Invoice\Spec\OrderItem
     * @return array
     */
    public function getOrderItems();

    /**
     * Get the id of the order
     * @return int|string
     */
    public function getOrderId();

    /**
     * Get the date of the sale
     * @return DateTime
     */
    public function getSaleDate($sFormat);

    /**
     * Optional note to go at the bottom of the PDF.
     * @return string
     */
    public function getOrderNote();
}
