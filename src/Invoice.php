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
namespace Quickshiftin\Pdf\Invoice;

use Quickshiftin\Pdf\Invoice\Spec\Order as OrderSpec;
use Quickshiftin\Pdf\Invoice\Spec\OrderItem as OrderItemSpec;

use Zend_Pdf;
use Zend_Pdf_Font;
use Zend_Pdf_Page;
use Zend_Pdf_Color_GrayScale;
use Zend_Pdf_Color_Rgb;
use Zend_Pdf_Style;

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
class Invoice implements \ArrayAccess
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
        $_oOrder,
        $_sLogoPath,
        $_LineColor,
        $_TitleBgFillColor,
        $_HeaderBgFillColor,
        $_BodyFontColor,
        $_BodyHeaderFontColor,
        $_TitleFontColor,
        $_aFontPaths = [],
        $_oFactory;

    private
        $_sOrderText                  = 'Order # ',
        $_sOrderDateText              = 'Order Date: ',
        $_sOrderDateFormat            = 'M jS Y g:i a',
        $_sSoldToText                 = 'Sold to',
        $_sShipToText                 = 'Ship to',
        $_sPaymentMethodText          = 'Payment Method',
        $_sShippingMethodText         = 'Shipping Method',
        $_sProductColumnText          = 'Products',
        $_sSkuColumnText              = 'SKU',
        $_sPriceColumnText            = 'Price',
        $_sQtyColumnText              = 'Qty',
        $_sTaxColumnText              = 'Tax',
        $_sSubTotalColumnText         = 'Subtotal',
        $_sSubTotalText               = 'Subtotal',
        $_sShippingHandlingText       = 'Shipping & Handling',
        $_sGrandTotalExcludingTaxText = 'Grand Total (Excl. Tax)',
        $_sTaxText                    = 'Tax',
        $_sGrandTotalIncludingTaxText = 'Grand Total (Incl. Tax)',
        $_bShowSoldTo                 = true,
        $_bShowTaxColumn              = true,
        $_bShowPriceColumn            = true,
        $_bShowSubTotalColumn         = true,
        $_bShowSubTotal               = true,
        $_bShowShippingHandling       = true,
        $_bShowGrandTotalExcludingTax = true,
        $_bShowTax                    = true,
        $_bShowGrandTotalIncludingTax = true,
        $_bShowTotalShippingCharges   = true;

    public function __construct(Factory $oFactory=null)
    {
        if(!$oFactory) {
            $oFactory = new Factory();
        }

        $this->_oFactory      = $oFactory;
        $this->_oStringHelper = $oFactory->createStringHelper();
    }

    private $_sOffsetType = null;
    public function offsetExists($offset)
    {
        $sTextKey = '_s' . ucfirst($offset);
        $sBoolKey = '_b' . ucfirst($offset);

        if(isset($this->$sTextKey)) {
            $this->_sOffsetType = 'text';
            return true;
        }

        if(isset($this->$sBoolKey)) {
            $this->_sOffsetType = 'bool';
            return true;
        }

        return false;
    }

    public function offsetGet($offset)
    {
        if(!$this->offsetExists($offset)) {
            return null;
        }

        $sKey
            = $this->_sOffsetType == 'text'
            ? '_s' . ucfirst($offset)
            : '_b' . ucfirst($offset);

        return $this->$sKey;
    }

    public function offsetSet($offset, $value)
    {
        if(!$this->offsetExists($offset)) {
            return null;
        }

        $sKey
            = $this->_sOffsetType == 'text'
            ? '_s' . ucfirst($offset)
            : '_b' . ucfirst($offset);

        $this->$sKey = $value;
    }

    public function offsetUnset($offset)
    {
        if(!$this->offsetExists($offset)) {
            return null;
        }

        if($this->_sOffsetType == 'text') {
            $sKey = '_s' . ucfirst($offset);
            $this->$sKey = '';
        } else {
            $sKey = '_b' . ucfirst($offset);
            $this->$sKey = true;
        }
    }

    public function setRegularFontPath($sPath) { $this->_setFontPath('regular', $sPath); }
    public function setBoldFontPath($sPath)    { $this->_setFontPath('bold', $sPath); }
    public function setItalicFontPath($sPath)  { $this->_setFontPath('italic', $sPath); }

    public function setTitleBgFillColor(\Zend_Pdf_Color $value)
    {
        $this->_TitleBgFillColor =  $value;
    }

    public function setHeaderBgFillColor(\Zend_Pdf_Color $value)
    {
        $this->_HeaderBgFillColor = $value;
    }

    public function setBodyHeaderFontColor(\Zend_Pdf_Color $value)
    {
        $this->_BodyHeaderFontColor = $value;
    }

    public function setLineColor(\Zend_Pdf_Color $value)
    {
        $this->_LineColor = $value;
    }

    public function getBodyHeaderFontColor()
    {
        return $this->_BodyHeaderFontColor;
    }

    public function getLineColor()
    {
        return $this->_LineColor;
    }

    public function setBodyFontColor(\Zend_Pdf_Color $value)
    {
        $this->_BodyFontColor = $value;
    }

    public function setTitleFontColor($value)
    {
        $this->_TitleFontColor = $value;
    }

    private function _setFontPath($sType, $sPath)
    {
        $this->_aFontPaths[$sType] = $sPath;
    }

    public function setLogoPath($sLogoPath)
    {
        $this->_sLogoPath = $sLogoPath;
    }

    public function getTitleBgFillColor()
    {
        if(!is_null($this->_TitleBgFillColor)) {
            return $this->_TitleBgFillColor;
        }
        else{
            return $this->_oFactory->createColorGrayscale(0.4);
        }

    }

    public function getHeaderBgFillColor()
    {
        if(!is_null($this->_HeaderBgFillColor)) {
            return $this->_HeaderBgFillColor;
        }
        else{
            return $this->_oFactory->createColorGrayscale(0.8);
        }
    }

    public function getBodyFontColor()
    {
        if(!is_null($this->_BodyFontColor)) {
            return $this->_BodyFontColor;
        }
        else{
            return $this->_oFactory->createColorHtml('black');
        }
    }

    public function getTitleFontColor()
    {
        if(!is_null($this->_TitleFontColor)) {
            return $this->_TitleFontColor;
        }
        else{
            return $this->_oFactory->createColorHtml('white');
        }
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
        $aTotals = [];

        if($this->_bShowSubTotal) {
            $aTotals[] = $this->_buildTotalsArrayPair($this->_sSubTotalText, $this->_oOrder->getPriceBeforeShippingNoTax());
        }

        if($this->_bShowShippingHandling) {
            $aTotals[] = $this->_buildTotalsArrayPair($this->_sShippingHandlingText, $this->_oOrder->getCustomerShipCharge());
        }

        if($this->_bShowGrandTotalExcludingTax) {
            $aTotals[] = $this->_buildTotalsArrayPair(
                $this->_sGrandTotalExcludingTaxText,
                $this->_oOrder->getPriceBeforeShippingNotax() + $this->_oOrder->getCustomerShipCharge());
        }

        if($this->_bShowTax) {
            $aTotals[] = $this->_buildTotalsArrayPair($this->_sTaxText, $this->_oOrder->getSalesTaxAmount());
        }

        if($this->_bShowGrandTotalIncludingTax) {
            $aTotals[] = $this->_buildTotalsArrayPair($this->_sGrandTotalIncludingTaxText, $this->_oOrder->getTotalCost());
        }

        return $aTotals;
    }

    /**
     * Insert totals to pdf page
     *
     * @param  Zend_Pdf_Page $page
     * @return Zend_Pdf_Page
     */
    private function insertTotals(Zend_Pdf_Page $oPage, OrderSpec $oOrder)
    {
        $lineBlock = [
            'lines'  => $this->_buildTotalsArray(),
            'height' => 15
        ];

        $this->y -= 20;

        $oPage = $this->_drawLineBlocks($oPage, [$lineBlock]);

        return $oPage;
    }

    private function insertNote(Zend_Pdf_Page $oPage, OrderSpec $oOrder)
    {
        $sNote = $oOrder->getOrderNote();

        if(empty($sNote)) {
            return;
        }

        $oPage->drawText($sNote, 35, $this->y, 'UTF-8');
    }

    /**
     * Insert order to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $obj
     * @param bool $bPutOrderId
     */
    private function insertOrder(Zend_Pdf_Page $oPage)
    {
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;

        //Main Section Background Color
        $oPage->setFillColor($this->getTitleBgFillColor());
        $oPage->setLineColor($this->_LineColor);
        $oPage->drawRectangle(25, $top, 570, $top - 40);

        // Section Text Color
        $oPage->setFillColor($this->getTitleFontColor());
        $this->_setFontRegular($oPage, 10);
        
        $oPage->drawText($this->_sOrderText . $this->_oOrder->getOrderId(), 35, ($top -= 15), 'UTF-8');

        $oPage->drawText(
            $this->_sOrderDateText . $this->_oOrder->getSaleDate($this->_sOrderDateFormat), 
            35, ($top -= 15), 'UTF-8'
        );

        $top -= 10;

        // Sold To and Ship To Background Color
        $oPage->setFillColor($this->getHeaderBgFillColor());
        $oPage->setLineColor($this->_LineColor);
        $oPage->setLineWidth(0.5);
        $oPage->drawRectangle(25, $top, 275, ($top - 25));
        $oPage->drawRectangle(275, $top, 570, ($top - 25));

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_oOrder->getFullBillingAddress();

        /* Payment */
        // Just show the payment method's title on the PDF, that should be plenty...
        $payment = [$this->_oOrder->getPaymentMethod()];

        /* Shipping Address and Method */
        /* Shipping Address */
        $shippingAddress = $this->_oOrder->getFullShippingAddress();
        $shippingMethod  = $this->_oOrder->getShippingMethodName();

        //Shipping To and Sold To Text Color
        $oPage->setFillColor($this->getBodyHeaderFontColor());
        $this->_setFontBold($oPage, 12);
        $oPage->drawText($this->_sSoldToText, 35, ($top - 15), 'UTF-8');

        if($this->_bShowSoldTo) {
            $oPage->drawText($this->_sShipToText, 285, ($top - 15), 'UTF-8');
        }

        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));

        // Shipping and billing address contentBackground
        $oPage->setFillColor($this->_oFactory->createColorGrayscale(1));

        $oPage->drawRectangle(25, ($top - 25), 570, $top - 33 - $addressesHeight);


        // Shipping and Billing Address text
        $oPage->setFillColor($this->getBodyFontColor());
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
                    if($this->_bShowSoldTo) {
                        $oPage->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                    }
                    $this->y -= 15;
                }
            }
        }


        $addressesEndY = min($addressesEndY, $this->y);
        $this->y = $addressesEndY;

        // Shipping Method Background Color
        $oPage->setFillColor($this->getHeaderBgFillColor());
        $oPage->setLineWidth(0.5);
        $oPage->drawRectangle(25, $this->y, 275, $this->y-25);
        $oPage->drawRectangle(275, $this->y, 570, $this->y-25);

        // Shipping Method Text Color
        $this->y -= 15;
        $this->_setFontBold($oPage, 12);
        $oPage->setFillColor($this->getBodyHeaderFontColor());
        $oPage->drawText($this->_sPaymentMethodText, 35, $this->y, 'UTF-8');
        $oPage->drawText($this->_sShippingMethodText, 285, $this->y , 'UTF-8');

        $this->y -=10;
        $oPage->setFillColor($this->getHeaderBgFillColor());

        // Credit Card and Shipping Method Text
        $this->_setFontRegular($oPage, 10);
        $oPage->setFillColor($this->getBodyFontColor());

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
            $this->_renderPrice($this->_oOrder->getCustomerShipCharge()) . ")";

        if($this->_bShowTotalShippingCharges) {
            $oPage->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
        }
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

        // Products Attributes Section Background Color
        $page->setFillColor($this->getHeaderBgFillColor());
        $page->setLineColor($this->_LineColor);
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y -15);
        $this->y -= 10;

        //Attributes Text Color
        $page->setFillColor($this->getBodyHeaderFontColor());

        //columns headers
        $lines[0][] = [
            'text' => $this->_sProductColumnText,
            'feed' => 35
        ];

        $lines[0][] = [
            'text'  => $this->_sSkuColumnText,
            'feed'  => 220,
            'align' => 'right'
        ];


        $lines[0][] = [
            'text'  => $this->_sQtyColumnText,
            'feed'  => 435,
            'align' => 'right'
        ];


        if($this->_bShowPriceColumn) {
            $lines[0][] = [
                'text'  => $this->_sPriceColumnText,
                'feed'  => 360,
                'align' => 'right'
            ];
        }

        if($this->_bShowTaxColumn) {
            $lines[0][] = [
                'text'  => $this->_sTaxColumnText,
                'feed'  => 495,
                'align' => 'right'
            ];
        }

        if($this->_bShowSubTotalColumn) {
            $lines[0][] = [
                'text'  => $this->_sSubTotalColumnText,
                'feed'  => 565,
                'align' => 'right'
            ];
        }

        $lineBlock = [
            'lines'  => $lines,
            'height' => 5
        ];

        $this->_drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        // Order Items Text Color
        $page->setFillColor($this->getBodyFontColor());
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
    public function getPdf(OrderSpec $oOrder)
    {
        $this->_oOrder = $oOrder;
        $oStyle        = $this->_oFactory->createStyle();

        $this->_pdf = $this->_oFactory->createPdf();
        $this->_setFontBold($oStyle, 10);

        $oPage = $this->newPage();

        // Add image
        $this->insertLogo($oPage);

        // Add head
        $this->insertOrder($oPage);

        // Add table
        $this->_drawHeader($oPage);

        // Add body
        foreach($oOrder->getOrderItems() as $oOrderItem) {
            // Draw item
            $oPage = $this->_drawLineItem($oPage, $oOrderItem);

// XXX What is this about?
//            $oPage = end($this->_pdf->pages);
        }

        // Add totals
        $this->insertTotals($oPage, $oOrder);

        // Add optional note
        $this->insertNote($oPage, $oOrder);

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
    private function _drawLineItem(Zend_Pdf_Page $oPage, OrderItemSpec $oOrderItem)
    {
        $lines  = [];

        // draw Product name
        $lines[0] = [[
            'text' => $this->_oStringHelper->str_split($oOrderItem->getName(), 35, true, true),
            'feed' => 35,
        ]];

        // draw SKU
        $lines[0][] = [
            'text'  => $this->_oStringHelper->str_split($oOrderItem->getSku(), 17),
            'feed'  => 290,
            'align' => 'right'
        ];

        // draw QTY
        $lines[0][] = [
            'text'  => $oOrderItem->getQuantity() * 1,
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
        if($this->_bShowPriceColumn) {
            $lines[$i][] = [
                'text'  => $this->_renderPrice($oOrderItem->getPricePerUnit()),
                'feed'  => $feedPrice,
                'font'  => 'regular',
                'align' => 'right'
            ];
        }

        // draw Subtotal
        if($this->_bShowSubTotalColumn) {
            $lines[$i][] = [
                'text'  => $this->_renderPrice($oOrderItem->getPrice(true)),
                'feed'  => $feedSubtotal,
                'font'  => 'regular',
                'align' => 'right'
            ];
        }

        // draw Tax
        if($this->_bShowTaxColumn) {
            $lines[0][] = [
                'text'  => $this->_renderPrice($oOrderItem->getSalesTaxAmount()),
                'feed'  => 495,
                'font'  => 'regular',
                'align' => 'right'
            ];
        }

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
        $font = $this->_oFactory->createPdfFont($this->_aFontPaths['regular']);
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
        $font = $this->_oFactory->createPdfFont($this->_aFontPaths['bold']);
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
        $font = $this->_oFactory->createPdfFont($this->_aFontPaths['italic']);
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
                        $font = $this->_oFactory->createPdfFont($column['font_file']);
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
