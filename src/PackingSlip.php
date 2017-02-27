<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Quickshiftin\Pdf;

use Zend_Pdf;
use Zend_Pdf_Font;
use Zend_Pdf_Page;
use Zend_Pdf_Color_GrayScale;
use Zend_Pdf_Color_Rgb;

/**
 * PDF Invoice generator
 * Cannibalized from Magento1
 *
 * TODO
 * - Interfaces for order and order item so it's fully portable
 * - Break out into standalone github project
 * - Add to pacakgist
 * - Make sure it works for multiple page documents
 * - Support to pass in various things
 *   . Logo image
 *   . Colors for font, background etc
 *   . Font configuration
 */
class Invoice
{
    /**
     * Y coordinate
     *
     * @var int
     */
    public $y;

    /**
     * Item renderers with render type key
     *
     * model    => the model name
     * renderer => the renderer model
     *
     * @var array
     */
    protected $_renderers = [];

    /**
     * Predefined constants
     */
    const XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID    = 'sales_pdf/invoice/put_order_id';
    const XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID   = 'sales_pdf/shipment/put_order_id';
    const XML_PATH_SALES_PDF_CREDITMEMO_PUT_ORDER_ID = 'sales_pdf/creditmemo/put_order_id';

    /**
     * Zend PDF object
     *
     * @var Zend_Pdf
     */
    protected $_pdf;

    /**
     * Default total model
     *
     * @var string
     */
    protected $_defaultTotalModel = 'sales/order_pdf_total_default';

    protected $_oStringHelper;

    private
        $_oSwOrder,
        $_sLogoPath,
        $_aFontPaths = [];

    public function __construct()
    {
        $this->_oStringHelper = new StringHelper();
    }

    public function setRegularFontPath($sPath) { $this->_setFontPath('regular', $sPath); }
    public function setBoldFontPath($sPath)    { $this->_setFontPath('bold', $sPath); }
    public function setItalicFontPath($sPath)  { $this->_setFontPath('italic', $sPath); }

    private function _setFontPath($sType, $sPath)
    {
        $this->_aFontPaths[$sType] = $sPath;
    }

    public function setLogoPath($sLogoPath)
    {
        $this->_sLogoPath = $sLogoPath;
    }

