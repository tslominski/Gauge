<?php

/*
* This file is part of the Gauge Framework
* (c) Tomasz Słomiński <tomasz@slominski.it>
*/

namespace Gauge {
	
	use Gauge\Interfaces\WriterInterface;

	/**
	 * Main gauge class
	 *
	 * @author Tomasz Słomiński <tomasz@slominski.it>
	 * @since 2011-11-15
	 * @version 1.0
	 * @package Gauge
	 * @todo Tests
	**/
	class Gauge {
		
		# test keys
		
		const KEY_START 		= 'start';
		const KEY_STOP  		= 'stop';
		const KEY_ITERATIONS  	= 'iterations';
		const KEY_TIME			= 'total_time';
		const KEY_EXECUTIONS	= 'executions';
		const KEY_TEST_CODE		= 'test_code';
		const KEY_CODE			= 'code';
		
		# options names
		
		const OPT_TEMP_DIR		= 'tmp';
		const OPT_ITERATIONS	= 'iterations';
		const OPT_TEMP_PREFIX	= 'tmp_prefix';
		const OPT_TEMPLATE_FILE	= 'tpl_file';
		const OPT_UNAME_ARG		= 'uname_arg';

		/**
		 * Default configuration
		 * @var array
		 */
		protected $aDefaultConfig = array(
			self::OPT_UNAME_ARG 	=> 'a',
			self::OPT_TEMP_DIR 		=> 'temp',
			self::OPT_TEMP_PREFIX	=> 'gauge_',
			self::OPT_ITERATIONS	=> 1000,
			self::OPT_TEMPLATE_FILE	=> 'tpl/gauge_test.tpl'
		);
		
		/**
		 * Runtime configuration
		 * @var array
		 */
		protected $aConfig  = array();
		
		/**
		 * Test runs data
		 * @var array
		 */
		protected $aTests = array();
		
		/**
		 * Tests data
		 * @var array
		 */
		protected $aTestsDefinitions = array();
		
		/**
		 * Current test
		 * @var string
		 */
		protected $sCurrentTestId = null;
		
		/**
		 * Test result writers
		 * @var array
		 */
		protected $aWriters = array();
		
		/**
		 * System string. Cached for efficency
		 * @var string
		 */
		protected $sSystem = null;
		
		/**
		 * Temporary files to delete
		 * @var array
		 */
		protected $aFiles = array();
		
		/**
		 * Class constructor
		 * @param array $aConfig Configuration
		 */
		public function __construct(array $aConfig = array()){
			
			$this->aConfig = $aConfig + $this->aDefaultConfig;
			
			$this->sSystem = php_uname($this->aConfig[self::OPT_UNAME_ARG]);
			
		} // __construct 
		
		/**
		 * Class destructor. Supprimes all created temp files
		 */
		public function __destruct(){
			
			foreach ($this->aFiles as $sFile){
				
				unlink($sFile);
				
			} // foreach
			
		} // __destruct
		
		/**
		 * Adds writer to writers list
		 * @param Gauge\Interfaces\WriterInterface $oWriter
		 * @return Gauge\Gauge Self
		 */
		public function addWriter(WriterInterface $oWriter){
			
			$this->aWriters[] = $oWriter;
			
			return $this;
			
		} // addWriter
		
		/**
		 * Sets option
		 * @param string $sOption Option name
		 * @param mixed $mValue Option value
		 * @return Gauge\Gauge Self
		 */
		public function setOption($sOption, $mValue){
			
			$this->aConfig[(string)$sOption] = $mValue;
			
			return $this;
			
		} // setOption

