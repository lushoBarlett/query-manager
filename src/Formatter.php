<?php

namespace QueryManager;

class Formatter {

	public $keys = [];
	public $defaults = [];

	public function __construct() {
		$this->keys = func_get_args();
	}

	public function add_default(string $key, $default = null) {
		$this->defaults[$key] = $default;
	}

	public function as_array($data) : array {
		$formatted = $this->defaults;

		if (is_array($data)) {
			foreach($this->keys as $key)
				if (array_key_exists($key, $data))
					$formatted[$key] = $data[$key];
		}
				
		else if (is_object($data)) {
			foreach($this->keys as $key)
				if (property_exists($key, $data))
					$formatted[$key] = $data->{$key};
		}
				
		else {
			throw \Exception("Can't format scalar");
		}
		
		return $formatted;
	}
	
	public function as_object($data) : object {
		return (object)$this->as_array($data);
	}
}

?>
