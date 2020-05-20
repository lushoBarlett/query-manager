<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

// bit of a hack, prevent the load of the real Connection class
class Connection {
	public function execute(QueryPiece $qp) { return $qp; }
	public function transaction() {}
	public function rollback() {}
	public function commit() {}
}

class Person extends Table {

	public function __construct() {
		$select = ["name", "age", "fav_food"];

		$insert = new Formatter("name", "age", "fav_food");
		$insert->add_default("fav_food", "banana");

		$update = new Formatter("age", "fav_food");

		parent::__construct("mydb", "person", $select, $insert, $update);
	}

	public function non_standard_query(Connection $conn, $value) {
		return $conn->execute(new QueryPiece("DO WEIRD STUFF ?", $value));
	}
}

class TableTest extends TestCase {

	public function testSelect() {
		$table = new Person();
		$this->assertEquals(
			new QueryPiece("SELECT name,age,fav_food FROM mydb.person"),
			$table->select(new Connection)
		);
	}

	public function testInsert() {
		$table = new Person();
		$this->assertEquals(
			new QueryPiece(
				"INSERT INTO mydb.person(name,age,fav_food) FROM mydb.person VALUES (?,?,?)",
				"gerald", 72, "banana"
			),
			$table->insert(new Connection, ["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("])
		);
	}

	public function testUpdate() {
		$table = new Person();
		$this->assertEquals(
			new QueryPiece("UPDATE mydb.person SET age = ?", 72),
			$table->update(new Connection, ["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("])
		);
	}

	public function testDelete() {
		$table = new Person();
		$this->assertEquals(
			new QueryPiece("DELETE FROM mydb.person"),
			$table->delete(new Connection)
		);
	}

	public function testNonStandardQuery() {
		$table = new Person();
		$this->assertEquals(
			new QueryPiece("DO WEIRD STUFF ?", [-1]),
			$table->non_standard_query(new Connection, [-1])
		);
	}
}

?>
