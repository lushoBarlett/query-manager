<?php

namespace QueryManager;

class Formatter {

	public $keys = [];

	/**
	 * A Mapping is a function that takes the data [key => value]
	 * and applies a function to it
	 */
	private $mappings = [];

	public function __construct() {
		$this->keys = func_get_args();
	}

	// MAPPINGS //
	private function value_mapping(string $key, callable $f) : callable {
		return function(array &$data) use ($key, $f) {
			if (array_key_exists($key, $data))
				$data[$key] = $f($data[$key]);
		};
	}

	private function default_mapping(string $key, $value) : callable {
		return function(array &$data) use ($key, $value) {
			if (!array_key_exists($key, $data))
				$data[$key] = $value;
		};
	}

	private function require_mapping(string $key) : callable {
		return function(array &$data) use ($key) {
			if (!array_key_exists($key, $data))
				throw new \Exception("Required field '$key' not found");
		};
	}

	// MAPPING DECLARATORS //
	public function add_default(string $key, $default = null) : void {
		$this->mappings[] = $this->default_mapping($key, $default);
	}

	public function require(string $key) : void {
		$this->mappings[] = $this->require_mapping($key);
	}

	public function map(string $key, callable $lambda) : void {
		$this->mappings[] = $this->value_mapping($key, $lambda);
	}

	public function replace(string $key, array $rules) : void {
		$this->mappings[] = $this->value_mapping($key,
			function($value) use ($rules) {
				return (array_key_exists($value, $rules)) ? $rules[$value] : $value;
			}
		);
	}

	public function restrict_type(string $key, string $type) : void {
		$this->mappings[] = $this->value_mapping($key,
			function($value) use ($key, $type) {
				     if ($type == "int")   $type = "integer";
				else if ($type == "float") $type = "double";
				else if ($type == "null")  $type = "NULL";
				else if ($type == "bool")  $type = "boolean";

				if ($type == gettype($value))
					return $value;
				else
					throw new \Exception("Incorrect type of field '$key'");
			}
		);
	}

	public function restrict_type_cast(string $key, string $type) : void {
		$this->mappings[] = $this->value_mapping($key,
			function($value) use ($key, $type) {
				set_error_handler(function($e) use($key, $type) {
					throw new \Exception("Error casting field '$key' to type '$type'");
				});
				settype($value, $type);
				restore_error_handler();

				return $value;
			}
		);
	}

	public function as_array($data) : array {
		if (is_scalar($data))
			throw \Exception("Can't format scalar");

		if (is_object($data))
			$data = (array)$data;

		foreach($this->keys as $key)
			if (array_key_exists($key, $data))
				$formatted[$key] = $data[$key];

		foreach($this->mappings as $mapping)
			$mapping($formatted);

		return $formatted;
	}
	
	public function as_object($data) : object {
		return (object)$this->as_array($data);
	}
}

?>
