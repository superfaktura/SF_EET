<?php

namespace Po1nt\EET\Tests;

use PHPUnit\Framework\TestCase;
use Po1nt\EET\Receipt;

/**
 * Tests for receipt classes
 * @package Po1nt\EET\Tests
 */
class ReceiptTest extends TestCase {

	/**
	 * @return Receipt
	 */
	public function testCreateReceipt() {
		$receipt = new Receipt();
		$receipt->uuid_zpravy = 'b3a09b52-7c87-4014-a496-4c7a53cf9120';
		$receipt->dic_popl = 'CZ1212121218';
		$receipt->id_provoz = '181';
		$receipt->id_pokl = '1';
		$receipt->porad_cis = '1';
		$receipt->dat_trzby = new \DateTime();
		$receipt->celk_trzba = 1000;

		return $receipt;
	}

	/**
	 * @expectedException \Po1nt\EET\Exceptions\ReceiptDataException
	 */
	public function testInvalidDataException() {
		$receipt = new Receipt();
		$receipt->uuid_zpravy = false;
	}
}
