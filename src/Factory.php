<?php
namespace Quickshiftin\Pdf\Invoice;

use \Zend_Pdf;
use \Zend_Pdf_Style;
use \Zend_Pdf_Font;
use \Zend_Pdf_Page;
use \Zend_Pdf_Color_GrayScale;
use \Zend_Pdf_Color_Html;

class Factory
{
    private static $_bIconvInitialized = false;

    // Initialize iconv settings
    // https://www.sonassi.com/blog/knowledge-base/magento-wrong-charset-conversion-from-utf-16be-to-utf-8-is-not-allowed
    public function __construct()
    {
        if(!self::$_bIconvInitialized) {
            iconv_set_encoding('internal_encoding', 'UTF-8');
            iconv_set_encoding('output_encoding', 'ISO-8859-1');

            self::$_bIconvInitialized = true;
        }
    }

    public function createStringHelper()
    {
        return new StringHelper();
    }

    public function createPdf()
    {
        return new Zend_Pdf();
    }

    public function createStyle()
    {
        return new Zend_Pdf_Style();
    }

    public function createPdfFont($sFontPath)
    {
        return Zend_Pdf_Font::fontWithPath($sFontPath);
    }

    public function createColorGrayscale($fValue)
    {
        return new Zend_Pdf_Color_GrayScale($fValue);
    }

    public function createColorHtml($sColor)
    {
        return new Zend_Pdf_Color_Html($sColor);
    }
}
