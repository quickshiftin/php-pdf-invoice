<?php
$oInvoicePdf = new Softwear\ECommerce\Pdf\PackingSlip();

// Configure fonts
$oInvoicePdf->setRegularFontPath('./assets/Arial.ttf');
$oInvoicePdf->setBoldFontPath('./assets/Arial Bold.ttf');
$oInvoicePdf->setItalicFontPath('./assets/Arial Italic.ttf');

// Configure logo
$oInvoicePdf->setLogoPath('./assets/fake-log.jpg');

// TODO Configure colors

// Build the PDF

// TODO Define Order & Order items interface
//      Create contrived order & order items for testing
$oPdf = $oInvoicePdf->getPdf($oOrder);

// Render the PDF and save it for viewing
file_put_contents('./test-invoice.pdf', $oPdf->render());
