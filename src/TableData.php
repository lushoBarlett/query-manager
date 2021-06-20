<?php

namespace QueryManager;

use QueryManager\Column;

class TableData {
	
	public $db;
	public $name;
	public $columns = [];
	public $insert_formatter;
	public $update_formatter;

	private $primary_index;
	private $foreign_indices = [];

	public function __construct(
		string $db = null,
		string $name = null,
		array $columns = null,
		Formatter $insert_formatter = null,
		Formatter $update_formatter = null
	) {
		$this->db = $db;
		$this->name = $name;
		if ($columns)
			$this->columns(...$columns);
		$this->insert_formatter = $insert_formatter;
		$this->update_formatter = $update_formatter;
	}
	
	public function db(string $db) : self {
		$this->db = $db;
		return $this;
	}

	public function name(string $name) : self {
		$this->name = $name;
		return $this;
	}
	
	public function columns(...$columns) : self {
		foreach ($columns as $k => $column) {
			$columns[$k] = $column = Column::lift($column);

			// TODO: assert when more than one primary?
			if ($column->primary)
				$this->primary_index = $k;

			if ($column->foreign)
				$this->foreign_indices[] = $k;
		}

		$this->columns = $columns;
		return $this;
	}

	public function on_insert(Formatter $format) : self {
		$this->insert_formatter = $format;
		return $this;
	}

	public function on_update(Formatter $format) : self {
		$this->update_formatter = $format;
		return $this;
	}

	public function primary() : ?Column {
		return $this->columns[$this->primary_index] ?? null;
	}

	public function foreign() : array {
		$answer = [];

		foreach ($this->foreign_indices as $index)
			$answer[] = $this->columns[$index];

		return $answer;
	}
}

?>