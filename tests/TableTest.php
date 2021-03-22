<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestConnection.php';

class Person extends Table {

	public static $db = "mydb";
	public static $name = "person";
	public static function columns() : array {
		return [
			new Column("name"),
			new Column("age"),
			new Column("fav_food")
		];
	}

	public static function non_standard_query($value) {
		return self::$connection->execute(new QueryPiece("DO WEIRD STUFF ?", $value));
	}
}

class Inherit extends Table {

	public static $db = Table::INHERIT;
	public static $name = "inherit";
	public static function columns() : array {
		return [
			new Column("a"),
			new Column("b"),
			new Column("c")
		];
	}
}

class StringColumns extends Table {
	public static $db = "mydb";
	public static $name = "strings";
	public static function columns() : array {
		return ["a", new Column("b"), "c"];
	}
}

class TableTest extends TestCase {

	private function conn($db = "") {
		return new TestConnection("","","",$db);
	}

	public function setUp() : void {
		Person::connect($this->conn());

		$insertf = new Formatter("name", "age", "fav_food");
		$insertf->add_default("fav_food", "banana");
		Person::$insert_formatter = $insertf;

		$updatef = new Formatter("age", "fav_food");
		Person::$update_formatter = $updatef;
	}

	public function testName() {
		$this->assertEquals("person", Person::name());
	}

	public function testFullname() {
		$this->assertEquals("`mydb`.`person`", Person::fullname());
	}
	
	public function testSelect() {
		$this->assertEquals(
			[new QueryPiece("SELECT name,age,fav_food FROM `mydb`.`person`")],
			Person::select()
		);
	}

	public function testInsert() {
		$this->assertEquals(
			[new QueryPiece(
				"INSERT INTO `mydb`.`person` (name,age,fav_food) VALUES (?,?,?)",
				"gerald", 72, "banana"
			)],
			Person::insert(["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("])
		);
	}

	public function testUpdate() {
		$this->assertEquals(
			[new QueryPiece("UPDATE `mydb`.`person` SET age = ?", 72)],
			Person::update(["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("])
		);
	}

	public function testDelete() {
		$this->assertEquals(
			[new QueryPiece("DELETE FROM `mydb`.`person`")],
			Person::delete()
		);
	}

	public function testNonStandardQuery() {
		$this->assertEquals(
			[new QueryPiece("DO WEIRD STUFF ?", [-1])],
			Person::non_standard_query([-1])
		);
	}

	public function testDatabaseInherit() {
		$conn1 = $this->conn("some_db");
		$conn2 = $this->conn("other_db");

		Inherit::connect($conn1);
		list($qp) = Inherit::select();
		$this->assertEquals("SELECT a,b,c FROM `some_db`.`inherit`", $qp->template);

		Inherit::connect($conn2);
		list($qp) = Inherit::select();
		$this->assertEquals("SELECT a,b,c FROM `other_db`.`inherit`", $qp->template);
	}

	public function testDatabaseNotSelected() {
		$this->expectException(\Exception::class);

		Inherit::connect($this->conn());
		Inherit::select();
	}

	public function testNoConnectionProvided() {
		$this->expectException(\Exception::class);

		Inherit::disconnect();
		Inherit::db_name();
	}

	public function testStringColumns() {
		StringColumns::connect($this->conn());
		list($qp) = StringColumns::select();
		$this->assertEquals("SELECT a,b,c FROM `mydb`.`strings`", $qp->template);
	}
}

?>
