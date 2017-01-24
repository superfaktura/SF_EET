<?php

namespace Po1nt\EET;

use Po1nt\EET\Exceptions\ClientException;

/**
 * Class for loading certificate from File
 * @package Po1nt\EET
 */
class FileCertificate extends Certificate {
	
	/**
	 * @param string $file File path
	 * @param string $password
	 *
	 * @throws ClientException
	 */
	public function __construct($file, $password) {
		if(!file_exists($file)) {
			throw new ClientException("Certificate has not been found");
		}
		
		$pkcs12 = file_get_contents($file);
		
		parent::__construct($pkcs12, $password);
	}
}