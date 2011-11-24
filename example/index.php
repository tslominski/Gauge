<?php

namespace {

	use Gauge\Gauge;
	use Gauge\Writers\HtmlWriter;
	use Gauge\Writers\SqlWriter;
	use \PDO;

error_reporting(-1);
ini_set('display_errors', 1);	
	
# delete it if you use PCR-0 compliant loader
require_once implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'lib', 'Gauge', 'Interfaces', 'WriterInterface.php'));
require_once implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'lib', 'Gauge', 'Writers', 'HtmlWriter.php'));
require_once implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'lib', 'Gauge', 'Writers', 'SqlWriter.php'));
require_once implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'lib', 'Gauge', 'Gauge.php'));


$oGauge = new Gauge;

$oGauge

	->addWriter(new SqlWriter(new PDO('sqlite:gauge.sq3')))

	->setOption(Gauge::OPT_ITERATIONS, 10000)
	->execute('calibrate', '')
	->execute('calibrate', '')
	->execute('test1', '$a+=1;', '$a=0;')
	->execute('test2', '$a++;',  '$a=0;')
	->execute('test3', '++$a;',  '$a=0;')
	->execute('test4', '$a = $a+1;',  '$a=0;')
	->execute('calibrate', '')
	
	->setOption(Gauge::OPT_ITERATIONS, 1000)
	->execute('calibrate', '')
	->execute('boilerplate', '', 'tests/callbacks/global_boilerplate.php', 0)
	->execute('direct_call', 'tests/callbacks/direct_call.php', 'tests/callbacks/boilerplate.php')
	->execute('call_user_func', 'tests/callbacks/call_user_func.php', 'tests/callbacks/boilerplate.php')
	->execute('invoke', 'tests/callbacks/invoke.php', 'tests/callbacks/boilerplate.php');

	$oGauge
		->addWriter(new HtmlWriter(null, '<!DOCTYPE html><html><head><meta charset="utf-8" /><title>My Gauge</title></head><body>'));

	foreach($oGauge->getTestsIds() as $sTestId){
	
		$oGauge->writeSummary($sTestId);
	
	}


} // namespace