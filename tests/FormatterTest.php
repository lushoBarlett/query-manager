<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase {

	public function testFormatWithSomeDefaults() {
		$f = new Formatter("first", "second", "third", "fourth");
		$f->add_default("first", 0);
		$data = [
			"fourth" => 0,
			"second" => 1,
			"trash" => 2
		];

		$this->assertEquals(
			["fourth" => 0, "second" => 1, "first" => 0],
			$f->as_array($data)
		);
		$this->assertEquals(
			(object)["fourth" => 0, "second" => 1, "first" => 0],
			$f->as_object($data)
		);
		$this->assertEquals(
			["fourth" => 0, "second" => 1, "first" => 0],
			$f->as_array((object)$data)
		);
		$this->assertEquals(
			(object)["fourth" => 0, "second" => 1, "first" => 0],
			$f->as_object((object)$data)
		);
	}

	public function testApplyMapping() {
		$f = new Formatter("first", "transform");
		$f->map("transform", function($v) { return $v.".example"; });

		$this->assertEquals(
			["first" => "value", "transform" => "text.example"],
			$f->as_array(["first" => "value", "transform" => "text"])
		);
	}

	public function testRequiredFields() {
		$f = new Formatter("first", "required");
		$f->require("required");

		$this->expectException(\Exception::class);
		$f->as_array(["first" => "value"]);
	}

	public function testReplacements() {
		$f = new Formatter("first", "replaceable");
		$f->replace("replaceable",
			["val1" => "val2", "val" => "val1"]
		);

		// Replacing can happen only once,
		// val => val1 =/> val2
		// val1 => val2
		$this->assertEquals(
			["first" => "val", "replaceable" => "val1"],
			$f->as_array(["first" => "val", "replaceable" => "val"])
		);

		$this->assertEquals(
			["first" => "val1", "replaceable" => "val2"],
			$f->as_array(["first" => "val1", "replaceable" => "val1"])
		);

		// No replacement
		$this->assertEquals(
			["first" => "val2", "replaceable" => "val3"],
			$f->as_array(["first" => "val2", "replaceable" => "val3"])
		);
	}

	public function testRestrictTypeIdempotence() {
		$f = new Formatter(
			"intf", "floatf", "boolf", "stringf", "arrayf", "objectf", "nullf"
		);

		$f->restrict_type("intf", "int");
		$f->restrict_type("floatf", "float");
		$f->restrict_type("boolf", "bool");
		$f->restrict_type("stringf", "string");
		$f->restrict_type("arrayf", "array");
		$f->restrict_type("objectf", "object");
		$f->restrict_type("nullf", "null");

		$this->assertEquals(
			[
				"intf" => 1,
				"floatf" => 1.34,
				"boolf" => false,
				"stringf" => "val",
				"arrayf" => [],
				"objectf" => new \stdClass,
				"nullf" => null,
			],
			$f->as_array([
				"intf" => 1,
				"floatf" => 1.34,
				"boolf" => false,
				"stringf" => "val",
				"arrayf" => [],
				"objectf" => new \stdClass,
				"nullf" => null,
			])
		);
	}

	public function testRestrictTypeFails() {
		$f = new Formatter("intf");
		$f->restrict_type("intf", "int");

		$this->expectException(\Exception::class);
		$f->as_array(["intf" => "not an int"]);
	}

	public function testRestrictTypeCast() {
		$f = new Formatter("intf", "stringf");
		$f->restrict_type_cast("intf", "int");
		$f->restrict_type_cast("stringf", "string");

		$this->assertEquals(
			["intf" => 1234, "stringf" => "1"],
			$f->as_array(["intf" => "1234", "stringf" => true])
		);
	}

	public function testRestrictTypeCastFails() {
		$f = new Formatter("intf", "stringf");
		$f->restrict_type_cast("intf", "int");

		$this->expectException(\Exception::class);
		$f->as_array(["intf" => new \stdClass]);
	}
}

?>
