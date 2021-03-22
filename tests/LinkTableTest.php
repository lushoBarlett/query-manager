<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestConnection.php';

class Link extends LinkTable {
	public static $db = "dbname";
	public static $name = "link";

	public static function columns(): array {
		$name1 = new Name(static::$db, "table1", "id");
		$name2 = new Name(static::$db, "table2", "id");
		return [
			(new Column("id_table_1"))->foreign($name1),
			(new Column("id_table_2"))->foreign($name2)
		];
	}
}

class LinkTableTest extends TestCase {

	public function setUp() : void {
		Link::connect(new TestConnection);
	}

	public function testLinkTable() {
		$qp = Link::inner_join();

		$from = "FROM `dbname`.`table1`";
		$inner1 = "INNER JOIN `dbname`.`link` ON `dbname`.`table1`.`id` = `dbname`.`link`.`id_table_1`";
		$inner2 = "INNER JOIN `dbname`.`table2` ON `dbname`.`link`.`id_table_2` = `dbname`.`table2`.`id`";
		$this->assertEquals("$from $inner1 $inner2", $qp->template);
	}
}

?>

