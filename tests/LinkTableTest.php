<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestConnection.php';

class LinkTableTest extends TestCase {

	public function testLinkTable() {
		$conn = new TestConnection("", "", "", "dbname");

		$link = $conn->inherit_db("link");
		$lt = new LinkTable($link, "id_table_1", "id_table_2");

		$table1 = $conn->inherit_db("table1");
		$table2 = $conn->inherit_db("table2");

		$primary1 = new PrimaryColumn($table1, "id");
		$primary2 = new PrimaryColumn($table2, "id");

		$qp = $lt->inner_join($primary1, $primary2);

		$from = "FROM dbname.table1";
		$inner1 = "INNER JOIN dbname.link ON dbname.table1.id = dbname.link.id_table_1";
		$inner2 = "INNER JOIN dbname.table2 ON dbname.link.id_table_2 = dbname.table2.id";
		$this->assertEquals("$from $inner1 $inner2", $qp->template);
	}
}

?>

