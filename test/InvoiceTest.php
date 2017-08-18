<?php
namespace Quickshiftin\Pdf\Invoice;

class InvoiceTest extends \PHPUnit_Framework_TestCase
{
    private
        $_oOrderMock,
        $_oFactoryMock;

    public function setup()
    {
        $this->_getFactoryMock();
        $this->_getOrderMock();
    }

    public function testSomething()
    {
        $oInvoicePdf = new Invoice($this->_oFactoryMock);

        // Configure fonts
        $oInvoicePdf->setRegularFontPath(__DIR__ . '/../assets/Arial.ttf');
        $oInvoicePdf->setBoldFontPath(__DIR__ . '/../assets/Arial Bold.ttf');
        $oInvoicePdf->setItalicFontPath(__DIR__ . '/../assets/Arial Italic.ttf');

        // Set Color
        $red    = '#d53f27';
        $yellow = '#e8e653';

        // Title section of invoice
        // Background color for title section of invoice, the default is white
        $oInvoicePdf->setTitleBgFillColor(new \Zend_Pdf_Color_Html($yellow));
        $oInvoicePdf->setTitleFontColor(new \Zend_Pdf_Color_Html('black'));

        // Header sections of invoice
        $oInvoicePdf->setHeaderBgFillColor(new \Zend_Pdf_Color_Html($red));
        $oInvoicePdf->setBodyHeaderFontColor(new \Zend_Pdf_Color_Html('white'));

        // Body section of invoice
        $oInvoicePdf->setBodyFontColor(new \Zend_Pdf_Color_Html('black'));

        // Line color of invoice
        $oInvoicePdf->setLineColor(new \Zend_Pdf_Color_Grayscale(0));

        // Configure logo
        $oInvoicePdf->setLogoPath(__DIR__ . '/../assets/fake-logo.jpg');

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

        $this->_setExp($this->_oOrderMock,'getOrderId', 1);
        $this->_setExp($this->_oOrderMock,'getSaleDate', '01-03-2016');
        $this->_setExp($this->_oOrderMock,'getCustomerShipCharge', 5, $this->any());
        $this->_setExp($this->_oOrderMock,'getFullBillingAddress', '557 Alton Way Unit B');
        $this->_setExp($this->_oOrderMock,'getFullShippingAddress', '557 Alton Way Unit B');
        $this->_setExp($this->_oOrderMock,'getPaymentMethod', 'Credit Card');
        $this->_setExp($this->_oOrderMock,'getPriceBeforeShippingNoTax', 50, $this->any());
        $this->_setExp($this->_oOrderMock,'getSalesTaxAmount', 5);
        $this->_setExp($this->_oOrderMock,'getShippingMethodName', 'UPS');
        $this->_setExp($this->_oOrderMock,'getTotalCost', 60);

        $oMockOrderItem1 = $this->_getOrderItemMock(
            'Ali Tshirt', 0, 25, 25, '001', 1);

        $oMockOrderItem2 = $this->_getOrderItemMock(
            'Prince Tshirt', 0, 30, 30, '002', 1);
        $this
            ->_oOrderMock
            ->expects($this->once())
            ->method('getOrderItems')
            ->willReturn([$oMockOrderItem1, $oMockOrderItem2]);
    }

    private function _setExp($oMock, $sMethod, $mReturn, $count=null)
    {
        if($count === null) {
            $count = $this->once();
        }

        $oMock
            ->expects($count)
            ->method($sMethod)
            ->willReturn($mReturn);
    }

    private function _getOrderItemMock($sName, $fSalesTax, $fPrice, $fPricePerUnit, $sSku, $iQuantity)
    {
        $oMockOrderItem = $this
            ->getMockBuilder('Quickshiftin\\Pdf\\Invoice\\Spec\\OrderItem')
            ->getMock();
        $this->_setExp($oMockOrderItem, 'getName',$sName );
        $this->_setExp($oMockOrderItem, 'getSalesTaxAmount', $fSalesTax);
        $this->_setExp($oMockOrderItem, 'getPrice', $fPrice);
        $this->_setExp($oMockOrderItem, 'getPricePerUnit', $fPrice);
        $this->_setExp($oMockOrderItem, 'getSku', $sSku);
        $this->_setExp($oMockOrderItem, 'getQuantity', $iQuantity);

        return $oMockOrderItem;

    }

    private function _getFactoryMock()
    {
        $this->_oFactoryMock = $this
            ->getMockBuilder('Quickshiftin\\Pdf\\Invoice\\Factory')
            ->setMethods(['createPdf'])
            ->getMock();

        $oPdfMock = $this
            ->getMockBuilder('Zend_Pdf')
            ->setMethods(['fakMethod'])
            ->getMock();

        $this
            ->_oFactoryMock
            ->expects($this->once())
            ->method('createPdf')
            ->willReturn($oPdfMock);
    }
}
