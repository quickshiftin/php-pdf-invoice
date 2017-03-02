<?php

require './vendor/autoload.php';

$oInvoicePdf = new Quickshiftin\Pdf\Invoice\Invoice();

// Configure fonts
$oInvoicePdf->setRegularFontPath('./assets/Arial.ttf');
$oInvoicePdf->setBoldFontPath('./assets/Arial Bold.ttf');
$oInvoicePdf->setItalicFontPath('./assets/Arial Italic.ttf');

// Set Color
$oInvoicePdf->setTitleBgFillColor(new \Zend_Pdf_Color_Html('gold'));
$oInvoicePdf->setTitleFontColor(new \Zend_Pdf_Color_Html('gray'));
$oInvoicePdf->setBodyBgFillColor(new \Zend_Pdf_Color_Html('turquoise'));
$oInvoicePdf->setBodyFontColor(new \Zend_Pdf_Color_Html('black'));

// Configure logo
$oInvoicePdf->setLogoPath('./assets/fake-log.jpg');

$oOrderItem1 = new \Quickshiftin\Pdf\Invoice\TestOrderItem();
$oOrderItem1->setName('Ali Tshirt');
$oOrderItem1->setSalesTaxAmount(0);
$oOrderItem1->setPrice(25);
$oOrderItem1->setPricePerUnit(25);
$oOrderItem1->setSwSku('001');
$oOrderItem1->setQuantity(1);

$oOrderItem2 = new \Quickshiftin\Pdf\Invoice\TestOrderItem();
$oOrderItem2->setName('Prince Tshirt');
$oOrderItem2->setSalesTaxAmount(0);
$oOrderItem2->setPrice(25);
$oOrderItem2->setPricePerUnit(25);
$oOrderItem2->setSwSku('002');
$oOrderItem2->setQuantity(1);

$oOrder = new \Quickshiftin\Pdf\Invoice\TestOrder();
$oOrder->setClientAppId(1);
$oOrder->setSaleDate('01-03-2016');
$oOrder->setCustomerShipCharge(5);
$oOrder->setFullBillingAddress('557 Alton Way Unit B');
$oOrder->setFullShippingAddress('557 Alton Way Unit B');
$oOrder->setPaymentMethod('Credit Card');
$oOrder->setPriceBeforeShippingNoTax(50);
$oOrder->setSalesTaxAmount(5);
$oOrder->setShipEcomHandle('UPS');
$oOrder->setTotalCost(60);
$oOrder->addOrderItem($oOrderItem1);
$oOrder->addOrderItem($oOrderItem2);

// TODO Configure colors

// Build the PDF

// TODO Define Order & Order items interface
//      Create contrived order & order items for testing
$oPdf = $oInvoicePdf->getPdf($oOrder);

// Render the PDF and save it for viewing
file_put_contents('./test-invoice.pdf', $oPdf->render());
