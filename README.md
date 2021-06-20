# PHP query manager

Manager for query building and execution.

## Objectives
* Query code flexibility and reusability
* Solve security and fidelity issues
* Force their use, with no penalty to the programmer
* Make simple database use cases quicker to code

## Implementation

### Query Pieces
The query building block is a `QueryPiece` class. In mathematical terms it is nothing more than a Monoid, because it acts as a pair of string and array. First constructor argument is the statement, the rest are the values filling that statement, called _fragments_. All arguments are optional, if nothing is provided you get the identity, empty string and array.
```php
$qp = new QueryPiece(
	"SELECT * FROM mytable WHERE id = ? and name = ?", 1, "some name"
);
$qp->template // "SELECT * FROM mytable WHERE id = ? and name = ?"
$qp->fragments // [1, "some name"]
```
These can be made smaller and then be merged. Spaces are added automatically.
```php
$qp1 = new QueryPiece("SELECT * FROM mytable");
$qp2 = new QueryPiece("WHERE id = ?", 1);
$qp3 = new QueryPiece("AND name = ?", "some name");

// produces the same object as the first example
$qp = QueryPiece::merge($qp1, $qp2, $qp3);
```

There's also a lot of static methods to make it look better, like `QueryPiece::Select(...)` which is the same as `new QueryPiece("SELECT ...")`.

### Formatters and Fields
The `Formatter` and `Field` classes are very helpful tool for sanitizing input. They are quite useful in the `Table` class, explained later, but they are not restricted to that use.

A `Field` defines a pipeline of operations to be performed on a value. There's maps, replacements, options, type and class restrictions and type casts.
```php
$pipe = new Field("name")
	->cast(Field::String)
	->in(["Hi", "Bye", "Hello", "Goodbye"])
	->replace([
		"Hi" => "Hello"
		"Bye" => "Goodbye"
	]),
	->map(fn($v) => $v . "!");

$pipe->pipeline("Hi"); // "Hello!"
$pipe->pipeline("Goodbye"); // "Goodbye!"
```
A `Formatter` is a just a set of those fields, but we can use new retrictions on those fields. Fields can be _optional_ or _required_. If they are optional they can have a default value to be used in the pipeline. The `Formatter` can also take strings, those represent optional fields with no default and no pipeline.
```php
$f = new Formatter(
	Field::default("first", 0),
	Field::required("second"),
	Field::optional("third")
);
```
Following this, you can call the formatting functions with some data. Note that formatting arrays and objects is the same, and they will be returned as such.
```php
$data = ["unwanted key" => 0, "second" => 1];

$f->format($data); // ["first" => 0, "second" => 1]
$f->format((object)$data); // {"first": 0, "second": 1}
```
### Columns

A `Column` class just holds a string, the name of the column. It can also specify if it is a primary column (also meaning unique), if it is unique, and if it is foreign. In the latter, it will hold a `Name` class referring to said foreign column.

```php
$column = new Column("this_id")
	->primary()
	->foreign(new Name("db", "other_table", "other_id"));

echo $column // "this_id"
```

### Names

A `Name` is a class that holds a database, table, column name, and or alias. It can make a valid string for SQL to use, or just use the data internally.

For database, an `IConnection` is also accepted. For columns, a `Column` is also accepted.

Not all four are required, any combination will work. Be wary that some combinations don't make sense.

```php
echo (new Name)
	->table("table")
	->alias("t") // `table` AS `t`

$fullname = new Name("database", "table", "column", "alias");

echo $fullname->db; // database
echo $fullname->table; // table
echo $fullname->column; // column
echo $fullname->alias; // alias
```

### Tables

The `Table` is a _static_ base class for any table. It implements many basic _static_ functions, that are available for the subclasses.

The subclasses will need to implement one function, `connect`. There, the suclass will construct a `TableData` object and pass it to `initialize` along with the connection provided in `connect`.

Suppose we have a `mydb.person` table that has columns `id, name, age, fav_food`.

Construction
```php
class Person extends Table {

	public static function connect(IConnection $conn) {
		// Note: if you don't need Column utilities,
		// you can use plain strings.
		$columns = [
			Column::make_primary("id"),
			"name",
			"age",
			"fav_food"
		];

		// forbids primary key insert
		$insert = new Formatter(
			"name",
			"age",
			Field::default("fav_food", "banana")
		);

		// also forbids name update
		$update = new Formatter(
			"age",
			"fav_food"
		);

		$data = (new TableData)
			->db("mydb")
			->name("person")
			->columns($columns)
			->on_insert($insert)
			->on_update($update);

		static::initialize($conn, $data);
	}
}

//...

$conn = get_my_connection();
Person::connect($conn);
```
The Table that execute a query on their own are public, so you can already call from outside, using any subclass. And always remember to `$conn->commit()`.
```php
// $data can be put directly here, the formatter takes care of cleanup.
// Table and Connection take care statement preparation,
// which prevents SQL inyection.
$data = get_evil_raw_data();
Person::insert($data);
```
### Connections

The Connection holds data necessary to connect to the database. It also prepares statements, passed as QueryPieces and it automatically uses a transaction model. The construction is the same as a normal mysqli class.
```php
// Note: database is optional
$c = new Connection("host", "user", "password", "database");
```
But it __always__ performs query preparation, and also has transaction managing functions exposed and used automatically as well. It starts a transaction in constructor, rolls back on any error and closes on destruction. It will not commit on its own, so you have to do it.
```php
$qp = new QueryPiece(...);
$result = $c->execute($qp);
$c->commit();
$c->transaction();
$c->rollback();
```