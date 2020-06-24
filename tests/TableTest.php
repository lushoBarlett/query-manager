<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

class TestConnection implements IConnection {
	public function __construct(string $s, string $usr, string $psw, string $db = "") {}
	public function execute(QueryPiece $qp) : ?array { return [$qp]; }
	public function last_insert_id() {}
	public function transaction() : void {}
	public function rollback() : void {}
	public function commit() : void {}
	public function errors() : object { return new stdClass; }
}

class Person extends Table {

	public function __construct() {
		$select = new Formatter("name", "age", "fav_food");

		$insert = new Formatter("name", "age", "fav_food");
		$insert->add_default("fav_food", "banana");

		$update = new Formatter("age", "fav_food");

		parent::__construct("mydb", "person", $select, $insert, $update);
	}

	public function non_standard_query(IConnection $conn, $value) {
		return $conn->execute(new QueryPiece("DO WEIRD STUFF ?", $value));
	}
}

class TableTest extends TestCase {

	private function conn() { return new TestConnection("","",""); }

	public function testSelect() {
		$table = new Person;
		$this->assertEquals(
			[new QueryPiece("SELECT name,age,fav_food FROM mydb.person")],
			$table->select($this->conn())
		);
	}

	public function testInsert() {
		$table = new Person;
		$this->assertEquals(
			[new QueryPiece(
				"INSERT INTO mydb.person (name,age,fav_food) VALUES (?,?,?)",
				"gerald", 72, "banana"
			)],
			$table->insert($this->conn(), ["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("])
		);
	}

	public function testUpdate() {
		$table = new Person;
		$this->assertEquals(
			[new QueryPiece("UPDATE mydb.person SET age = ?", 72)],
			$table->update($this->conn(), ["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("])
		);
	}

	public function testDelete() {
		$table = new Person;
		$this->assertEquals(
			[new QueryPiece("DELETE FROM mydb.person")],
			$table->delete($this->conn())
		);
	}

	public function testNonStandardQuery() {
		$table = new Person;
		$this->assertEquals(
			[new QueryPiece("DO WEIRD STUFF ?", [-1])],
			$table->non_standard_query($this->conn(), [-1])
		);
	}
}

?>
