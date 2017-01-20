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
	 * @param string $certificate
	 * @param string $password
	 *
	 * @throws ClientException
	 */
	public function __construct($certificate, $password) {
		if(!extension_loaded('openssl') || !function_exists('openssl_pkcs12_read')) {
			throw new ClientException("OpenSSL extension not available");
		}
		
		if(!file_exists($certificate)) {
			throw new ClientException("Certificate has not been found");
		}
		
		if(empty($password)) {
			throw new ClientException("Certificate password is empty");
		}
		
		$pkcs12 = file_get_contents($certificate);
		
		$certs = [];
		$openSSL = openssl_pkcs12_read($pkcs12, $certs, $password);
		
		if(!$openSSL) {
			throw new ClientException("Certificate couldn't be exported");
		}
		
		$this->privateKey = $certs['pkey'];
		$this->certificate = $certs['cert'];
	}
}