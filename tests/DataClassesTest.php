<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

class DataClassesTest extends TestCase {

	public function testMakeName() {
		$name = (new Name)
			->db("db")
			->table("table")
			->column("column")
			->alias("alias");

		$this->assertEquals("`db`.`table`.`column` AS `alias`", (string)$name);
	}

	public function testMakeColumn() {
		$name = new Name();
		$column = Column::make_primary("column")
			->foreign($name);

		$this->assertEquals("column", (string)$column);
	}

	public function testTableData() {
		$foreign = new Name("foreign");
		$primary = Column::make_primary("primary")->foreign($foreign);
		$second = Column::make_foreign("second", $foreign);
		$data = (new TableData)->columns($primary, $second);

		$this->assertSame($primary, $data->primary());
		$this->assertSame([$primary, $second], $data->foreign());
	}
}

?>
