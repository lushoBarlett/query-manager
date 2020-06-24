<?php

namespace QueryManager;

class Connection implements IConnection {

	private $db;

	public function __construct(string $s, string $usr, string $psw, string $db = "") {
		$this->db = new \mysqli($s, $usr, $psw, $db);

		if ($this->db->connect_errno)
			throw new \Exception($this->db->connect_error);

		$this->transaction();
	}
	
	private function prepare(string $query) : \mysqli_stmt {
		if($statement = $this->db->prepare($query))
			return $statement;
		else
			throw new \Exception($this->db->error);
	}

	public function execute(QueryPiece $qp) : ?array {
		$statement = $this->prepare($qp->template);

		// bind N strings, mysql can cast the values if necessary
		// don't cast null
		$params = array_map(
			function($v) { return $v === null ? $v : (string)$v; },
			$qp->fragments
		);
		
		if (count($params) > 0)
			$statement->bind_param(
				str_repeat("s", count($params)),
				...$params
			);

		if($statement->execute()) {
			if ($m = $statement->result_metadata()) {
				$keys = [];
				while($f = $m->fetch_field())
					$keys[] = $f->name;
				$m->close();

				// fill array with null and bind it
				$bindings = array_pad([], count($keys), null);
				$statement->bind_result(...$bindings);

				$dereference = function($v) { return $v; };

				$result = [];
				while($statement->fetch())
					// deep copy of references
					$result[] = (object)array_combine(
						$keys, array_map($dereference, $bindings)
					);

				$statement->close();
				return $result;
			}
		} else {
			throw new \Exception($statement->error);
		}
	}

	public function last_insert_id() {
		return $this->db->insert_id;
	}

	public function transaction() : void {
		if ($this->db->begin_transaction() === false)
			throw new \Exception($this->db->error);
	}

	public function rollback() : void {
		if ($this->db->rollback() === false)
			throw new \Exception($this->db->error);
	}

	public function commit() : void {
		if ($this->db->commit() === false)
			throw new \Exception($this->db->error);
	}

	public function errors() : object {
		return (object)[
			"error" => $this->db->error,
			"error_list" => $this->db->error_list,
			"connect_error" => $this->db->connect_error
		];
	}

	public function __destruct() {
		$this->db->close();
	}
}

?>
