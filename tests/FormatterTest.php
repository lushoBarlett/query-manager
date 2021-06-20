<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase {

	public function testFormatWithSomeDefaults() {
		$f = new Formatter(Field::default("first", 0), "second", "third", "fourth");

		$data = ["fourth" => 0, "second" => 1, "trash" => 2];

		$this->assertEquals(
			["fourth" => 0, "second" => 1, "first" => 0],
			$f->format($data)
		);

		$this->assertEquals(
			(object)["fourth" => 0, "second" => 1, "first" => 0],
			$f->format((object)$data)
		);
	}

	public function testApplyMapping() {
		$mapper = function($v) { return $v . ".example"; };
		$f = new Formatter("first", Field::optional("transform")->map($mapper));

		$this->assertEquals(
			["first" => "value", "transform" => "text.example"],
			$f->format(["first" => "value", "transform" => "text"])
		);
	}

	public function testRequiredFields() {
		$f = new Formatter(Field::optional("first"), Field::required("required"));

		$this->expectException(\Exception::class);
		$f->format(["first" => "value"]);
	}

	public function testReplacements() {
		$replacer = ["val1" => "val2", "val" => "val1"];
		$f = new Formatter("first", Field::optional("replaceable")->replace($replacer));

		// Replacing can happen only once,
		// val => val1 =/> val2
		// val1 => val2
		$this->assertEquals(
			["first" => "val", "replaceable" => "val1"],
			$f->format(["first" => "val", "replaceable" => "val"])
		);

		$this->assertEquals(
			["first" => "val1", "replaceable" => "val2"],
			$f->format(["first" => "val1", "replaceable" => "val1"])
		);

		// No replacement
		$this->assertEquals(
			["first" => "val2", "replaceable" => "val3"],
			$f->format(["first" => "val2", "replaceable" => "val3"])
		);
	}

	public function testRestrictTypeIdempotence() {
		$f = new Formatter(
			Field::optional("intf")->type(Field::Int),
			Field::optional("floatf")->type(Field::Float),
			Field::optional("boolf")->type(Field::Bool),
			Field::optional("stringf")->type(Field::String),
			Field::optional("arrayf")->type(Field::Array),
			Field::optional("objectf")->type(Field::Object),
			Field::optional("nullf")->type(Field::Null)
		);

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
			$f->format([
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
		$f = new Formatter(Field::optional("intf")->type(Field::Int));

		$this->expectException(\Exception::class);
		$f->format(["intf" => "not an int"]);
	}

	public function testRestrictTypeCast() {
		$f = new Formatter(
			Field::optional("intf")->cast(Field::Int),
			Field::optional("stringf")->cast(Field::String)
		);

		$this->assertEquals(
			["intf" => 1234, "stringf" => "1"],
			$f->format(["intf" => "1234", "stringf" => true])
		);
	}

	public function testRestrictTypeCastFails() {
		$f = new Formatter(
			Field::optional("intf")->cast(Field::Int),
			Field::optional("stringf")->cast(Field::String)
		);

		$this->expectException(\Exception::class);
		$f->format(["intf" => new \stdClass]);
	}
}

?>
