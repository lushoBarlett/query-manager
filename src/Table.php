<?php

namespace QueryManager;

use QueryManager\QueryPiece as QP;

class Table {

	const INHERIT = '@inherit';

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

	public function select(IConnection $conn, QP $extra = null) {
		$s = QP::Select("{$this->selectf} FROM {$this->fullname($conn)}");
		return $conn->execute($extra ? QP::merge($s, $extra) : $s);
	}

	public function insert(IConnection $conn, $data, QP $extra = null) {
		$qmarks = function(int $n) {
			return rtrim(str_repeat('?,',$n), ',');
		};

		$formatted = $this->insertf->as_array($data);
		$i = QP::InsertInto(
			"{$this->fullname($conn)} (" .
			implode(',', array_keys($formatted)) .
			") VALUES (" .
			$qmarks(count($formatted)) .
			")",
			...array_values($formatted)
		);
		return $conn->execute($extra ? QP::merge($i, $extra) : $i);
	}

	public function update(IConnection $conn, $data, QP $extra = null) {
		$set = function(array $formatted) {
			$s = [];
			foreach ($formatted as $key => $value)
				$s[] = "{$key} = ?";
			return implode(',', $s);
		};

		$formatted = $this->updatef->as_array($data);
		$u = QP::Update(
			"{$this->fullname($conn)} SET " .
			$set($formatted),
			...array_values($formatted)
		);
		return $conn->execute($extra ? QP::merge($u, $extra) : $u);
	}

	public function delete(IConnection $conn, QP $extra = null) {
		$d = QP::DeleteFrom($this->fullname($conn));
		return $conn->execute($extra ? QP::merge($d, $extra) : $d);
	}

	public function fullname(?IConnection $conn = null) : string {
		if ($this->database === self::INHERIT) {
			if (!$conn)
				throw new \Exception("Connection not supplied to fetch database name for a table set to inherit");
			if (!$conn->db_name())
				throw new \Exception("Database not selected in supplied Connection");
			return $conn->inherit_db($this->tablename);
		}
		return "{$this->database}.{$this->tablename}";
	}
}

?>
