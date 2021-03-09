<?php

namespace QueryManager;

class PrimaryColumn {

	public $table;
	public $name;

	public function __construct(string $table, string $name) {
		$this->table = $table;
		$this->name = $name;
	}

	public function fullname() : string {
		return "{$this->table}.{$this->name}";
	}
}

?>
