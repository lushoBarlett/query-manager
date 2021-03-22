<?php

namespace QueryManager;

class QueryPiece {

	public $template = "";
	public $fragments = [];

	public function __construct(string $query = "", ...$frags) {
		$this->template = $query;
		$this->fragments = $frags;
	}

	public static function merge(...$qps) : self {
		return new self(
			implode(" ", array_map(function($qp) { return $qp->template; }, $qps)),
			...array_reduce(
				array_map( function($qp) { return $qp->fragments; }, $qps ),
				function ($last, $actual) { return array_merge($last, $actual); },
				[]
			)
		);
	}

	private static function prepend(string $inyection, $qp, ...$frags) : self {
		if (is_string($qp) || $qp instanceof Name)
			return new self("$inyection $qp", ...$frags);

		if ($qp instanceof self) {
			$qp->template = "$inyection $qp->template";
			return $qp;
		}

		throw new \Exception("Argument 2 should be either string, Name, or QueryPiece");
	}

	public static function Select()         { return self::prepend("SELECT",          ...func_get_args()); }
	public static function SelectDistinct() { return self::prepend("SELECT DISTINCT", ...func_get_args()); }
	public static function From()           { return self::prepend("FROM",            ...func_get_args()); }
	public static function InnerJoin()      { return self::prepend("INNER JOIN",      ...func_get_args()); }
	public static function Where()          { return self::prepend("WHERE",           ...func_get_args()); }
	public static function OrderBy()        { return self::prepend("ORDER BY",        ...func_get_args()); }
	public static function GroupBy()        { return self::prepend("GROUP BY",        ...func_get_args()); }
	public static function Limit()          { return self::prepend("LIMIT",           ...func_get_args()); }
	public static function InsertInto()     { return self::prepend("INSERT INTO",     ...func_get_args()); }
	public static function ReplaceInto()    { return self::prepend("REPLACE INTO",    ...func_get_args()); }
	public static function Values()         { return self::prepend("VALUES",          ...func_get_args()); }
	public static function Update()         { return self::prepend("UPDATE",          ...func_get_args()); }
	public static function Set()            { return self::prepend("SET",             ...func_get_args()); }
	public static function DeleteFrom()     { return self::prepend("DELETE FROM",     ...func_get_args()); }
}

?>
