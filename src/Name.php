<?php

namespace QueryManager;

class Name {

	public $db;
	public $table;
	public $column;

	public function __construct($db = null, $table = null, $column = null) {
		$this->db = $db;
		$this->table = $table;
		$this->column = $column;
	}

	public static function make() : self {
		return new Name;
	}

	public static function stringify(...$objects) {
		foreach ($objects as &$obj) {
			if ($obj instanceof IConnection)
				$obj = $obj->db_name();

			// NOTE: can't quote *. Also, Column has __toString
			if ($obj !== Column::ALL)
				$obj = "`$obj`";
		}
		
		return implode('.', $objects);
	}

	public function __toString() : string {
		$objects = [];
		if ($this->db)
			$objects[] = $this->db;
		if ($this->table)
			$objects[] = $this->table;
		if ($this->column)
			$objects[] = $this->column;

		return self::stringify(...$objects);
	}

	public function db($db) : self {
		$this->db = $db;
		return $this;
	}

	public function table($table) : self {
		$this->table = $table;
		return $this;
	}

	public function column($column) : self {
		$this->column = $column;
		return $this;
	}
}

?>