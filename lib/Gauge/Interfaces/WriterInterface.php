<?php

/*
* This file is part of the Gauge Framework
* (c) Tomasz Słomiński <tomasz@slominski.it>
*/

namespace Gauge\Interfaces {
	
	/**
	 * Writer interface
	 *
	 * @author Tomasz Słomiński <tomasz@slominski.it>
	 * @since 2011-11-15
	 * @version 1.0
	 * @package Gauge
	 **/
	interface WriterInterface {

		/**
		 * Writes test data
		 * @param string $sTestId Id of test
		 * @param integer $nIterations Number of iterations
		 * @param float $fTime Time (in seconds)
		 * @param string $sPHP PHP version
		 * @param string $sSystem System identification
		 */
		public function write($sTestId, $nIterations, $fTime, $sPHP, $sSystem);
		
	} // interface
	
} // namespace