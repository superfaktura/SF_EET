<?php

namespace Po1nt\EET;

use Po1nt\EET\Exceptions\ClientException;
use Po1nt\EET\Certificate;
use RobRichards\WsePhp\WSSESoap;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SoapClient extends \SoapClient {

	/** @var string */
	private $key;
	/** @var Certificate */
	private $certificate;
	/** @var boolean */
	private $traceRequired;
	/** @var float */
	private $connectionStartTime;
	/** @var float */
	private $lastResponseStartTime;
	/** @var float */
	private $lastResponseEndTime;
	/** @var string */
	private $lastRequest;
	private $returnRequest = false;
	/**
	 * @var int timeout in milliseconds
	 */
	private $timeout = 2500;
	/**
	 * @var int connection timeout in milliseconds
	 */
	private $connectTimeout = 2000;

	/**
	 *
	 * @param string      $service
	 * @param Certificate $certificate
	 * @param boolean     $trace
	 */
	public function __construct($service, $certificate, $trace = false) {
		$this->connectionStartTime = microtime(true);
		parent::__construct($service, [
			'exceptions' => true,
			'trace'      => $trace,
		]);
		$this->certificate = $certificate;
		$this->traceRequired = $trace;
	}

	public function getXMLforMethod($method, $data) {
		$this->returnRequest = true;
		$this->$method($data);
		$this->returnRequest = false;

		return $this->lastRequest;
	}

	public function __doRequest($request, $location, $saction, $version, $one_way = null) {

		$xml = $this->getXML($request);
		$this->lastRequest = $xml;
		if($this->returnRequest) {
			return '';
		}

		$this->traceRequired && $this->lastResponseStartTime = microtime(true);

		$response = $this->__doRequestByCurl($xml, $location, $saction, $version);

		$this->traceRequired && $this->lastResponseEndTime = microtime(true);

		return $response;
	}

	public function getXML($request) {

		$doc = new \DOMDocument('1.0');
		$doc->loadXML($request);

		$objWSSE = new WSSESoap($doc);
		$objWSSE->addTimestamp();

		$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
		$objKey->loadKey($this->certificate->getKey());
		$objWSSE->signSoapDoc($objKey, ["algorithm" => XMLSecurityDSig::SHA256]);

		$token = $objWSSE->addBinaryToken($this->certificate->getCert());
		$objWSSE->attachTokentoSig($token);
		
		return $objWSSE->saveXML();
	}

	/**
	 * @param string $request
	 * @param string $location
	 * @param string $action
	 * @param int    $version
	 * @param bool   $one_way
	 *
	 * @return string|null
	 * @throws ClientException
	 */
	public function __doRequestByCurl($request, $location, $action, $version, $one_way = false) {
		// Call via Curl and use the timeout a
		$curl = curl_init($location);
		if($curl === false) {
			throw new ClientException('Curl initialisation failed');
		}
		/** @var $headers array of headers to be sent with request */
		$headers = [
			'User-Agent: PHP-SOAP',
			'Content-Type: text/xml; charset=utf-8',
			'SOAPAction: "' . $action . '"',
			'Content-Length: ' . strlen($request),
		];
		$options = [
			CURLOPT_VERBOSE        => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $request,
			CURLOPT_HEADER         => $headers,
			CURLOPT_HTTPHEADER     => [
				sprintf('Content-Type: %s', $version == 2? 'application/soap+xml' : 'text/xml'),
				sprintf('SOAPAction: %s', $action),
			],
		];
		// Timeout in milliseconds
		$options = $this->__curlSetTimeoutOption($options, $this->timeout, 'CURLOPT_TIMEOUT');
		// ConnectTimeout in milliseconds
		$options = $this->__curlSetTimeoutOption($options, $this->connectTimeout, 'CURLOPT_CONNECTTIMEOUT');

		$this->__setCurlOptions($curl, $options);
		$response = curl_exec($curl);

		if(curl_errno($curl)) {
			$errorMessage = curl_error($curl);
			$errorNumber = curl_errno($curl);
			curl_close($curl);
			throw new ClientException($errorMessage, $errorNumber);
		}

		$header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_len);
		$body = substr($response, $header_len);

		curl_close($curl);
		// Return?
		if($one_way) {
			return null;
		} else {
			return $body;
		}
	}

	private function __curlSetTimeoutOption($options, $milliseconds, $name) {
		if($milliseconds > 0) {
			if(defined("{$name}_MS")) {
				$options[constant("{$name}_MS")] = $milliseconds;
			} else {
				$seconds = ceil($milliseconds / 1000);
				$options[$name] = $seconds;
			}
			if($milliseconds <= 1000) {
				$options[CURLOPT_NOSIGNAL] = 1;
			}
		}

		return $options;
	}

	private function __setCurlOptions($curl, array $options) {
		foreach($options as $option => $value) {
			if(false !== curl_setopt($curl, $option, $value)) {
				continue;
			}
			throw new ClientException(sprintf('Failed setting CURL option %d (%s) to %s', $option, $this->__getCurlOptionName($option), var_export($value, true)));
		}
	}

	/**
	 *
	 * @return float
	 */
	public function __getLastResponseTime() {
		return $this->lastResponseEndTime - $this->lastResponseStartTime;
	}

	/**
	 *
	 * @return float
	 */
	public function __getConnectionTime($tillLastRequest = false) {
		return $tillLastRequest? $this->getConnectionTimeTillLastRequest() : $this->getConnectionTimeTillNow();
	}

	private function getConnectionTimeTillLastRequest() {
		if(!$this->lastResponseEndTime || !$this->connectionStartTime) {
			return null;
		}

		return $this->lastResponseEndTime - $this->connectionStartTime;
	}

	private function getConnectionTimeTillNow() {
		if(!$this->connectionStartTime) {
			return null;
		}

		return microtime(true) - $this->connectionStartTime;
	}

	/**
	 * @return string
	 */
	public function __getLastRequest() {
		return $this->lastRequest;
	}

	/**
	 * @return int|null timeout in milliseconds
	 */
	public function getTimeout() {
		return $this->timeout;
	}

	/**
	 * @param int|null $milliseconds timeout in milliseconds
	 */
	public function setTimeout($milliseconds) {
		$this->timeout = $milliseconds;
	}

	/**
	 * @return int|null
	 */
	public function getConnectTimeout() {
		return $this->connectTimeout;
	}

	/**
	 * @param int|null $milliseconds
	 */
	public function setConnectTimeout($milliseconds) {
		$this->connectTimeout = $milliseconds;
	}
}
