<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestConnection.php';

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

class Inherit extends Table {

	public function __construct() {
		$select = new Formatter("a", "b", "c");
		parent::__construct(Table::INHERIT, "inherit", $select, $select, $select);
	}

	public function inherit_db(IConnection $conn) : array {
		return parent::select($conn);
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

	public function testDatabaseInherit() {
		$conn1 = new TestConnection("", "", "", "some_db");
		$conn2 = new TestConnection("", "", "", "other_db");
		$table = new Inherit;

		list($qp) = $table->inherit_db($conn1);
		$this->assertEquals("SELECT a,b,c FROM some_db.inherit", $qp->template);

		list($qp) = $table->inherit_db($conn2);
		$this->assertEquals("SELECT a,b,c FROM other_db.inherit", $qp->template);
	}

	public function testDatabaseNotSelected() {
		$this->expectException(\Exception::class);

		$conn = new TestConnection("", "", "", "");
		$table = new Inherit;

		$table->inherit_db($conn);
	}

	public function testNoConnectionProvided() {
		$this->expectException(\Exception::class);

		(new Inherit)->fullname();
	}
}

?>
