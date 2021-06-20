<?php

namespace QueryManager;

class Name {

	public $db;
	public $table;
	public $column;
	public $alias;

	public function __construct($db = null, $table = null, $column = null, $alias = null) {
		$this->db = $db;
		$this->table = $table;
		$this->column = $column;
		$this->alias = $alias;
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
		
		$name = self::stringify(...$objects);
		if ($this->alias)
			$name .= " AS `$this->alias`";
		return $name;
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

	public function alias($alias) : self {
		$this->alias = $alias;
		return $this;
	}
}

?>