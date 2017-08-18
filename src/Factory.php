<?php
namespace Quickshiftin\Pdf\Invoice;

use \Zend_Pdf;
use \Zend_Pdf_Style;
use \Zend_Pdf_Font;
use \Zend_Pdf_Page;
use \Zend_Pdf_Color_GrayScale;
use \Zend_Pdf_Color_Rgb;

class Factory
{
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
