<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

class NameAndColumnTest extends TestCase {

	public function testMakeName() {
		$name = Name::make()
		->db("db")
		->table("table")
		->column("column");

		$this->assertEquals("`db`.`table`.`column`", (string)$name);
		$this->assertEquals("db", $name->db);
		$this->assertEquals("table", $name->table);
		$this->assertEquals("column", $name->column);
	}

	public function testMakeColumn() {
		$name = Name::make();
		$column = Column::name("column")
		->primary()
		->unique()
		->foreign($name);

		$this->assertEquals("column", (string)$column);
		$this->assertTrue($column->primary);
		$this->assertTrue($column->unique);
		$this->assertSame($name, $column->foreign);
	}
}

?>
