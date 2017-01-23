<?php

namespace Po1nt\EET;

class ReceiptData {
	
	/** @var string */
	protected $name;
	/** @var string */
	protected $value;
	/** @var mixed[] */
	protected $validations = [];
	
	public function __construct($name, $default = null, $validations = false) {
		$this->name = $name;
		$this->value = $default;
		
		if($validations) { // Put to array if not array
			if(!is_array($validations)) {
				$this->validations = [$validations];
			} else {
				$this->validations = $validations;
			}
		}
	}
	
	/**
	 * @return boolean
	 */
	protected function __validate() {
		$ret = true;
		foreach($this->validations as $validation) {
			$valid = true;
			if(is_string($validation)) {
				$valid = preg_match($validation, $this->value) !== false;
			} else {
				if(is_callable($validation)) {
					$valid = $validation($this->value);
				}
			}
			if(!$valid) {
				$ret = false;
				break;
			}
		}
		
		return $ret;
	}
	
	/**
	 * Validates and sets value. If value not valid returns false, otherwise true.
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public function setValue($value) {
		if($this->__validate()) {
			$this->value = $value;
			
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}