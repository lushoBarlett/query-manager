<?php

namespace QueryManager;

class Table {
	
	private $this->database;
	private $this->tablename;
	private $this->selectf;
	private $this->insertf;
	private $this->updatef;
	
	public function __construct(
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

	protected function select(Connection $connection, QueryPiece $extra = null) {
		return $connection->execute(
			QueryPiece::merge(
				new QueryPiece("SELECT {$this->select} FROM {$this->fullname}"),
				$this->default_qp($extra)
			)
		);
	}

	protected function insert(Connection $connection, $data, QueryPiece $extra = null) {
		function qmarks(int $n) {
			return implode(',', str_repeat('?',$n));
		}

		return $connection->execute(
			QueryPiece::merge(
				new QueryPiece(
					"INSERT INTO {$this->fullname} (" .
					implode(',', $this->insertf->keys) .
					") VALUES (" .
					qmarks(count($this->insertf->keys)) .
					")",
					$this->insertf->as_array($data)
				),
				$this->default_qp($extra)
			)
		);
	}

	protected function update(Connection $connection, $data, QueryPiece $extra = null) {
		function set(array $keys) {
			$s = [];
			foreach ($keys as $key)
				$s[] = "{$key} = ?";
			return implode(',', $s);
		}

		return $connection->execute(
			QueryPiece::merge(
				new QueryPiece(
					"UPDATE {$this->fullname} SET " .
					set($this->updatef->keys),
					$this->updatef->as_array($data)
				),
				$this->default_qp($extra)
			)
		);
	}

	protected function delete(Connection $connection, QueryPiece $extra = null) {
		return $connection->execute(
			QueryPiece::merge(
				new QueryPiece("DELETE FROM {$this->fullname}"),
				$this->default_qp($extra)
			)
		);
	}

	private function fullname() : QueryPiece {
		return "{$this->database}.{$this->tablename}";
	}

	private function default_qp($qp) {
		if ($qp === null)
			return new QueryPiece();
		return $qp;
	}
}

?>
