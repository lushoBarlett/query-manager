<?php

namespace QueryManager;

class QueryPiece {

	public $template = "";
	public $fragments = [];

	public function __construct(string $query = "") {
		$this->template = $query;
		$this->fragments = array_slice(func_get_args(),1);
	}

	public static function merge() {
		$qps = func_get_args();
		return new QueryPiece(
			implode(" ", array_map(function($qp) { return $qp->template; }, $qps)),
			...array_reduce(
				array_map( function($qp) { return $qp->fragments; }, $qps ),
				function ($last, $actual) { return array_merge($last, $actual); },
				[]
			)
		);
	}
}

?>
