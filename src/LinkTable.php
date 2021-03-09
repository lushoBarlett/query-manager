<?php

namespace QueryManager;

use QueryManager\QueryPiece as QP;

class LinkTable {

	public $fullname;
	public $foreign1;
	public $foreign2;

	public function __construct(string $fullname, string $foreign1, string $foreign2) {
		$this->fullname = $fullname;
		$this->foreign1 = $foreign1;
		$this->foreign2 = $foreign2;
	}
	
	public function inner_join(PrimaryColumn $p1, PrimaryColumn $p2) {
		$first  = "{$this->fullname}.{$this->foreign1}";
		$second = "{$this->fullname}.{$this->foreign2}";

		return QP::merge(
			QP::From($p1->table),
			QP::InnerJoin("{$this->fullname} ON {$p1->fullname()} = $first"),
			QP::InnerJoin("{$p2->table} ON $second = {$p2->fullname()}")
		);
	}
}

?>
