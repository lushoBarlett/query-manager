<?php

namespace QueryManager;

use QueryManager\QueryPiece as QP;

class Table {

	const INHERIT = '@inherit';

	protected static $connection = null;
	protected static $db = "";
	protected static $name = "";
	
	public static $insert_formatter = null;
	public static $update_formatter = null;

	public static function connect(IConnection $conn) {
		static::$connection = $conn;
	}

	public static function disconnect() {
		static::$connection = null;
	}

	public static function db_name() : string {
		if (static::$db === self::INHERIT) {
			if (!static::$connection)
				throw new \Exception("No connection provided to get database name");

			if (empty($db_name = static::$connection->db_name()))
				throw new \Exception("Connection has empty database name");

			return $db_name;
		}
		
		return static::$db;
	}

	public static function name() : string {
		return static::$name;
	}

	public static function fullname($column = null) : Name {
		$name = new Name(static::db_name(), static::name());
		if ($column)
			$name->column($column);

		return $name;
	}

	public static function columns() : array {
		return [];
	}

	public static function select(?QP $extra = null) : ?array {
		$fullname = static::fullname();
		$columns = Column::serialize(static::columns());

		$s = QP::Select("{$columns} FROM {$fullname}");

		return static::$connection->execute($extra ? QP::merge($s, $extra) : $s);
	}

	public static function insert($data, ?QP $extra = null) {
		$qmarks = function(array $values) {
			return "(" . rtrim(str_repeat('?,', count($values)), ',') . ")";
		};

		$formatted = static::$insert_formatter->as_array($data);

		list($columns, $values) = [array_keys($formatted), array_values($formatted)];

		$fullname = static::fullname();
		$columns = Column::serialize($columns);

		$i = QP::InsertInto("$fullname ($columns) VALUES {$qmarks($values)}", ...$values);

		return static::$connection->execute($extra ? QP::merge($i, $extra) : $i);
	}

	public static function update($data, ?QP $extra = null) {
		$qmarks = function(array $columns) {
			$columns = array_map(function($c) { return "$c = ?"; }, $columns);
			return implode(',', $columns);
		};

		$formatted = static::$update_formatter->as_array($data);

		list($columns, $values) = [array_keys($formatted), array_values($formatted)];

		$fullname = static::fullname();

		$u = QP::Update("$fullname SET {$qmarks($columns)}", ...$values);

		return static::$connection->execute($extra ? QP::merge($u, $extra) : $u);
	}

	public static function delete(QP $extra = null) {
		$d = QP::DeleteFrom(static::fullname());

		return static::$connection->execute($extra ? QP::merge($d, $extra) : $d);
	}
}

?>

