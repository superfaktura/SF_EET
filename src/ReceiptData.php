<?php

namespace Po1nt\EET;

/**
 * Each data value of Receipt
 *
 * @package Po1nt\EET
 */
class ReceiptData {
	
	/** @var string */
	protected $name;
	/** @var string */
	protected $value;
	/** @var mixed[] */
	protected $validations = [];

	/**
	 * ReceiptData constructor.
	 *
	 * @param string $name
	 * @param string|float|int|\DateTime $default
	 * @param mixed[]|bool $validations
	 */
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

				if(!is_string($this->value) && !is_numeric($this->value)) {
					if($this->value instanceof \DateTime) {
						$value = $this->value->format(\DateTime::W3C);
					} else {
						return false; // Unsupported data type
					}
				} else {
					$value = $this->value;
				}

				$valid = preg_match($validation, $value) !== false;
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
		$this->value = $value;
		return $this->__validate();
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