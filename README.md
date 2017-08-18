# PHP PDF Invoice
This project uses a PDF invoice generator extracted from Magento for general use via Composer. You can easily integrate your existing domain model and start generating PDF invoices that look like this:

![Example Invoice PDF](http://i289.photobucket.com/albums/ll238/quickshiftin/php-pdf-invoice-example_zpswiumm9tg.png)

## Features
* Generate PDF invoices with PHP
* Composer integration
* Change colors, fonts, line color and provide custom logo of the PDF
* Automatically create multiple pages based on number of line items

## Install via Composer

`./composer.phar require quickshiftin/php-pdf-invoice`

## Usage
### Integration boilerplate - connecting your existing domain model to the generator
To integrate your existing application's orders, simply provide two classes that `implement` `Quickshiftin\Pdf\Invoice\Spec\Order` and `Quickshiftin\Pdf\Invoice\Spec\OrderItem`

#### OrderItem interface implementation
```php
namespace MyApp;
use Quickshiftin\Pdf\Invoice\Spec\OrderItem;

// Implement the Order Item methods below
class MyOrderItem interface implements OrderItem
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
```
#### Order interface implementation
```php
use Quickshiftin\Pdf\Invoice\Spec\Order;

// Implement the order methods below
class MyOrder implements Order
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
    public function getSaleDate();
}
```

### Implementation suggestion
Since these are `interface`s, you can create a new class that wraps your existing OrderItem objects. If there are no name collisions you could also consider implementing directly on your existing OrderItem class.


### Building & Styling your Invoice PDFs
This system uses [`Zend_Pdf`](https://framework.zend.com/manual/1.10/en/zend.pdf.html) (from ZF1) under the hood. The package provides `Quickshiftin\Pdf\Invoice\Factory` which is a wrapper for instantiating classes from `Zend_Pdf`. You'll use these objects to customize the appearance of your PDFs.

We also assume you have an instance of an order object which implements `Quickshiftin\Pdf\Invoice\Spec\Order` as described above that is stored in a variable called `$myOrder`.

```php
use Quickshiftin\Pdf\Invoice as PdfInvoice;
use Quickshiftin\Pdf\Invoice\Factory as InvoiceFactory;

$oInvoiceFactory = new InvoiceFactory();
$oInvoicePdf     = new Invoice();

// Configure fonts - just put ttf font files somewhere your project can access them
$oInvoicePdf->setRegularFontPath(__DIR__ . '/../assets/Arial.ttf');
$oInvoicePdf->setBoldFontPath(__DIR__ . '/../assets/Arial Bold.ttf');
$oInvoicePdf->setItalicFontPath(__DIR__ . '/../assets/Arial Italic.ttf');

// Set Colors
$red    = '#d53f27';
$yellow = '#e8e653';

// Title section of invoice
// Background color for title section of invoice, the default is white
$oInvoicePdf->setTitleBgFillColor($oInvoiceFactory->createColorHtml($yellow));
$oInvoicePdf->setTitleFontColor($oInvoiceFactory->createColorHtml('black'));

// Header sections of invoice
$oInvoicePdf->setHeaderBgFillColor($oInvoiceFactory->createColorHtml($red));
$oInvoicePdf->setBodyHeaderFontColor($oInvoiceFactory->createColorHtml('white'));

// Body section of invoice
$oInvoicePdf->setBodyFontColor($oInvoiceFactory->createColorHtml('black'));

// Line color of invoice
$oInvoicePdf->setLineColor($oInvoiceFactory->createColorGrayscale(0));

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