		/**
		 * Executes test
		 * Test code is executed inside of the function, so it is isolated from outside. It has acces, via $this,
		 * to Gauge object
		 * @param string $sTestId Id of test
		 * @param string $sTestCode Test code or path to file containing code
		 * @param string $sBoilerPlateCode Boileprlate code or path to file containing code
		 * @param integer|null $nIterations Number of iterations. If null, default number of iterations is executed
		 * @return Gauge\Gauge Self
		 */
		public function execute($sTestId, $sTestCode, $sBoilerPlateCode = '',  $nIterations = null){
			
			$this->sCurrentTestId = (string)$sTestId;
			
			// $this->aTests[$this->sCurrentTestId][] = array(self::KEY_START => 0, self::KEY_STOP => 0, self::KEY_ITERATIONS => 0);
				
			$nIterations = (!is_int($nIterations) ? (int)$this->aConfig[self::OPT_ITERATIONS] : $nIterations);

			$sTestCode = $this->generateTestCode($this->sCurrentTestId, $this->prepareCode($sTestCode), $this->prepareCode($sBoilerPlateCode), $nIterations);		
			
			$this->aTestsDefinitions[$this->sCurrentTestId] = array(
				self::KEY_ITERATIONS => $nIterations,
				self::KEY_CODE		 => $sTestCode
			);
			
			$sTestFile = $this->createTestFile($sTestCode);
			
			require_once $sTestFile;
			
			$this->unlinkTestFile($sTestFile);
			
			return $this;
			
		} // execute
		
		/**
		 * Return list of executed tests id
		 * @return array List of test ids
		 */
		public function getTestsIds(){
			
			return array_keys($this->aTests);
			
		} // getTestsIds
		
		/**
		* Starts timer. If timer id not specified, starts current timer
		* @param string $sTestId
		* @return Gauge\Gauge Self
		*/
		protected function start($sTestId){
			
			$sTestId = $sTestId === null ? $this->sCurrentTestId : (string)$sTestId;
		
			if (!array_key_exists($sTestId, $this->aTests)){
				
				$this->aTests[$sTestId] = array();
				
			} // if
						
			$this->aTests[$sTestId][] = array(
				self::KEY_START 		=> microtime(true),
				self::KEY_STOP			=> 0,
				self::KEY_ITERATIONS	=> $this->aTestsDefinitions[$sTestId][self::KEY_ITERATIONS],
				self::KEY_CODE			=> $this->aTestsDefinitions[$sTestId][self::KEY_CODE]
			);
				
			return $this;
			
		} // start
		
		/**
		 * Stops timer. If timer id not specified, stops current timer
		 * @param string $sTestId
		 * @return Gauge\Gauge Self
		 */
		protected function stop($sTestId = null){
			
			$sTestId = ($sTestId === null ? $this->sCurrentTestId : (string)$sTestId);
						
			$this->aTests[$sTestId][$this->getLastTestIndex($sTestId)][self::KEY_STOP] = microtime(true);				
				
			return $this;
		
		} // stop
		
		/**
		 * Writes test result for last test with given id
		 * @param string $sTestId
		 * @return Gauge\Gauge Self
		 */
		protected function write($sTestId){
			
			$sTestId = $sTestId === null ? $this->sCurrentTestId : (string)$sTestId;
							
			if (isset($this->aTests[$sTestId]) && count($this->aTests[$sTestId]) > 0){				
															
				$aTestData = $this->aTests[$sTestId][$this->getLastTestIndex($sTestId)];
				
				$aTestData[self::KEY_EXECUTIONS] = 1;
				
				$aTestData[self::KEY_TIME] = $aTestData[self::KEY_STOP] - $aTestData[self::KEY_START]; 
												
				/**
				 * @var Gauge\Interfaces\WriterInterface
				 */
				foreach ($this->aWriters as $oWriter){
					
					$oWriter->write($sTestId, $aTestData + $this->aTestsDefinitions[$sTestId], PHP_VERSION, $this->sSystem);
					
				} // foreach
		
			}
			
			return $this;
				
		} // write

