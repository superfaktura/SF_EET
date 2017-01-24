<?php

namespace Po1nt\EET;

use Po1nt\EET\Exceptions\ClientException;

/**
 * Rozdelenie PKCS#12 na klúč a certifikát
 */
class Certificate {
	
	/** @var string */
	protected $privateKey;
	/** @var string */
	protected $certificate;
	
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