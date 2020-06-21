<?php

namespace QueryManager;

class Table {
	
	private $database;
	private $tablename;
	private $selectf;
	private $insertf;
	private $updatef;
	
	protected function __construct(
		string $database,
		string $tablename,
		Formatter $selectf,
		Formatter $insertf,
		Formatter $updatef
	) {
		$this->database = $database;
		$this->tablename = $tablename;
		$this->selectf = implode(',', $selectf->keys);
		$this->insertf = $insertf;
		$this->updatef = $updatef;
	}

	public function select(IConnection $connection, QueryPiece $extra = null) {
		$s = new QueryPiece("SELECT {$this->selectf} FROM {$this->fullname()}");
		return $connection->execute($extra ? QueryPiece::merge($s, $extra) : $s);
	}

	public function insert(IConnection $connection, $data, QueryPiece $extra = null) {
		$qmarks = function(int $n) {
			return rtrim(str_repeat('?,',$n), ',');
		};

		$formatted = $this->insertf->as_array($data);
		$i = new QueryPiece(
			"INSERT INTO {$this->fullname()} (" .
			implode(',', array_keys($formatted)) .
			") VALUES (" .
			$qmarks(count($formatted)) .
			")",
			...array_values($formatted)
		);
		return $connection->execute($extra ? QueryPiece::merge($i, $extra) : $i);
	}

	public function update(IConnection $connection, $data, QueryPiece $extra = null) {
		$set = function(array $formatted) {
			$s = [];
			foreach ($formatted as $key => $value)
				$s[] = "{$key} = ?";
			return implode(',', $s);
		};

		$formatted = $this->updatef->as_array($data);
		$u = new QueryPiece(
			"UPDATE {$this->fullname()} SET " .
			$set($formatted),
			...array_values($formatted)
		);
		return $connection->execute($extra ? QueryPiece::merge($u, $extra) : $u);
	}

	public function delete(IConnection $connection, QueryPiece $extra = null) {
		$d = new QueryPiece("DELETE FROM {$this->fullname()}");
		return $connection->execute($extra ? QueryPiece::merge($d, $extra) : $d);
	}

	private function fullname() : string {
		return "{$this->database}.{$this->tablename}";
	}
}

?>
