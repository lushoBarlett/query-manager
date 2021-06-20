<?php

namespace QueryManager;

class Formatter {

	public $fields = [];

	public function __construct(...$args) {
		foreach ($args as $k => $field)
			$args[$k] = Field::lift($field);

		$this->fields = $args;
	}

	private function apply_rules($data) : array {
		$formatted = [];

		foreach($this->fields as $field) {
			if ($field->required && !array_key_exists($field->name, $data))
				throw new \Exception("Field '$field->name' is required");

			if ($field->default && !array_key_exists($field->name, $data))
				$data[$field->name] = $field->default_value;
				
			if (array_key_exists($field->name, $data))
				$formatted[$field->name] = $field->pipeline($data[$field->name]);
		}

		return $formatted;
	}
	
	public function format($data) {
		if (is_scalar($data))
			throw new \Exception("Can't format scalar");

		if (is_array($data))
			return $this->apply_rules($data);

		if (is_object($data))
			return (object)$this->apply_rules((array)$data);
	}
}

?>
