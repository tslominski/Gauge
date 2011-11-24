<?php

/*
* This file is part of the Gauge Framework
* (c) Tomasz Słomiński <tomasz@slominski.it>
*/

namespace Gauge\Writers{

	use Gauge\Gauge;

	use Gauge\Interfaces\WriterInterface;
	
	/**
	 * Simple html writer
	 *
	 * @author Tomasz Słomiński <tomasz@slominski.it>
	 * @since 2011-11-15
	 * @version 1.0
	 * @package Gauge
	 **/
	class HtmlWriter implements WriterInterface{
		
		/**
		 * True if header was written
		 * @var boolean
		 */
		protected $bHeaderWritten = false;
		
		/**
		 * True if footer was written
		 * @var boolean
		 */
		protected $bFooterWritten = false;
		
		/**
		 * Print template
		 * @var string
		 */
		protected $sTemplate = '<p>%1$s<br />PHP %4$s @ %5$s<br />%2$d iterations, %3$.4f seconds</p>';
		
		protected $sHeader = '<!DOCTYPE html><html><head><meta charset="utf-8" /><title>Gauge</title></head><body>';
		
		protected $sFooter = '</body></html>';
		
		
		/**
		 * Class constructor
		 * @param string $sTemplate If template is string, replaces default template
		 */
		public function __construct($sTemplate = null, $sHeader = null, $sFooter = null){
			
			if (is_string($sTemplate)){
				
				$this->sTemplate = $sTemplate;
				
			} // if
			
			if (is_string($sHeader)){
			
				$this->sHeader = $sHeader;
			
			} // if
			
			if (is_string($sFooter)){
			
				$this->sFooter = $sFooter;
			
			} // if
			
		} // __construct
		
		/**
		 * Writes footer
		 */
		public function __destruct(){
			
			if (!$this->bFooterWritten){
				
				$this->writeFooter();
				
			} // if
			
		} // __destruct
		
		/**
		 * (non-PHPdoc)
		 * @see Gauge\Interfaces.WriterInterface::write()
		 */
		public function write($sTestId, $aTestData, $sPHP, $sSystem){
			
			$nIterations = $aTestData[Gauge::KEY_ITERATIONS];
			
			$fTime = $aTestData[Gauge::KEY_TIME];

			if (!$this->bHeaderWritten){
			
				$this->writeHeader();
			
			} // if
			
			printf($this->sTemplate, $sTestId, $nIterations, $fTime, $sPHP, $sSystem);
			
		} // write
		
		/**
		 * Writes header
		 */
		protected function writeHeader(){
			
			print $this->sHeader;
			
			$this->bHeaderWritten = true;

		} // writeHeader
				
		/**
		 * Writes footer
		 */
		protected function writeFooter(){
				
			print $this->sFooter;
			
			$this->bFooterWritten = true;
				
		} // writeFooter
		
	} // class
	
} // namespace