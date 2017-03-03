<?php
namespace Quickshiftin\Pdf\Invoice;
use Quickshiftin\Pdf\Invoice\Spec\OrderItem;

class TestOrderItem implements OrderItem
{
    private $_sName;
    private $_sSwSku;
    private $_iQuantity;
    private $_iPricePerUnit;
    private $_iPrice;
    private $_iSalesTaxAmount;


    public function setName($Name)
    {
        $this->_sName = $Name;
    }
    public function setSwSku($sku)
    {
        $this->_sSwSku = $sku;
    }
    public function setQuantity($iQuantity)
    {
        $this->_iQuantity = $iQuantity;
    }
    public function setPricePerUnit($iPriceUnit)
    {
        $this->_iPricePerUnit = $iPriceUnit;
    }
    public function setPrice($iPrice)
    {
        $this->_iPrice = $iPrice;
    }
    public function setSalesTaxAmount($saleAmount)
    {
        $this->_iSalesTaxAmount = $saleAmount;
    }

    public function getName()
    {
        return $this->_sName;
    }
    public function getSwSku()
    {
        return $this->_sSwSku;
    }
    public function getQuantity()
    {
        return $this->_iQuantity;
    }
    public function getPricePerUnit()
    {
        return $this->_iPricePerUnit;
    }
    public function getPrice()
    {
        return $this->_iPrice;
    }
    public function getSalesTaxAmount()
    {
        return $this->_iSalesTaxAmount;
    }

}