<?php
namespace Quickshiftin\Pdf\Invoice\Spec;

interface OrderItem
{
    /**
     * The name or description of the product
     * @return string
     */
    public function getName();

    /**
     * The 'SKU' or unique identifier for your product
     * @return string
     */
    public function getSku();

    /**
     * The quantity sold
     * @return int
     */
    public function getQuantity();

    /**
     * The price per unit
     * @return float
     */
    public function getPricePerUnit();

    /**
     * The price including tax
     * @return flaot
     */
    public function getPrice();

    /**
     * The sales tax amount in dollars
     * @return float
     */
    public function getSalesTaxAmount();
}
