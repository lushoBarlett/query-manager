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
	"SELECT * FROM mytable WHERE id = ? and name = ?, 1, "some name"
);
$qp->template // "SELECT * FROM mytable WHERE id = ? and name = ?"
$qp->fragments // [1, "some name"]
```
These can be made smaller and then be merged. Don't worry about adding extra spaces.
```php
$qp1 = new QueryPiece("SELECT * FROM mytable");
$qp2 = new QueryPiece("WHERE id = ?", 1);
$qp3 = new QueryPiece("and name = ?", "some name");

// procudes the same object as the first example
QueryPiece::merge($qp1, $qp2, $qp3);
```

### Formatters
The Formatter class is a very helpful tool for sanitizing and managing complex default values when the database can't do it. The main feature it provides is best justified by the Table class, explained later. Basically it formats an associative array or object to **not have extra keys defined**, and if some are missing, the gap can be filled by a specified default value.

It takes a list of arguments, the desired keys. Then you can add defaults
```php
$f = new Formatter("first", "second", "third");
$f.add_default("first", 0);
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

### Table

The Table is a base class for any table, and it attempts to quickly solve the easy side of tables. Table implements 4 basic functions, that are inherited to the subclass. These are select, insert, update and delete. You *will* need more than this... but that's on you to actually implement. No matter the complexity of the software, you always need that one long and weird query that makes things so fast and easy.

Arguments:
1. database name
2. table name
3. list of keys that you wish to use in basic select
4. formatter that you wish to use in basic insert
5. formatter that you wish to use in basic update

The 4 functions take a Connection (next section) as a first argument, and an optional QueryPiece as a last argument, to be appended at the end of the basic query. Note: no function includes a *where* clause by default, they include just enough to be executed properly.

Let's explain the rest with examples. Let's suppose I have a `mydb.person` table that has columns id, name, age and fav_food.

Construction
```php
public function __construct() {
	$select = ["name", "age", "fav_food"]; // ommit id on purpose
	
	$insert = new Formatter("name", "age", "fav_food"); // did it again
	$insert->add_default("fav_food", random_food()); // very cool

	$update = new Formatter("age", "fav_food"); // no new name for you!

	parent::__construct("mydb", "person", $select, $insert, $update);
}
```
The Table functions are protected, so you have to expose them to let others use them. And let's not forget there is no where clause in update and delete so that's not good
```php
// $data can be safely used here, the formatter takes care of cleanup
// and the Table function takes care of the preparation of the update statement
public function update(Connection $db, $data, int $id) {
	$qp = new QueryPiece("WHERE id = ?", $id);
	parent::update($db, $data, $qp);
}
```
### Connection

What does the Connection do anyway? Connecting, preparing statements and automatically using a transaction model.
The construction is the same as a normal mysqli.
```php
$c = new Connection("host", "user", "password", "database"); // database is optional
```
But it performs query preparation by default, and also has transaction managing functions. It starts a transaction in constructor, rolls back on any error and commits and closes on destruction.
```php
// $qp = new QueryPiece( ... );
$mysqliresult = $c->execute($qp);
$c->transaction();
$c->rollback();
$c->commit();
```
