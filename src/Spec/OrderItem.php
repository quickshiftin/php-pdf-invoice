<?php
namespace Quickshiftin\Pdf\Invoice\Spec;

interface OrderItem
{
    public function getName();
    public function getSwSku();
    public function getQuantity();
    public function getPricePerUnit();
    public function getPrice();
    public function getSalesTaxAmount();
}