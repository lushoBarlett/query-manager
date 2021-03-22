# PHP query manager

Manager for query building and execution.

## Objectives
* Query code flexibility and reusability
* Solve security and fidelity issues
* Force their use, with no penalty to the programmer
* Make simple database use cases quicker to code

## Implementation

### Query Pieces
The query building block is a QueryPiece class. First constructor argument is a statement, the rest are blank fillers. Both things are optional, if nothing is provided it will do nothing QueryPiece. Combined they form a prepared query
```php
$qp = new QueryPiece(
	"SELECT * FROM mytable WHERE id = ? and name = ?", 1, "some name"
);
$qp->template // "SELECT * FROM mytable WHERE id = ? and name = ?"
$qp->fragments // [1, "some name"]
```
These can be made smaller and then be merged. Don't worry about adding extra spaces.
```php
$qp1 = new QueryPiece("SELECT * FROM mytable");
$qp2 = new QueryPiece("WHERE id = ?", 1);
$qp3 = new QueryPiece("and name = ?", "some name");

// produces the same object as the first example
QueryPiece::merge($qp1, $qp2, $qp3);
```

There's also a lot of static methods to make it look cooler, like `QueryPiece::Select(...)` which is the same as `new QueryPiece("SELECT ...")` and you can `QueryPiece::merge(...)` these to achieve nice code.

### Formatters
The Formatter class is a very helpful tool for sanitizing and managing complex default values when the database can't do it. The main feature it provides is best justified by the Table class, explained later. Basically it formats an associative array or object to **not have extra keys defined**, and if some are missing, the gap can be filled by a specified default value. Other less common things might be mapping the values or replacing the values with some rules.

It takes a list of arguments, the desired keys. Then you can add defaults
```php
$f = new Formatter("first", "second", "third");
$f->add_default("first", 0);
```
Following this, you can call the formatting functions with some data
```php
$data = [
	"unwanted key" => 0,
	"second" => 1
]

// This produces ["first" => 0, "second" => 1]
$f->as_array($data);
// This produces (object)["first" => 0, "second" => 1]
$f->as_object($data);
// Note: "third" is not part of the result
```
Both formatting functions will accept either an associative array or an object.

### Columns

A Column class just holds a string, the name of the column. It can also specify if it is a primary column (also meaning unique), if it is unique, and if it is foreign. Last case, it will hold a Name class referring to said foreign column.

```php
$column = new Column("this_id");
$column->primary()->foreign(new Name("db", "other_table", "other_id"));
echo $column // "this_id"
```

### Names

A Name is a class that holds a database, table, and or column name. It can make a valid string for SQL to use, or just use the data internally.

For database, an `IConnection` is also accepted. For columns, a `Column` is also accepted.

Not all three are required, one or two will work as well.

```php
$fullname = new Name("database", "table", "column");
echo $fullname; // `database`.`table`.`column`

echo Name::make()->table("table")->column(Column::ALL) // `table`.*

echo $fullname->db; // database
echo $fullname->table; // table
echo $fullname->column; // column
```

### Tables

The Table is a _static_ base class for any table. It implements 4 basic _static_ functions, that are available for the subclasses. These are select, insert, update and delete. You **will** need more than this... but that's where you come in and implement more methods. No matter the complexity of the software, you always need that one long and weird query that makes things so fast and easy.

The 4 functions take an optional QueryPiece as argument, to be appended at the end of the basic query. **Note:** no function includes a *where* clause by default, they include just enough to be executed.

`Table::select()` will give you results automatically with what you return from `MyClass::columns()` (see example). Whereas insert and update will require a `Formatter` class to sanitize the values they take in, because they take them in as an associative array or object. Delete does not need anything, of course.

Suppose we have a `mydb.person` table that has columns `id, name, age, fav_food`.

Construction
```php
class Person extends Table {
	public static $db = "mydb";
	public static $name = "person";
	public static function columns() {
		// Note: if you don't need Column utilities,
		// you can use plain strings.
		// e.g. "age" instead of new Column("age")
		return [
			(new Column("id"))->primary(),
			new Column("name"),
			"age",
			new Column("fav_food"),
		];
	}
	
}

//...

$conn = get_my_connection();

$insert = new Formatter("name", "age", "fav_food"); // did it again
$insert->add_default("fav_food", random_food()); // very cool

$update = new Formatter("age", "fav_food"); // no new name for you!

Person::connect($conn);
Person::$insert_formatter = $insert;
Person::$update_formatter = $update;
```
The Table functions are public, so you can already call `Person::select()`. And let's not forget there is no where clause in update and delete so be wary of that! (not that it matters if you don't `$conn->commit()`)
```php
// $data can be put directly here, the formatter takes care of cleanup.
// Table and Connection take care statement preparation,
// which prevents SQL inyection.
$data = get_evil_raw_data();
Person::insert($data);
```
### Connections

What does the Connection do anyway? Connecting, preparing statements and automatically using a transaction model. The construction is the same as a normal mysqli.
```php
// Note: database is optional
$c = new Connection("host", "user", "password", "database");
```
But it performs query preparation __always__, and also has transaction managing functions exposed and used automatically as well. It starts a transaction in constructor, rolls back on any error and closes on destruction. It will not commit on its own, so you have to do it.
```php
$qp = new QueryPiece(...);
$array_or_null = $c->execute($qp);
$c->commit();
$c->transaction();
$c->rollback();
```

### Link Tables

A link table is a subclass of Table, which adds an extra function called `LinkTable::inner_join()` and is essencially a many to many linking QueryPiece for you to use for free without bothering.

```php
class LinkExample extends LinkTable {
	public static $db = "mydb";
	public static $name = "table1_table2";
	public static function columns() {
		$key1 = new Name(self::$db, "table1", "id");
		$key2 = new Name(self::$db, "table2", "id");
		// foreign columns must come first, and in order
		return [
			(new Column("link1"))->foreign($key1),
			(new Column("link2"))->foreign($key2),
			new Column("extra1"),
			new Column("extra2"),
		];
	}
}

//...

// Note: inner_join builds from "FROM" keyword of the query.
// Remember you can use the 4 basic operations on link tables as well.
$qp = QueryPiece::merge(
	QueryPiece::Select(...),
	LinkExample::inner_join(),
	QueryPiece::Where(...)
);
```