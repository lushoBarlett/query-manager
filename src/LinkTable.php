<?php

namespace QueryManager;

use QueryManager\QueryPiece as QP;

class LinkTable extends Table {
	
	public static function inner_join() {
		list($foreign1, $foreign2) = static::columns();

		$primary1 = $foreign1->foreign;
		$primary2 = $foreign2->foreign;
		
		$foreign1 = Name::stringify(static::db_name(), static::name(), $foreign1);
		$foreign2 = Name::stringify(static::db_name(), static::name(), $foreign2);

		$table1 = Name::stringify($primary1->db, $primary1->table);
		$table2 = Name::stringify($primary2->db, $primary2->table);
		$link = static::fullname();

		return QP::merge(
			QP::From($table1),
			QP::InnerJoin("$link ON $primary1 = $foreign1"),
			QP::InnerJoin("$table2 ON $foreign2 = $primary2")
		);
	}
}

?>
