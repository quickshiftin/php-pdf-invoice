# PHP PDF Ivoice
This project uses a PDF invoice generator extracted from Magento for general use via Composer. You can easily integrate your existing domain model and start generating PDF invoices.

![Example Invoice PDF](http://i289.photobucket.com/albums/ll238/quickshiftin/php-pdf-invoice-example_zpsbsjomzsr.png)

## Features
* Generate PDF invoices with PHP
* Composer integration
* Change colors, fonts and provide custom logo of the PDF

## Install via Composer

`./composer.phar require quickshiftin/php-pdf-invoice`

## Usage
### Integration boilerplate - connecting your existing domain model to the generator
To integrate your existing application's orders, simply provide two classes that `implement` `Quickshiftin\Pdf\Invoice\Spec\Order` and `Quickshiftin\Pdf\Invoice\Spec\OrderItem`

### Implementation suggestion
Since these are `interface`s, you can create a new class that wraps your existing OrderItem objects. If there are no name collisions you could also consider implementing directly on your existing OrderItem class.

#### OrderItem interface
```php
namespace MyApp;
use Quickshiftin\Pdf\Invoice\Spec\OrderItem;

// Implement the Order Item methods below
class MyOrderItem interface OrderItem
{
    public function getName();
    public function getSku();
    public function getQuantity();
    public function getPricePerUnit();
    public function getPrice();
    public function getSalesTaxAmount();
}
```
#### Order interface
```php
use Quickshiftin\Pdf\Invoice\Spec\OrderItem;

// Implement the order methods below
interface Order
{
    public function addOrderItem(OrderItem $oOrderItem);
    public function addOrderItems(array $aOrderItems);
    public function getPriceBeforeShippingNoTax();
    public function getCustomerShipCharge();
    public function getSalesTaxAmount();
    public function getTotalCost();
    public function getFullBillingAddress();
    public function getPaymentMethod();
    public function getFullShippingAddress();
    public function getShipEcomHandle();
    public function getOrderItems();
    public function getClientAppOrderId();
    public function getSaleDate();
}
```

### Building PDFs
This system uses [`Zend_Pdf`](https://framework.zend.com/manual/1.10/en/zend.pdf.html) (from ZF1) under the hood. The package provides `Quickshiftin\Pdf\Invoice\Factory` which is a wrapper for instantiating classes from `Zend_Pdf`. You'll use these objects to customize the appearance of your PDFs.

We also assume you have an instance of an order object which implements `Quickshiftin\Pdf\Invoice\Spec\Order` as described above that is stored in a variable called `$myOrder`.

```php
use Quickshiftin\Pdf\Invoice as PdfInvoice;
use Quickshiftin\Pdf\Invoice\Factory as InvoiceFactory;

$oInvoiceFactory = new InvoiceFactory();
$oInvoicePdf = new Invoice($this->_oFactoryMock);

// Configure fonts
$oInvoicePdf->setRegularFontPath(__DIR__ . '/../assets/Arial.ttf');
$oInvoicePdf->setBoldFontPath(__DIR__ . '/../assets/Arial Bold.ttf');
$oInvoicePdf->setItalicFontPath(__DIR__ . '/../assets/Arial Italic.ttf');

// Set Color
$oInvoicePdf->setTitleBgFillColor($oInvoiceFactory->createColorHtml('gold'));
$oInvoicePdf->setTitleFontColor($oInvoiceFactory->createColorHtml('gray'));
$oInvoicePdf->setBodyBgFillColor($oInvoiceFactory->createColorHtml('turquoise'));
$oInvoicePdf->setBodyFontColor($oInvoiceFactory->createColorHtml('black'));

// Configure logo
$oInvoicePdf->setLogoPath(__DIR__ . '/../assets/fake-logo.jpg');

// Build the PDF
// $oPdf is an instance of Zend_Pdf
$oPdf = $oInvoicePdf->getPdf($myOrder);

// A string rendition, you could echo this to the browser with headers to implement a download
$pdf = $oPdf->render();

// You can also simply save it to a file
file_put_contents('/tmp/test.pdf', $pdf);
```

## Notes
You can look at the test directory for usage insights. Issues and PRs welcome!
