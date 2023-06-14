<?php // declare(strict_types=1);

namespace boctulus\SW\core\libs;

/*
	@author Pablo Bozzolo
*/

class ValidationRules
{
	protected $rules = [];
	protected $current_field = NULL;

	public function field(string $name){
		$this->current_field = $name; 
		return $this;
	}

	// podría recibir una constante
	public function type(string $type, string $error_msg = null){
		$this->rules[$this->current_field]['type'] = $type;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['type'] = $error_msg;
		}

		return $this;
	}

	public function required(bool $status = true, string $error_msg = null){
		$this->rules[$this->current_field]['required'] = $status;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['required'] = $error_msg;
		}

		return $this;
	}

	public function max($val, string $error_msg = null){
		$this->rules[$this->current_field]['max'] = $val;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['max'] = $error_msg;
		}

		return $this;
	}

	public function min($val, string $error_msg = null){
		$this->rules[$this->current_field]['min'] = $val;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['min'] = $error_msg;
		}

		return $this;
	}

	public function between(Array $arr, string $error_msg = null){
		$this->rules[$this->current_field]['between'] = $arr;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['between'] = $error_msg;
		}

		return $this;
	}

	public function notBetween(Array $arr, string $error_msg = null){
		$this->rules[$this->current_field]['not_between'] = $arr;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['not_between'] = $error_msg;
		}

		return $this;
	}

	public function in(Array $arr, string $error_msg = null){
		$this->rules[$this->current_field]['in'] = $arr;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['in'] = $error_msg;
		}

		return $this;
	}

	public function notIn(Array $arr, string $error_msg = null){
		$this->rules[$this->current_field]['not_in'] = $arr;

		if ($error_msg != NULL){
			$this->rules[$this->current_field]['messages']['not_in'] = $error_msg;
		}

		return $this;
	}

	public function getRules(){
		return $this->rules;
	}
}

