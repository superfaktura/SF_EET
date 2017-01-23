<?php

namespace Po1nt\EET\Tests;

use PHPUnit\Framework\TestCase;
use Po1nt\EET\Certificate;
use Po1nt\EET\Dispatcher as Tested;
use Po1nt\EET\Receipt;
use Po1nt\EET\Exceptions\ServerException;
use Po1nt\EET\Exceptions\ClientException;

/**
 * Tests for Dispatcher class
 * @package Po1nt\EET\Tests
 */
class DispatcherTest extends TestCase {

	public function testSendOk() {
		$fik = $this->getTestDispatcher()->send($this->getExampleReceipt());
		$this->assertInternalType('string', $fik);
	}

	/**
	 * @expectedException ServerException
	 */
	public function testSendError() {
		$dispatcher = $this->getTestDispatcher();
		$r = $this->getExampleReceipt();
		$r->dic_popl = 'x';
		$dispatcher->send($r);
	}

	public function testGetConnectionTime() {
		$dispatcher = $this->getTestDispatcher();
		$dispatcher->trace = true;
		$dispatcher->send($this->getExampleReceipt());
		$time = $dispatcher->getConnectionTime();
		$this->assertInternalType('float', $time);
		$this->assertTrue($time > 0);
	}

	public function testGetConnectionTimeTillLastRequest() {
		$dispatcher = $this->getTestDispatcher();
		$dispatcher->trace = true;
		$dispatcher->send($this->getExampleReceipt());
		$time = $dispatcher->getConnectionTime(true);
		$this->assertInternalType('float', $time);
		$this->assertTrue($time > 0);
	}

	public function testGetLastResponseTime() {
		$dispatcher = $this->getTestDispatcher();
		$dispatcher->trace = true;
		$dispatcher->send($this->getExampleReceipt());
		$time = $dispatcher->getLastResponseTime();
		$this->assertInternalType('float', $time);
		$this->assertTrue($time > 0);
	}

	public function testGetLastRequestSize() {
		$dispatcher = $this->getTestDispatcher();
		$dispatcher->trace = true;
		$dispatcher->send($this->getExampleReceipt());
		$size = $dispatcher->getLastRequestSize();
		$this->assertInternalType('int', $size);
		$this->assertTrue($size > 0);
	}

	public function testGetLastResponseSize() {
		$dispatcher = $this->getTestDispatcher();
		$dispatcher->trace = true;
		$dispatcher->send($this->getExampleReceipt());
		$size = $dispatcher->getLastResponseSize();
		$this->assertInternalType('int', $size);
		$this->assertTrue($size > 0);
	}

	/**
	 * @expectedException ClientException
	 */
	public function testTraceNotEnabled() {
		$dispatcher = $this->getTestDispatcher();
		$dispatcher->send($this->getExampleReceipt());
		$dispatcher->getLastResponseSize();
	}

	public function testDateReformat() {
		$dispatcher = $this->getTestDispatcher();
		$data = $dispatcher->prepareData($this->getExampleReceipt());
		$this->assertRegExp('/^(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})$/', $data['Data']['dat_trzby']);
	}

	/**
	 * @return Tested
	 */
	private function getTestDispatcher() {
		/** @var Certificate $certificate */
		$certificate = new Certificate(DIR_CERT . '/EET_CA1_Playground-CZ1212121218.p12', 'eet');
		return new Tested(PLAYGROUND_WSDL, $certificate);
	}

	/**
	 * @return Receipt
	 */
	private function getExampleReceipt() {
		$r = new Receipt();
		$r->uuid_zpravy = 'b3a09b52-7c87-4014-a496-4c7a53cf9120';
		$r->dic_popl = 'CZ1212121218';
		$r->id_provoz = '181';
		$r->id_pokl = '1';
		$r->porad_cis = '1';
		$r->dat_trzby = new \DateTime();
		$r->celk_trzba = 1000;

		return $r;
	}
}