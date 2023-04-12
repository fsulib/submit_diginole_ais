<?php

require_once(__DIR__ . '/../vendor/autoload.php');

class FPDF extends tFPDF
{
  protected $_tplIdx;
  public function Header()
  {
    if (is_null($this->_tplIdx)) {
      $this->setSourceFile(__DIR__ . '/coverpage.pdf');
      $this->_tplIdx = $this->importPage(1);
    }   
  }
}

?>
