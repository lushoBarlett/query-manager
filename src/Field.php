<?php

namespace QueryManager;

class Field {
	public $name;
	public $required = false;
	public $default = false;
	public $default_value;
	public $mappings = [];

	const Int    = "integer";
	const Float  = "double";
	const String = "string";
	const Bool   = "boolean";
	const Object = "object";
	const Array  = "array";
	const Null   = "NULL";

	public function __construct(string $name) {
		$this->name = $name;
	}

	public static function optional(string $name) : self {
		return new self($name);
	}

	public static function default(string $name, $value) : self {
		$field = new self($name);
		$field->default = true;
		$field->default_value = $value;
		return $field;
	}

	public static function required(string $name) : self {
		$field = new self($name);
		$field->required = true;
		return $field;
	}

	public function map(callable $f) : self {
		$this->mappings[] = $f;
		return $this;
	}

	public function replace(array $rules) : self {
		$this->mappings[] = function($value) use ($rules) {
			return $rules[$value] ?? $value;
		};
		return $this;
	}

	public function type(string $type) : self {
		$this->mappings[] = function($value) use ($type) {
			if ($type == gettype($value))
				return $value;
			throw new \Exception("Incorrect type of field '$this->name'");
		};
		return $this;
	}

	public function instance(string $class) : self {
		$this->mappings[] = function($value) use ($class) {
			if ($value instanceof $class)
				return $value;
			throw new \Exception("Incorrect class of field '$this->name'");
		};
		return $this;
	}

	public function cast(string $type) : self {
		$this->mappings[] = function($value) use ($type) {
			set_error_handler(function($e) use($type) {
				throw new \Exception("Error casting field '$this->name' to type '$type'");
			});
			settype($value, $type);
			restore_error_handler();

			return $value;
		};
		return $this;
	}

	public function in(array $options) : self {
		$this->mappings[] = function($value) use ($options) {
			if (in_array($value, $options, true))
				return $value;
			throw new Exception("Value not found in supplied options");
		};
		return $this;
	}

	public function pipeline($value) {
		foreach ($this->mappings as $f)
			$value = $f($value);

		return $value;
	}

	public static function lift($value) : self {
		return is_string($value) ? self::optional($value) : $value;
	}
}

?>