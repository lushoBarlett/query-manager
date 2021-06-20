<?php

namespace QueryManager;

class TestConnection implements IConnection {
	public $qps = [];
	public function __construct(string $s = "", string $usr = "", string $psw = "", string $db = "") { $this->db = $db; }
	public function execute(QueryPiece $qp) : ?array {
		$this->qps[] = $qp;
		return $this->qps;
	}
	public function db_name() : string { return $this->db; }
	public function last_insert_id() {}
	public function transaction() : void {}
	public function rollback() : void {}
	public function commit() : void {}
	public function errors() : object { return new stdClass; }
}

?>