<?php

namespace QueryManager;

class Column {

	const ALL = "*";

	public $name;
	public $primary = false;
	public $unique = false;
	public $foreign = null;

	public function __construct($name) {
		$this->name = $name;
	}

	public static function name($name) : self {
		return new self($name);
	}

	public function primary() : self {
		$this->primary = true;
		$this->unique = true;
		return $this;
	}

	public function unique() : self {
		$this->unique = true;
		return $this;
	}

	public function foreign(Name $foreign) : self {
		$this->foreign = $foreign;
		return $this;
	}

	public function __toString() : string {
		return $this->name;
	}

	public static function serialize(array $columns) : string {
		return implode(',', $columns);
	}
}

?>