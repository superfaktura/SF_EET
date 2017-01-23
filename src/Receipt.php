<?php

namespace Po1nt\EET;

use \DateTime;
use Po1nt\EET\Exceptions\ClientException;

/**
 * Receipt for Ministry of Finance
 */
class Receipt {

	/**
	 * @var ReceiptData[] $data
	 */
	protected $data;
	
	public function __get($key) {
		if(!isset($this->data[$key])) {
			throw new ClientException("Couldn't find such data");
		}
		
		$data = $this->data[$key];
		
		return $data->getValue();
	}
	
	public function __set($key, $value) {
		if($key == 'data' && is_array($value)) {
			$this->data = $value;
			
			return;
		}
		
		if(!isset($this->data[$key])) {
			throw new ClientException("Data key " . $key . " is not documented, therefore couldn't be set");
		}
		
		$data = $this->data[$key];
		$valid = $data->setValue($value);
		if(!$valid) {
			throw new ClientException("Data value '" . $value . "' is not valid for key '" . $key . "'");
		}
	}
	
	private function fillDefault() {
		$data = [];
		/** By documentation 3.3.3.1 */
		$data['uuid_zpravy'] = new ReceiptData('uuid_zpravy', null, '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fAF]{3}-[0-9a-fA-F]{12}$/');
		/** By documentation 3.3.3.3 */
		$data['prvni_zaslani'] = new ReceiptData('prvni_zaslani', true, function($val) {
			return is_bool($val) || preg_match('/^[01]$/', $val);
		});
		/** By documentation 3.3.3.5 */
		$data['dic_popl'] = new ReceiptData('dic_popl', null, '/^CZ[0-9]{8,10}$/');
		/** By documentation 3.3.3.6 */
		$data['dic_poverujiciho'] = new ReceiptData('dic_poverujiciho', null, '/^CZ[0-9]{8,10}$/');
		/** By documentation 3.3.3.7 */
		$data['id_provoz'] = new ReceiptData('id_provoz', null, '/^[1-9][0-9]{0,5}$/');
		/** By documentation 3.3.3.8 */
		$data['id_pokl'] = new ReceiptData('id_pokl', null, '/^[0-9a-zA-Z\.,:;\/#\-_ ]{1,20}$/');
		/** By documentation 3.3.3.9 */
		$data['porad_cis'] = new ReceiptData('porad_cis', null, '/^[0-9a-zA-Z\.,:;\/#\-_ ]{1,25}$/');
		/** By documentation 3.3.3.10 */
		$data['dat_trzby'] = new ReceiptData('dat_trzby', (new DateTime())->format(DateTime::W3C), '/^(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})$/');
		/** By documentation 3.3.3.11 */
		$fin_validations = [
			'/^((0|-?[1-9]\d{0,7})\.\d\d|-0\.(0[1-9]|[1-9]\d))$/',
			function($val) {
				return $val > -100000000 && $val < 100000000;
			},
		];
		$data['celk_trzba'] = new ReceiptData('celk_trzba', 0, $fin_validations);
		$data['zakl_nepodl_dph'] = new ReceiptData('zakl_nepodl_dph', 0, $fin_validations);
		$data['zakl_dan1'] = new ReceiptData('zakl_dan1', 0, $fin_validations);
		$data['dan1'] = new ReceiptData('dan1', 0, $fin_validations);
		$data['zakl_dan2'] = new ReceiptData('zakl_dan2', 0, $fin_validations);
		$data['dan2'] = new ReceiptData('dan2', 0, $fin_validations);
		$data['zakl_dan3'] = new ReceiptData('zakl_dan3', 0, $fin_validations);
		$data['dan3'] = new ReceiptData('dan3', 0, $fin_validations);
		$data['cest_sluz'] = new ReceiptData('cest_sluz', 0, $fin_validations);
		$data['pouzit_zboz1'] = new ReceiptData('pouzit_zboz1', 0, $fin_validations);
		$data['pouzit_zboz2'] = new ReceiptData('pouzit_zboz2', 0, $fin_validations);
		$data['pouzit_zboz3'] = new ReceiptData('pouzit_zboz3', 0, $fin_validations);
		$data['urceno_cerp_zuct'] = new ReceiptData('urceno_cerp_zuct', 0, $fin_validations);
		$data['cerp_zuct'] = new ReceiptData('cerp_zuct', 0, $fin_validations);
		/** By documentation 3.3.3.12 */
		$data['rezim'] = new ReceiptData('rezim', 0, '/^[01]$/');
		
		$this->data = $data;
	}

	/**
	 * Receipt constructor.
	 *
	 * @param array $data
	 */
	public function __construct($data = []) {
		$this->fillDefault();
		
		foreach($data as $k => $v) {
			$this->k = $v;
		}
	}
}
