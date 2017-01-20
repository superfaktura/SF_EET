<?php

namespace Po1nt\EET;

use Po1nt\EET\Exceptions\ClientException;
use Po1nt\EET\Exceptions\RequirementsException;
use Po1nt\EET\Exceptions\ServerException;
use Po1nt\EET\SoapClient;
use Po1nt\EET\Utils\Format;
use Po1nt\EET\Certificate;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Receipt for Ministry of Finance
 */
class Dispatcher {

	/**
	 * Certificate
	 * @var Certificate
	 */
	private $certificate;
	/**
	 * WSDL path or URL
	 * @var string
	 */
	private $service;
	/**
	 * @var boolean
	 */
	public $trace;
	/**
	 *
	 * @var SoapClient
	 */
	private $soapClient;
	/**
	 * @var array [warning code => message]
	 */
	private $warnings;

	/**
	 *
	 * @param string $key
	 * @param string $cert
	 */
	public function __construct($service, $certificate) {
		$this->service = $service;
		$this->certificate = $certificate;
		$this->warnings = [];
		$this->checkRequirements();
	}

	/**
	 *
	 * @param string  $service
	 * @param Receipt $receipt
	 *
	 * @return boolean|string
	 */
	public function check(Receipt $receipt) {
		try {
			return $this->send($receipt, true);
		} catch(ServerException $e) {
			return false;
		}
	}

	/**
	 *
	 * @param boolean $tillLastRequest optional If not set/FALSE connection time till now is returned.
	 *
	 * @return float
	 */
	public function getConnectionTime($tillLastRequest = false) {
		!$this->trace && $this->throwTraceNotEnabled();

		return $this->getSoapClient()->__getConnectionTime($tillLastRequest);
	}

	/**
	 *
	 * @return int
	 */
	public function getLastResponseSize() {
		!$this->trace && $this->throwTraceNotEnabled();

		return mb_strlen($this->getSoapClient()->__getLastResponse(), '8bit');
	}

	/**
	 *
	 * @return int
	 */
	public function getLastRequestSize() {
		!$this->trace && $this->throwTraceNotEnabled();

		return mb_strlen($this->getSoapClient()->__getLastRequest(), '8bit');
	}

	/**
	 *
	 * @return float time in ms
	 */
	public function getLastResponseTime() {
		!$this->trace && $this->throwTraceNotEnabled();

		return $this->getSoapClient()->__getLastResponseTime();
	}

	/**
	 *
	 * @throws ClientException
	 */
	private function throwTraceNotEnabled() {
		throw new ClientException('Trace is not enabled! Set trace property to TRUE.');
	}

	/**
	 *
	 * @param \Ondrejnov\EET\Receipt $receipt
	 *
	 * @return array
	 */
	public function getCheckCodes(Receipt $receipt) {
		$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
		$objKey->loadKey($this->certificate->getKey());

		$arr = [
			$receipt->dic_popl,
			$receipt->id_provoz,
			$receipt->id_pokl,
			$receipt->porad_cis,
			$receipt->dat_trzby->format('c'),
			Format::price($receipt->celk_trzba),
		];
		$sign = $objKey->signData(join('|', $arr));

		return [
			'pkp' => ['_' => $sign, 'digest' => 'SHA256', 'cipher' => 'RSA2048', 'encoding' => 'base64'],
			'bkp' => ['_' => Format::BKB(sha1($sign)), 'digest' => 'SHA1', 'encoding' => 'base16'],
		];
	}

	/**
	 *
	 * @param Receipt $receipt
	 * @param boolean $check
	 *
	 * @return boolean|string
	 */
	public function send(Receipt $receipt, $check = false) {
		$this->initSoapClient();

		$response = $this->processData($receipt, $check);

		isset($response->Chyba) && $this->processError($response->Chyba);
		isset($response->Varovani) && $this->warnings = $this->processWarnings($response->Varovani);

		return $check? true : $response->Potvrzeni->fik;
	}

	/**
	 * Returns array of warnings if the last response contains any, empty array otherwise.
	 *
	 * @return array [warning code => message]
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 *
	 * @throws RequirementsException
	 * @return void
	 */
	private function checkRequirements() {
		if(!class_exists('\SoapClient')) {
			throw new RequirementsException('Class SoapClient is not defined! Please, allow php extension php_soap.dll in php.ini');
		}
	}

	/**
	 * Get (or if not exists: initialize and get) SOAP client.
	 *
	 * @return SoapClient
	 */
	public function getSoapClient() {
		!isset($this->soapClient) && $this->initSoapClient();

		return $this->soapClient;
	}

