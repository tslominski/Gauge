<?php

/*
* This file is part of the Gauge Framework
* (c) Tomasz Słomiński <tomasz@slominski.it>
*/

namespace Gauge\Writers{

	use Gauge\Interfaces\WriterInterface;
	use PDO;
	
	/**
	 * Simple sql writer
	 * Create table statement (for sqlite)
	 *    CREATE TABLE gauge_log (id INTEGER PRIMARY KEY, id_test TEXT, iterations NUMERIC, test_time NUMERIC, php TEXT, system TEXT);
	 * @author Tomasz Słomiński <tomasz@slominski.it>
	 * @since 2011-11-15
	 * @version 1.0
	 * @package Gauge
	 **/
	class SqlWriter implements WriterInterface{
			
		/**
		 * Database connection
		 * @var PDO
		 */
		protected $oConnection = null;
		
		/**
		 * Query template
		 * @var string
		 */
		protected $sQuery = 'INSERT INTO gauge_log (id_test, iterations, test_time, php, system) VALUES (?, ?, ?, ?, ?)';
		
		/**
		 * Prepared insert statement
		 * @var PDOStatement
		 */
		protected $oStatement = null;
		
		/**
		 * Class constructor
		 * @param PDO $oConnection database connection
		 * @param string|null $sQuery If not null, changes default query template
		 */
		public function __construct(PDO $oConnection, $sQuery = null){
	
			$this->oConnection = $oConnection;
			
			if (is_string($sQuery)){
			
				$this->sQuery = $sQuery;
			
			} // if
			
			$this->oStatement = $oConnection->prepare($this->sQuery);
		
		} // __construct
		
		/**
		 * (non-PHPdoc)
		 * @see Gauge\Interfaces.WriterInterface::write()
		 */
		public function write($sTestId, $nIterations, $fTime, $sPHP, $sSystem){

			$this->oStatement->execute(array($sTestId, $nIterations, $fTime, $sPHP, $sSystem));
			
		} // write
		
	} // class
	
} // namespace