    /**
     * Returns the total width in points of the string using the specified font and
     * size.
     *
     * This is not the most efficient way to perform this calculation. I'm
     * concentrating optimization efforts on the upcoming layout manager class.
     * Similar calculations exist inside the layout manager class, but widths are
     * generally calculated only after determining line fragments.
     *
     * @param  string $string
     * @param  Zend_Pdf_Resource_Font $font
     * @param  float $fontSize Font size in points
     * @return float
     */
    private function widthForStringUsingFontSize($string, $font, $fontSize)
    {
        $drawingString = '"libiconv"' == ICONV_IMPL ?
            iconv('UTF-8', 'UTF-16BE//IGNORE', $string) :
            @iconv('UTF-8', 'UTF-16BE', $string);

        $characters = array();
        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
        }
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;
        return $stringWidth;

    }

    /**
     * Calculate coordinates to draw something in a column aligned to the right
     *
     * @param  string $string
     * @param  int $x
     * @param  int $columnWidth
     * @param  Zend_Pdf_Resource_Font $font
     * @param  int $fontSize
     * @param  int $padding
     * @return int
     */
    private function getAlignRight(
        $string, $x, $columnWidth, \Zend_Pdf_Resource_Font $font, $fontSize, $padding = 5
    ) {
        $width = $this->widthForStringUsingFontSize($string, $font, $fontSize);
        return $x + $columnWidth - $width - $padding;
    }

    /**
     * Calculate coordinates to draw something in a column aligned to the center
     *
     * @param  string $string
     * @param  int $x
     * @param  int $columnWidth
     * @param  Zend_Pdf_Resource_Font $font
     * @param  int $fontSize
     * @return int
     */
    private function getAlignCenter($string, $x, $columnWidth, \Zend_Pdf_Resource_Font $font, $fontSize)
    {
        $width = $this->widthForStringUsingFontSize($string, $font, $fontSize);
        return $x + round(($columnWidth - $width) / 2);
    }

    /**
     * Insert logo to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param null $store
     */
    private function insertLogo($page)
    {
        // Bail if we have no logo path
        // @todo Consider additional validation
        if(!file_exists($this->_sLogoPath)) {
            return;
        }

        $this->y = $this->y ? $this->y : 815;

        $image       = \Zend_Pdf_Image::imageWithPath($this->_sLogoPath);
        $top         = 830; //top border of the page
        $widthLimit  = 270; //half of the page width
        $heightLimit = 270; //assuming the image is not a "skyscraper"
        $width       = $image->getPixelWidth();
        $height      = $image->getPixelHeight();

        //preserving aspect ratio (proportions)
        $ratio = $width / $height;
        if ($ratio > 1 && $width > $widthLimit) {
            $width  = $widthLimit;
            $height = $width / $ratio;
        } elseif ($ratio < 1 && $height > $heightLimit) {
            $height = $heightLimit;
            $width  = $height * $ratio;
        } elseif ($ratio == 1 && $height > $heightLimit) {
            $height = $heightLimit;
            $width  = $widthLimit;
        }

        $y1 = $top - $height;
        $y2 = $top;
        $x1 = 25;
        $x2 = $x1 + $width;

        //coordinates after transformation are rounded by Zend
        $page->drawImage($image, $x1, $y1, $x2, $y2);

        $this->y = $y1 - 10;
    }

    /**
     * Calculate address height
     *
     * @param  array $address
     * @return int Height
     */
    private function _calcAddressHeight($address)
    {
        $address = explode("\n", $address);
        $y = 0;
        foreach ($address as $value){
            if ($value !== '') {
                $text = array();
                foreach ($this->_oStringHelper->str_split($value, 55, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $y += 15;
                }
            }
        }
        return $y;
    }

    private function _buildTotalsArrayPart($sText, $iFeed)
    {
        return [
            'text'  => $sText,  'font_size' => 10,
            'align' => 'right', 'feed'      => $iFeed , 'font'=> 'bold'
        ];
    }

    private function _buildTotalsArrayPair($sLabel, $mValue)
    {
        return [
            $this->_buildTotalsArrayPart($sLabel . ':', 475),
            $this->_buildTotalsArrayPart($this->_renderPrice($mValue), 565),
        ];
    }

    private function _buildTotalsArray()
    {
        return [
            $this->_buildTotalsArrayPair('Subtotal', $this->_oSwOrder->getPriceBeforeShippingNoTax()),
            $this->_buildTotalsArrayPair('Shipping & Handling', $this->_oSwOrder->getCustomerShipCharge()),
            $this->_buildTotalsArrayPair(
                'Grand Total (Excl. Tax)',
                $this->_oSwOrder->getPriceBeforeShippingNotax() + $this->_oSwOrder->getCustomerShipCharge()),
            $this->_buildTotalsArrayPair('Tax', $this->_oSwOrder->getSalesTaxAmount()),
            $this->_buildTotalsArrayPair('Grand Total (Incl. Tax)', $this->_oSwOrder->getTotalCost())
        ];
    }

    /**
     * Insert totals to pdf page
     *
     * @param  Zend_Pdf_Page $page
     * @return Zend_Pdf_Page
     */
    private function insertTotals(\Zend_Pdf_Page $oPage, \SwOrder $oOrder)
    {
        $lineBlock = [
            'lines'  => $this->_buildTotalsArray(),
            'height' => 15
        ];

        $this->y -= 20;

        $oPage = $this->_drawLineBlocks($oPage, [$lineBlock]);

        return $oPage;
    }

    /**
     * Insert order to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $obj
     * @param bool $bPutOrderId
     */
    private function insertOrder(\Zend_Pdf_Page $oPage)
    {
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;

        $oPage->setFillColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $oPage->setLineColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $oPage->drawRectangle(25, $top, 570, $top - 40);
        $oPage->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $this->_setFontRegular($oPage, 10);
        
        $oPage->drawText('Order # ' . $this->_oSwOrder->getClientAppOrderId(), 35, ($top -= 15), 'UTF-8');

        $oPage->drawText(
            'Order Date: ' . $this->_oSwOrder->getSaleDate('M jS Y g:i a'), 
            35, ($top -= 15), 'UTF-8'
        );

        $top -= 10;
        $oPage->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $oPage->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $oPage->setLineWidth(0.5);
        $oPage->drawRectangle(25, $top, 275, ($top - 25));
        $oPage->drawRectangle(275, $top, 570, ($top - 25));

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_oSwOrder->getFullBillingAddress();

        /* Payment */
        // Just show the payment method's title on the PDF, that should be plenty...
        $payment = [$this->_oSwOrder->getPaymentMethod()];

        /* Shipping Address and Method */
        /* Shipping Address */
        $shippingAddress = $this->_oSwOrder->getFullShippingAddress();
        $shippingMethod  = $this->_oSwOrder->getShipEcomHandle();

        $oPage->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($oPage, 12);
        $oPage->drawText('Sold to:', 35, ($top - 15), 'UTF-8');

        $oPage->drawText('Ship to:', 285, ($top - 15), 'UTF-8');

        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));

        $oPage->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $oPage->drawRectangle(25, ($top - 25), 570, $top - 33 - $addressesHeight);
        $oPage->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($oPage, 10);
        $this->y = $top - 40;
        $addressesStartY = $this->y;

        $billingAddress = explode("\n", $billingAddress);
        foreach ($billingAddress as $value){
            if ($value !== '') {
                $text = array();
                foreach ($this->_oStringHelper->str_split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $oPage->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $addressesEndY = $this->y;

        $this->y = $addressesStartY;

        $shippingAddress = explode("\n", $shippingAddress);
        foreach ($shippingAddress as $value){
            if ($value!=='') {
                $text = array();
                foreach ($this->_oStringHelper->str_split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $oPage->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $addressesEndY = min($addressesEndY, $this->y);
        $this->y = $addressesEndY;

        $oPage->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $oPage->setLineWidth(0.5);
        $oPage->drawRectangle(25, $this->y, 275, $this->y-25);
        $oPage->drawRectangle(275, $this->y, 570, $this->y-25);

        $this->y -= 15;
        $this->_setFontBold($oPage, 12);
        $oPage->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $oPage->drawText('Payment Method', 35, $this->y, 'UTF-8');
        $oPage->drawText('Shipping Method:', 285, $this->y , 'UTF-8');

        $this->y -=10;
        $oPage->setFillColor(new Zend_Pdf_Color_GrayScale(1));

        $this->_setFontRegular($oPage, 10);
        $oPage->setFillColor(new Zend_Pdf_Color_GrayScale(0));

        $paymentLeft = 35;
        $yPayments   = $this->y - 15;


        foreach ($payment as $value){
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach ($this->_oStringHelper->str_split($value, 45, true, true) as $_value) {
                    $oPage->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
        }

        $topMargin    = 15;
        $methodStartY = $this->y;
        $this->y     -= 15;

        foreach($this->_oStringHelper->str_split($shippingMethod, 45, true, true) as $_value) {
            $oPage->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
            $this->y -= 15;
        }

        $yShipments = $this->y;
        $totalShippingChargesText =
            "(" . 'Total Shipping Charges' . " " .
            $this->_renderPrice($this->_oSwOrder->getCustomerShipCharge()) . ")";

        $oPage->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
        $yShipments -= $topMargin + 10;

        $yShipments -= $topMargin - 5;

        $currentY = min($yPayments, $yShipments);

        // replacement of Shipments-Payments rectangle block
        $oPage->drawLine(25,  $methodStartY, 25,  $currentY); //left
        $oPage->drawLine(25,  $currentY,     570, $currentY); //bottom
        $oPage->drawLine(570, $currentY,     570, $methodStartY); //right

        $this->y = $currentY;
        $this->y -= 15;
    }

    private function _renderPrice($fPrice)
    {
        return '$' . money_format('%.2n', $fPrice);
    }

    /**
     * Draw header for item table
     *
     * @param Zend_Pdf_Page $page
     * @return void
     */
    private function _drawHeader(Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y -15);
        $this->y -= 10;
        $page->setFillColor(new Zend_Pdf_Color_RGB(0, 0, 0));

        //columns headers
        $lines[0][] = [
            'text' => 'Products',
            'feed' => 35
        ];

        $lines[0][] = [
            'text'  => 'SKU',
            'feed'  => 220,
            'align' => 'right'
        ];

        $lines[0][] = [
            'text'  => 'Qty',
            'feed'  => 435,
            'align' => 'right'
        ];

        $lines[0][] = [
            'text'  => 'Price',
            'feed'  => 360,
            'align' => 'right'
        ];

        $lines[0][] = [
            'text'  => 'Tax',
            'feed'  => 495,
            'align' => 'right'
        ];

        $lines[0][] = [
            'text'  => 'Subtotal',
            'feed'  => 565,
            'align' => 'right'
        ];

        $lineBlock = [
            'lines'  => $lines,
            'height' => 5
        ];

        $this->_drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    //=======================================================
    // Copy and paste from Mage_Sales_Model_Order_Pdf_Invoice
    //=======================================================

    /**
     * Return PDF document
     *
     * @param  $invoice TODO What type is this ????
     * @return Zend_Pdf
     */
    public function getPdf(\SwOrder $oOrder)
    {
        $this->_oSwOrder = $oOrder;
        $oStyle          = new \Zend_Pdf_Style();

        $this->_pdf = new \Zend_Pdf();
        $this->_setFontBold($oStyle, 10);

        $oPage = $this->newPage();

        /* Add image */
        // TODO Get the packing slip image off the ClientApp
        $this->insertLogo($oPage);

        /* Add head */
        $this->insertOrder($oPage);

        /* Add table */
        $this->_drawHeader($oPage);

        /* Add body */
        foreach($oOrder->getSwOrderItems() as $oOrderItem) {
            /* Draw item */
            $oPage = $this->_drawLineItem($oPage, $oOrderItem);
//            $oPage = end($this->_pdf->pages);
        }

        /* Add totals */
        $this->insertTotals($oPage, $oOrder);

        return $this->_pdf;
    }

    /**
     * Create new page and assign to PDF object
     *
     * @param  array $settings
     * @return Zend_Pdf_Page
     */
    private function newPage(array $aSettings=[])
    {
        /* Add new table head */
        $pageSize            = !empty($settings['page_size']) ? $settings['page_size'] : Zend_Pdf_Page::SIZE_A4;
        $oPage               = $this->_pdf->newPage($pageSize);
        $this->_pdf->pages[] = $oPage;
        $this->y             = 800;

        if(!empty($aSettings['table_header'])) {
            $this->_drawHeader($oPage);
        }

        return $oPage;
    }

    /**
     * Draw item line
     */
    private function _drawLineItem(\Zend_Pdf_Page $oPage, \SwOrderItem $oSwOrderItem)
    {
        $lines  = [];

        // draw Product name
        $lines[0] = [[
            'text' => $this->_oStringHelper->str_split($oSwOrderItem->getName(), 35, true, true),
            'feed' => 35,
        ]];

        // draw SKU
        $lines[0][] = [
            'text'  => $this->_oStringHelper->str_split($oSwOrderItem->getSwSku(), 17),
            'feed'  => 290,
            'align' => 'right'
        ];

        // draw QTY
        $lines[0][] = [
            'text'  => $oSwOrderItem->getQuantity() * 1,
            'feed'  => 435,
            'align' => 'right'
        ];

        // draw item Prices
        //--------------------------------------------------------------------------------=
        // XXX I needs to be defined by how many times this function is called?
        //--------------------------------------------------------------------------------=
        $i            = 0;
        $feedPrice    = 395;
        $feedSubtotal = $feedPrice + 170;

        // draw Price
        $lines[$i][] = [
            'text'  => $this->_renderPrice($oSwOrderItem->getPricePerUnit()),
            'feed'  => $feedPrice,
            'font'  => 'regular',
            'align' => 'right'
        ];

        // draw Subtotal
        $lines[$i][] = [
            'text'  => $this->_renderPrice($oSwOrderItem->getPrice(true)),
            'feed'  => $feedSubtotal,
            'font'  => 'regular',
            'align' => 'right'
        ];

        // draw Tax
        $lines[0][] = [
            'text'  => $this->_renderPrice($oSwOrderItem->getSalesTaxAmount()),
            'feed'  => 495,
            'font'  => 'regular',
            'align' => 'right'
        ];

        $lineBlock = [
            'lines'  => $lines,
            'height' => 20
        ];

        return $this->_drawLineBlocks($oPage, [$lineBlock], ['table_header' => true]);
    }

    /**
     * Set font as regular
     *
     * @param  Zend_Pdf_Page $object
     * @param  int $size
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontRegular($object, $size = 7)
    {
        $font = Zend_Pdf_Font::fontWithPath($this->_aFontPaths['regular']);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Set font as bold
     *
     * @param  Zend_Pdf_Page $object
     * @param  int $size
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontBold($object, $size = 7)
    {
        $font = Zend_Pdf_Font::fontWithPath($this->_aFontPaths['bold']);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Set font as italic
     *
     * @param  Zend_Pdf_Page $object
     * @param  int $size
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontItalic($object, $size = 7)
    {
        $font = Zend_Pdf_Font::fontWithPath($this->_aFontPaths['italic']);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Draw lines
     *
     * draw items array format:
     * lines        array;array of line blocks (required)
     * shift        int; full line height (optional)
     * height       int;line spacing (default 10)
     *
     * line block has line columns array
     *
     * column array format
     * text         string|array; draw text (required)
     * feed         int; x position (required)
     * font         string; font style, optional: bold, italic, regular
     * font_file    string; path to font file (optional for use your custom font)
     * font_size    int; font size (default 7)
     * align        string; text align (also see feed parametr), optional left, right
     * height       int;line spacing (default 10)
     *
     * @param  Zend_Pdf_Page $page
     * @param  array $draw
     * @param  array $pageSettings
     * @throws Mage_Core_Exception
     * @return Zend_Pdf_Page
     */
    private function _drawLineBlocks(Zend_Pdf_Page $page, array $draw, array $pageSettings = array())
    {
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                throw new \Exception('Invalid draw line data. Please define "lines" array.');
            }
            $lines  = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;

            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = array($column['text']);
                        }
                        $top = 0;
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < 15) {
                $page = $this->newPage($pageSettings);
            }

            foreach ($lines as $line) {
                $maxHeight = 0;
                foreach ($line as $column) {
                    $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
                    if (!empty($column['font_file'])) {
                        $font = Zend_Pdf_Font::fontWithPath($column['font_file']);
                        $page->setFont($font, $fontSize);
                    } else {
                        $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
                        switch ($fontStyle) {
                            case 'bold':
                                $font = $this->_setFontBold($page, $fontSize);
                                break;
                            case 'italic':
                                $font = $this->_setFontItalic($page, $fontSize);
                                break;
                            default:
                                $font = $this->_setFontRegular($page, $fontSize);
                                break;
                        }
                    }

                    if (!is_array($column['text'])) {
                        $column['text'] = array($column['text']);
                    }

                    $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                    $top = 0;
                    foreach ($column['text'] as $part) {
                        if ($this->y - $lineSpacing < 15) {
                            $page = $this->newPage($pageSettings);
                        }

                        $feed = $column['feed'];
                        $textAlign = empty($column['align']) ? 'left' : $column['align'];
                        $width = empty($column['width']) ? 0 : $column['width'];
                        switch ($textAlign) {
                            case 'right':
                                if ($width) {
                                    $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                                }
                                else {
                                    $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                                }
                                break;
                            case 'center':
                                if ($width) {
                                    $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                                }
                                break;
                        }
                        $page->drawText($part, $feed, $this->y-$top, 'UTF-8');
                        $top += $lineSpacing;
                    }

                    $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                }
                $this->y -= $maxHeight;
            }
        }

        return $page;
    }
}
