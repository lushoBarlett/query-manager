<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestConnection.php';

class Person extends Table {

	public static function connect(IConnection $conn) : void {
		static::initialize($conn, (new TableData)
			->db("mydb")
			->name("person")
			->columns("name", "age", "fav_food")
			->on_insert(new Formatter("name", "age", Field::default("fav_food", "banana")))
			->on_update(new Formatter("age", "fav_food"))
		);
	}

	public static function non_standard_query($value) {
		return self::execute(new QueryPiece("DO WEIRD STUFF ?", $value));
	}
}

class Inherit extends Table {

	public static function connect(IConnection $conn) : void {
		static::initialize($conn, (new TableData)
			->db(Table::Inherit)
			->name("inherit")
			->columns("a", "b", "c")
		);
	}
}

// TODO: expand the test suite

class TableTest extends TestCase {

	private $conn;

	private function conn($db = "") {
		return new TestConnection("","","",$db);
	}

	public function setUp() : void {
		$this->conn = $this->conn();
		Person::connect($this->conn);
		Inherit::connect($this->conn);
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
		Person::insert(["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("]);
		$this->assertEquals(
			[new QueryPiece(
				"INSERT INTO `mydb`.`person` (name,age,fav_food) VALUES (?,?,?)",
				"gerald", 72, "banana"
			)],
			$this->conn->qps
		);
	}

	public function testUpdate() {
		Person::update(["name" => "gerald", "age" => 72, "inyect" => "');DROP DATABASE;("]);
		$this->assertEquals(
			[new QueryPiece("UPDATE `mydb`.`person` SET age = ?", 72)],
			$this->conn->qps
		);
	}

	public function testDelete() {
		Person::delete();
		$this->assertEquals(
			[new QueryPiece("DELETE FROM `mydb`.`person`")],
			$this->conn->qps
		);
	}

	public function testJoin() {
		$this->assertEquals(
			new QueryPiece("INNER JOIN `mydb`.`other` ON `mydb`.`person`.`left` = `mydb`.`other`.`right`"),
			Person::qp_inner_join("left", new Name("mydb", "other", "right"))
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
		Inherit::select();
	}
}

?>