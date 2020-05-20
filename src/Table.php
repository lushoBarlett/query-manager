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
		array $select,
		Formatter $insertf,
		Formatter $updatef
	) {
		$this->database = $database;
		$this->tablename = $tablename;
		$this->select = implode(',',$select);
		$this->insertf = $insertf;
		$this->updatef = $updatef;
	}

	public function select(Connection $connection, QueryPiece $extra = null) {
		$s = new QueryPiece("SELECT {$this->select} FROM {$this->fullname()}");
		return $connection->execute($extra ? QueryPiece::merge($s, $extra) : $s);
	}

	public function insert(Connection $connection, $data, QueryPiece $extra = null) {
		function qmarks(int $n) {
			return rtrim(',', str_repeat('?,',$n));
		}

		$i = new QueryPiece(
			"INSERT INTO {$this->fullname()} (" .
			implode(',', $this->insertf->keys) .
			") VALUES (" .
			qmarks(count($this->insertf->keys)) .
			")",
			$this->insertf->as_array($data)
		);
		return $connection->execute($extra ? QueryPiece::merge($i, $extra) : $i);
	}

	public function update(Connection $connection, $data, QueryPiece $extra = null) {
		function set(array $keys) {
			$s = [];
			foreach ($keys as $key)
				$s[] = "{$key} = ?";
			return implode(',', $s);
		}

		$u = new QueryPiece(
			"UPDATE {$this->fullname()} SET " .
			set($this->updatef->keys),
			$this->updatef->as_array($data)
		);
		return $connection->execute($extra ? QueryPiece::merge($u, $extra) : $u);
	}

	public function delete(Connection $connection, QueryPiece $extra = null) {
		$d = new QueryPiece("DELETE FROM {$this->fullname()}");
		return $connection->execute($extra ? QueryPiece::merge($d, $extra) : $d);
	}

	private function fullname() : string {
		return "{$this->database}.{$this->tablename}";
	}
}

?>
