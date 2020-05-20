<?php

namespace QueryManager;

class Connection {

	public function __construct($server, $user, $password, $database = "") {
		$this->db = new mysqli($server, $user, $password, $database);

		if ($this->db->connect_errno)
			throw \Exception($this->db->connect_error);

		$this->transaction();
	}
	
	private function prepare(string $query) : mysqli_stmt {
		if($statement = $this->db->prepare($query))
			return $statement;
		else
			throw new \Exception($this->db->error);
	}

	public function execute(QueryPiece $qp) : mysqli_result {
		$statement = $this->prepare($qp->template);

		// bind N strings, mysql can cast the values if necessary
		array_map(function($v) { return (string)$v; }, $qp->fragments);
		
		if (count($params) > 0)
			$statement->bind_param(
				str_repeat("s", count($params)),
				...$params
			);

		if($statement->execute())
			return $statement->get_result();
		else
			throw new \Exception($statement->error);
	}

	public function transaction() {
		if ($this->db->begin_transaction() === false)
			throw new \Exception($this->db->error);
	}

	public function rollback() {
		if ($this->db->rollback() === false)
			throw new \Exception($this->db->error);
	}

	public function commit() {
		if ($this->db->commit() === false)
			throw new \Exception($this->db->error);
	}

	public function __destruct() {
		$this->commit();
		$this->db->close();
	}
}

?>
