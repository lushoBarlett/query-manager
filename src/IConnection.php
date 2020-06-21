<?php

namespace QueryManager;

interface IConnection {

	public function __construct(string $s, string $usr, string $psw, string $db = "");
	public function execute(QueryPiece $qp) : ?array;
	public function transaction() : void;
	public function rollback() : void;
	public function commit() : void;
	public function errors() : object; 
}

?>