	/**
	 * Require to initialize a new SOAP client for a new request.
	 *
	 * @return void
	 */
	private function initSoapClient() {
		if($this->soapClient === null) {
			$this->soapClient = new SoapClient($this->service, $this->certificate, $this->trace);
		}
	}

	public function prepareData($receipt, $check = false) {
		$head = [
			'uuid_zpravy'   => $receipt->uuid_zpravy,
			'dat_odesl'     => time(),
			'prvni_zaslani' => $receipt->prvni_zaslani,
			'overeni'       => $check,
		];

		$body = [
			'dic_popl'         => $receipt->dic_popl,
			'dic_poverujiciho' => $receipt->dic_poverujiciho,
			'id_provoz'        => $receipt->id_provoz,
			'id_pokl'          => $receipt->id_pokl,
			'porad_cis'        => $receipt->porad_cis,
			'dat_trzby'        => $receipt->dat_trzby->format('c'),
			'celk_trzba'       => Format::price($receipt->celk_trzba),
			'zakl_nepodl_dph'  => Format::price($receipt->zakl_nepodl_dph),
			'zakl_dan1'        => Format::price($receipt->zakl_dan1),
			'dan1'             => Format::price($receipt->dan1),
			'zakl_dan2'        => Format::price($receipt->zakl_dan2),
			'dan2'             => Format::price($receipt->dan2),
			'zakl_dan3'        => Format::price($receipt->zakl_dan3),
			'dan3'             => Format::price($receipt->dan3),
			'cest_sluz'        => Format::price($receipt->cest_sluz),
			'pouzit_zboz1'     => Format::price($receipt->pouzit_zboz1),
			'pouzit_zboz2'     => Format::price($receipt->pouzit_zboz2),
			'pouzit_zboz3'     => Format::price($receipt->pouzit_zboz3),
			'urceno_cerp_zuct' => Format::price($receipt->urceno_cerp_zuct),
			'cerp_zuct'        => Format::price($receipt->cerp_zuct),
			'rezim'            => $receipt->rezim,
		];

		return ['Hlavicka' => $head, 'Data' => $body, 'KontrolniKody' => $this->getCheckCodes($receipt)];
	}

	/**
	 *
	 * @param Receipt $receipt
	 * @param boolean $check
	 *
	 * @return object
	 */
	private function processData(Receipt $receipt, $check = false) {
		$data = $this->prepareData($receipt, $check);

		return $this->getSoapClient()->OdeslaniTrzby($data);
	}

	/**
	 * @param $error
	 *
	 * @throws ServerException
	 */
	private function processError($error) {
		if($error->kod) {
			$msgs = [
				-1 => 'Docasna technicka chyba zpracovani – odeslete prosim datovou zpravu pozdeji',
				2  => 'Kodovani XML neni platne',
				3  => 'XML zprava nevyhovela kontrole XML schematu',
				4  => 'Neplatny podpis SOAP zpravy',
				5  => 'Neplatny kontrolni bezpecnostni kod poplatnika (BKP)',
				6  => 'DIC poplatnika ma chybnou strukturu',
				7  => 'Datova zprava je prilis velka',
				8  => 'Datova zprava nebyla zpracovana kvuli technicke chybe nebo chybe dat',
			];
			$msg = isset($msgs[$error->kod])? $msgs[$error->kod] : '';
			throw new ServerException($msg, $error->kod);
		}
	}

	/**
	 * @param \stdClass $warnings
	 *
	 * @return array [warning code => message]
	 */
	private function processWarnings($warnings) {
		$result = [];
		foreach($warnings as $warning) {
			$result[\intval($warning->kod_varov)] = $this->getWarningMsg($warning->kod_varov);
		}

		return $result;
	}

	/**
	 * @param int $id warning code
	 *
	 * @return string warning message
	 */
	private function getWarningMsg($id) {
		$result = 'Nezname varovani, zkontrolujte technickou specifikaci';
		$msgs = [
			1 => 'DIC poplatnika v datove zprave se neshoduje s DIC v certifikatu',
			2 => 'Chybny format DIC poverujiciho poplatnika',
			3 => 'Chybna hodnota PKP',
			4 => 'Datum a cas prijeti trzby je novejsi nez datum a cas prijeti zpravy',
			5 => 'Datum a cas prijeti trzby je vyrazne v minulosti',
		];
		if(\array_key_exists($id, $msgs)) {
			$result = $msgs[$id];
		}

		return $result;
	}
}
