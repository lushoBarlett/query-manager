<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

class QueryPieceTest extends TestCase {

	public function testCreation() {
		$qp = new QueryPiece("first text", "other", "stuff");
		$this->assertEquals("first text", $qp->template);
		$this->assertEquals(["other","stuff"], $qp->fragments);
	}
	
	public function testMerge() {
		$this->assertEquals(
			new QueryPiece("this is the full text", "these", "are", "the", "fragments"),
			QueryPiece::merge(
				new QueryPiece("this is", "these"),
				new QueryPiece("the full", "are"),
				new QueryPiece("text", "the", "fragments")
			)
		);
	}

	public function testPrependThroughSelect() {
		$qp = new QueryPiece("a,b,c FROM table WHERE a = ?", "argument");
		$result = new QueryPiece("SELECT a,b,c FROM table WHERE a = ?", "argument");

		$this->assertEquals($result, QueryPiece::Select($qp->template, ...$qp->fragments));
		$this->assertEquals($result, QueryPiece::Select($qp));
	}
}

?>
