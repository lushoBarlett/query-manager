<?php

namespace QueryManager;

class TestConnection implements IConnection {
	public function __construct(string $s, string $usr, string $psw, string $db = "") { $this->db = $db; }
	public function execute(QueryPiece $qp) : ?array { return [$qp]; }
	public function db_name() : string { return $this->db; }
	public function inherit_db(string $name) : string { return "{$this->db}.{$name}"; }
	public function last_insert_id() {}
	public function transaction() : void {}
	public function rollback() : void {}
	public function commit() : void {}
	public function errors() : object { return new stdClass; }
}

?>