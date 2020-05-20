<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase {

	
	public function testCreation() {
		$f = new Formatter("first", "second", "third");
		$f->add_default("first", 0);
		$f->add_default("third");

		$this->assertEquals(["first","second","third"], $f->keys);
		$this->assertEquals(["first" => 0, "third" => null], $f->defaults);
	}
	
	public function testFormatWithSomeDefaults() {
		$f = new Formatter("first", "second", "third", "fourth");
		$f->add_default("first", 0);
		$data = [
			"fourth" => 0,
			"second" => 1,
			"trash" => 2
		];

		// due to implementation, defaults go first
		$this->assertEquals(
			["first" => 0, "fourth" => 0, "second" => 1],
			$f->as_array($data)
		);
		$this->assertEquals(
			(object)["first" => 0, "fourth" => 0, "second" => 1],
			$f->as_object($data)
		);
		$this->assertEquals(
			["first" => 0, "fourth" => 0, "second" => 1],
			$f->as_array((object)$data)
		);
		$this->assertEquals(
			(object)["first" => 0, "fourth" => 0, "second" => 1],
			$f->as_object((object)$data)
		);
	}
}

?>
