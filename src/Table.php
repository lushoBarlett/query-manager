<?php

namespace QueryManager;

use QueryManager\QueryPiece as QP;

abstract class Table {

	const Inherit = '@inherit';
	
	private static $connected = [];

	protected static function data() : TableData {
		// TODO: assert if no connection is present
		return static::$connected[static::class];
	}

	protected static function initialize(IConnection $conn, TableData $data) : void {
		$data->connection = $conn;
		static::$connected[static::class] = $data;
	}

	// subclass has to construct and provide data to initialize
	abstract public static function connect(IConnection $conn) : void;

	public static function disconnect() : void {
		unset(static::$connected[static::class]);
	}

	public static function db_name() : string {
		$data = static::data();

		if ($data->db === self::Inherit) {
			if (!empty($db_name = static::data()->connection->db_name()))
				return $db_name;

			throw new \Exception("Connection has empty database name");
		}
		
		return $data->db;
	}

	public static function name() : string {
		return static::data()->name;
	}

	public static function fullname($column = null) : Name {
		$name = new Name(static::db_name(), static::name());
		if ($column)
			$name->column($column);

		return $name;
	}

	protected static function maybe_merge(?QP $a, ?QP $b) : ?QP {
		if ($a && $b)
			return QP::merge($a, $b);

		return $a ? $a : $b;
	}

	protected static function default_formatter() : Formatter {
		$fields = [];
		foreach (static::data()->columns as $column)
			$fields[] = $column->name;

		return new Formatter(...$fields);
	}

	public static function execute(QP $qp) {
		return static::data()->connection->execute($qp);
	}

	public static function qp_select_from(...$columns) : QP {
		if (!count($columns))
			$columns = static::data()->columns;

		$fullname = static::fullname();
		$columns = Column::serialize($columns);

		return QP::Select("$columns FROM $fullname");
	}

	public static function select(QP $extra = null) : ?array {
		$select = static::qp_select_from();
		return static::execute(static::maybe_merge($select, $extra));
	}

	public static function qp_insert_into($data) : QP {
		$qmarks = function($values) {
			return implode(',', array_fill(0, count($values), '?'));
		};

		$formatter = static::data()->insert_formatter ?: static::default_formatter();
		$data = (array)$formatter->format($data);

		list($columns, $values) = [array_keys($data), array_values($data)];

		$fullname = static::fullname();
		$columns = Column::serialize($columns);

		return QP::InsertInto("$fullname ($columns) VALUES ({$qmarks($values)})", ...$values);
	}

	public static function insert($data, ?QP $extra = null) : void {
		$insert = static::qp_insert_into($data);
		static::execute(static::maybe_merge($insert, $extra));
	}

	public static function qp_update($data) : QP {
		$qmarks = function($columns) {
			return implode(',', array_map(function($c) { return "$c = ?"; }, $columns));
		};

		$formatter = static::data()->update_formatter ?: static::default_formatter();
		$data = (array)$formatter->format($data);

		list($columns, $values) = [array_keys($data), array_values($data)];

		$fullname = static::fullname();

		return QP::Update("$fullname SET {$qmarks($columns)}", ...$values);
	}

	public static function update($data, ?QP $extra = null) : void {
		$update = static::qp_update($data);
		static::execute(static::maybe_merge($update, $extra));
	}

	public static function qp_delete() : QP {
		return QP::DeleteFrom(static::fullname());
	}

	public static function delete(?QP $extra = null) : void {
		$delete = static::qp_delete();
		static::execute(static::maybe_merge($delete, $extra));
	}

	protected static function qp_join(string $type, $left, Name $right) : QP {
		if (is_string($left))
			$left = static::fullname($left);

		$lefttable = new Name($left->db, $left->table);
		$righttable = new Name($right->db, $right->table);

		return QP::$type("$righttable ON $left = $right");
	}

	public static function qp_left_join(...$args) : QP {
		return static::qp_join("LeftJoin", ...$args);
	}

	public static function qp_right_join(...$args) : QP {
		return static::qp_join("RightJoin", ...$args);
	}

	public static function qp_inner_join(...$args) : QP {
		return static::qp_join("InnerJoin", ...$args);
	}

	public static function qp_full_join(...$args) : QP {
		return static::qp_join("FullJoin", ...$args);
	}
}

?>