		/**
		* Writes summaric result for all tests with the same id
		* @param string $sTestId
		* @return Gauge\Gauge Self
		*/
		public function writeSummary($sTestId){
				
			$sTestId = ($sTestId === null ? $this->sCurrentTestId : (string)$sTestId);

			if (isset($this->aTests[$sTestId])){
					
				$aTestSummary = $this->getTestSummary($sTestId);
							
				/**
				 * @var Gauge\Interfaces\WriterInterface
				 */
				foreach ($this->aWriters as $oWriter){
			
					$oWriter->write($sTestId, $aTestSummary, PHP_VERSION, $this->sSystem);
			
				} // foreach
			
			} // if
		
			return $this;
		
		} // write		
		
		/**
		 * Returns test summary (# of executions, # of iterations, time)
		 * @param string $sTestId Id of test
		 * @return array Test summary
		 */
		protected function getTestSummary($sTestId){
			
			$aResult = array(
				self::KEY_EXECUTIONS => 0,
				self::KEY_ITERATIONS => 0,
				self::KEY_TIME => 0
			);			
			
			if (isset($this->aTests[$sTestId])){
								 
				$aResult[self::KEY_EXECUTIONS] = count($this->aTests[$sTestId]);
				
				foreach ($this->aTests[$sTestId] as $aTestRun){
					
					$aResult[self::KEY_ITERATIONS] += $aTestRun[self::KEY_ITERATIONS];
					
					$aResult[self::KEY_TIME] += ($aTestRun[self::KEY_STOP] - $aTestRun[self::KEY_START]);
					
				} // foreach
				
			} // if
			
			return $aResult;
		
		} // 
		
		/**
		 * Creates temporary file with given code inside
		 * @param string $sCode Source code to inject
		 * @return string Path to temporary file
		 */
		protected function createTestFile($sCode){
			
			$sTestFile = tempnam($this->aConfig[self::OPT_TEMP_DIR], $this->aConfig[self::OPT_TEMP_PREFIX]);
			
			$this->aFiles[$sTestFile] = $sTestFile;
			
			file_put_contents($sTestFile, $sCode);
			
			return $sTestFile;
			
		} // createTestFile
		
		/**
		 * Deletes file
		 * @param string $sFileName File name
		 */
		protected function unlinkTestFile($sTestFile){
			
			if (file_exists($sTestFile) && unlink($sTestFile)){
					
				unset($this->aFiles[$sTestFile]);
				
				return true;
				
			} // if
			
			return false;
			
		} // unlinkTestFile
		
		/**
		 * Prepares code. If filename passed, loads code from file. Strips starting '<?php' tag
		 * @param string $sCode Code or path to file
		 * @return string PHP code to execute
		 * @todo Use tokenizer to parse file
		 * @todo Calidate code
		 */
		protected function prepareCode($sCode){
			
			$sResult = (!empty($sCode) && file_exists($sCode)) ? file_get_contents($sCode) : (string)$sCode;
			
			if (substr($sResult, 0, 5) == '<?php'){
			
				$sResult = substr($sResult, 5);
			
			} // if
				
			return $sResult;
		
		} // prepareCode		
		
		/**
		 * Generates test code based on template file
		 * @param string $sTestId test id
		 * @param string $sTestCode Testing code
		 * @param string $sBoilerplateCode Boilerplate code
		 * @param integer $nIterations Number of iterations
		 */
		protected function generateTestCode($sTestId, $sTestCode, $sBoilerplateCode, $nIterations){
		
			$sTemplate = file_get_contents($this->aConfig[self::OPT_TEMPLATE_FILE]);
			
			return sprintf($sTemplate, (string)$sTestId, (string)$sTestCode, (string)$sBoilerplateCode,  (int)$nIterations );		
					
		} // generateTestCode
		
		/**
		 * Get numeric index of last test with given id
		 * @param string $sTestId Test id
		 * @return integer Index of last id (or 0 if no test, but it should never happen)
		 */
		protected function getLastTestIndex($sTestId){
			
			return isset($this->aTests[$sTestId]) ? count($this->aTests[$sTestId]) - 1 : 0;
			
		} // getLastTestIndex
		
	} // class
	
} // namespace