<?php

namespace Po1nt\EET;

use Po1nt\EET\Exceptions\ClientException;
use DateTime;

/**
 * Rozdelenie PKCS#12 na klúč a certifikát
 */
class Certificate {
	
	/** @var string */
	protected $privateKey;
	/** @var string */
	protected $certificate;

	/** @var string */
	protected $hash;
	/** @var DateTime */
	protected $validFrom;
	/** @var DateTime */
	protected $validTo;
	
	/**
	 * @return string
	 */
	public function getKey() {
		return $this->privateKey;
	}
	
	/**
	 * @return string
	 */
	public function getCert() {
		return $this->certificate;
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @return DateTime
	 */
	public function getValidFrom() {
		return $this->validFrom;
	}

	/**
	 * @return DateTime
	 */
	public function getValidTo() {
		return $this->validTo;
	}

	/**
	 * Certificate constructor
	 *
	 * @param string $pkcs12
	 * @param string $password
	 *
	 * @throws ClientException
	 */
	public function __construct($pkcs12, $password) {
		$this->checkRequirements();
		
		if(empty($pkcs12)) {
			throw new ClientException("Certificate is empty");
		}

		if(empty($password)) {
			throw new ClientException("Certificate password is empty");
		}
		
		$certs = $this->splitPkcs12($pkcs12, $password);

		$meta = openssl_x509_parse($certs['cert']);

		$this->hash = $meta['hash'];
		$this->validFrom = date_create_from_format('ymdHise', $meta['validFrom']);
		$this->validTo = date_create_from_format('ymdHise', $meta['validTo']);

		$this->privateKey = $certs['pkey'];
		$this->certificate = $certs['cert'];
	}
	
	/**
	 * @param string $pkcs12
	 * @param string $password
	 *
	 * @return string[]
	 * @throws ClientException
	 */
	protected function splitPkcs12($pkcs12, $password) {
		$certs = [];
		$success = openssl_pkcs12_read($pkcs12, $certs, $password);
		
		if(!$success) {
			throw new ClientException("Certificate is not valid, and couldn't be split " . substr($pkcs12, 0, 100));
		}
		
		return $certs;
	}
	
	/**
	 * @throws ClientException
	 */
	private function checkRequirements() {
		if(!extension_loaded('openssl') || !function_exists('openssl_pkcs12_read')) {
			throw new ClientException("OpenSSL extension not available");
		}
	}
}