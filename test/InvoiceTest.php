<?php
namespace Quickshiftin\Pdf\Invoice;

class InvoiceTest extends \PHPUnit_Framework_TestCase
{
    private
        $_oOrderMock;

    public function setup()
    {
        $this->_getOrderMock();
    }

    public function testSomething()
    {
        $oInvoicePdf = new Invoice();

        // Configure fonts
        $oInvoicePdf->setRegularFontPath(__DIR__ . '/../assets/Arial.ttf');
        $oInvoicePdf->setBoldFontPath(__DIR__ . '/../assets/Arial Bold.ttf');
        $oInvoicePdf->setItalicFontPath(__DIR__ . '/../assets/Arial Italic.ttf');

        // Set Color
        $oInvoicePdf->setTitleBgFillColor(new \Zend_Pdf_Color_Html('gold'));
        $oInvoicePdf->setTitleFontColor(new \Zend_Pdf_Color_Html('gray'));
        $oInvoicePdf->setBodyBgFillColor(new \Zend_Pdf_Color_Html('turquoise'));
        $oInvoicePdf->setBodyFontColor(new \Zend_Pdf_Color_Html('black'));

        // Configure logo
        $oInvoicePdf->setLogoPath(__DIR__ . '/../assets/fake-log.jpg');

        // Build the PDF
        $oPdf = $oInvoicePdf->getPdf($this->_oOrderMock);
        
        $pdf = $oPdf->render();

        $this->assertTrue(!empty($pdf));
    }

    private function _getOrderMock()
    {
        $this->_oOrderMock = $this
            ->getMockBuilder('Quickshiftin\\Pdf\\Invoice\\Spec\\Order')
            ->getMock();

        $this->_setOrderExp('getClientAppOrderId', 1);
        $this->_setOrderExp('getSaleDate', '01-03-2016');
        $this->_setOrderExp('getCustomerShipCharge', 5, $this->any());
        $this->_setOrderExp('getFullBillingAddress', '557 Alton Way Unit B');
        $this->_setOrderExp('getFullShippingAddress', '557 Alton Way Unit B');
        $this->_setOrderExp('getPaymentMethod', 'Credit Card');
        $this->_setOrderExp('getPriceBeforeShippingNoTax', 50, $this->any());
        $this->_setOrderExp('getSalesTaxAmount', 5);
        $this->_setOrderExp('getShipEcomHandle', 'UPS');
        $this->_setOrderExp('getTotalCost', 60);

        $oOrderItem1 = new TestOrderItem();
        $oOrderItem1->setName('Ali Tshirt');
        $oOrderItem1->setSalesTaxAmount(0);
        $oOrderItem1->setPrice(25);
        $oOrderItem1->setPricePerUnit(25);
        $oOrderItem1->setSwSku('001');
        $oOrderItem1->setQuantity(1);

        $oOrderItem2 = new TestOrderItem();
        $oOrderItem2->setName('Prince Tshirt');
        $oOrderItem2->setSalesTaxAmount(0);
        $oOrderItem2->setPrice(25);
        $oOrderItem2->setPricePerUnit(25);
        $oOrderItem2->setSwSku('002');
        $oOrderItem2->setQuantity(1);

        $this
            ->_oOrderMock
            ->expects($this->once())
            ->method('getOrderItems')
            ->willReturn([$oOrderItem1, $oOrderItem2]);
    }

    private function _setOrderExp($sMethod, $mReturn, $count=null)
    {
        if($count === null) {
            $count = $this->once();
        }

        $this
            ->_oOrderMock
            ->expects($count)
            ->method($sMethod)
            ->willReturn($mReturn);
    }
}
