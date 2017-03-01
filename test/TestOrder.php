<?php
namespace Quickshiftin\Pdf\Invoice;

use Quickshiftin\Pdf\Invoice\Spec\Order as OrderSpec;

class TestOrder implements OrderSpec
{
    private $_iPriceBeforeShipNoTax;
    private $_iCustomerShipCharge;
    private $_iSalesTaxAmount;
    private $_iTotalCost;
    private $_sFullBillingAddress;
    private $_sFullShippngAddress;
    private $_sPaymentMethod;
    private $_sShipmentHandle;
    private $_aOrderItems = [];
    private $_iClientAppId;
    private $_sSaleDate;

    public function setPriceBeforeShippingNoTax($Price)
    {
        $this->_iPriceBeforeShipNoTax = $Price;
    }
    public function setCustomerShipCharge($Price)
    {
        $this->_iCustomerShipCharge = $Price;
    }
    public function setSalesTaxAmount($Price)
    {
        $this->_iSalesTaxAmount = $Price;
    }
    public function setTotalCost($Price)
    {
        $this->_iTotalCost = $Price;
    }
    public function setFullBillingAddress($sAddress)
    {
        $this->_sFullBillingAddress = $sAddress;
    }
    public function setPaymentMethod($PaymentMethod)
    {
        $this->_sPaymentMethod = $PaymentMethod;
    }
    public function setFullShippingAddress($sAddress)
    {
        $this->_sFullShippngAddress = $sAddress;
    }
    public function setShipEcomHandle($Handle)
    {
        $this->_sShipmentHandle = $Handle;
    }
    public function setOrderItems($aOrderItems)
    {
        $this->_aOrderItems = $aOrderItems;
    }
    public function setClientAppId($id)
    {
        $this->_iClientAppId = $id;
    }
    public function setSaleDate($Date)
    {
        $this->_sSaleDate = $Date;
    }
    public function getPriceBeforeShippingNoTax()
    {
        return $this->_iPriceBeforeShipNoTax;
    }
    public function getCustomerShipCharge()
    {
        return $this->_iCustomerShipCharge;
    }
    public function getSalesTaxAmount()
    {
        return $this->_iSalesTaxAmount;
    }
    public function getTotalCost()
    {
        return $this->_iTotalCost;
    }
    public function getFullBillingAddress()
    {
        return $this->_sFullBillingAddress;
    }
    public function getPaymentMethod()
    {
        return $this->_sPaymentMethod;
    }
    public function getFullShippingAddress()
    {
        return $this->_sFullShippngAddress;
    }
    public function getShipEcomHandle()
    {
        return $this->_sShipmentHandle;
    }
    public function getOrderItems()
    {
        return $this->_aOrderItems;
    }

    public function addOrderItem(TestOrderItem $oOrderItem)
    {
        $this->_aOrderItems[] = $oOrderItem;
    }
    public function addOrderItems(array $aOrderItems)
    {
      $this->_aOrderItems = $aOrderItems;
    }

    public function getClientAppOrderId()
    {
        return $this->_iClientAppId;
    }
    public function getSaleDate()
    {
        return $this->_sSaleDate;
    }
}
