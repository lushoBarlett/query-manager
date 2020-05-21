<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

// extremely hacky, mask the global namespace classes to prevent their use
class mysqli_result {
	public function __construct($q, $p) {
		$this->query = $q;
		$this->params = $p;
	}
}
class mysqli_stmt {

	public function __constructor(string $error = null) {
		if($error)
			$this->error = $error;
	}

	public function execute(string $query) {
		if($this->error)
			return false;
		$this->query = $query;
		return true;
	}

	public function bind_param(string $types) {
		$this->params = array_slice(func_get_args(),1);
		assert(strlen($types) === count($this->params));
	}

	public function get_result() : mysqli_result {
		assert($this->query);
		return new mysqli_result($this->query, $this->params);
	}
}
class mysqli {

	// configure error triggering before instantiation
	static $connection_error = null;
	static $preparation_error = null;
	static $execution_error = null;
	static $transaction_error = null;
	static $rollback_error = null;
	static $commit_error = null;

	public $connect_errno;
	public $connect_error;
	public $error;

	static $transaction = false;
	static $commit = false;
	static $rollback = false;
	static $closed = false;
	static $prepared = false;

	public function __consruct($h, $u, $p, $d = "") {
		if (self::$connection_error) {
			$this->connect_errno = 1;
			$this->connect_error = self::$connection_error;
		}
		self::$host = $h;
		self::$user = $u;
		self::$pass = $p;
		self::$db = $d;
	}

	public function prepare(string $q) {
		if (self::$preparation_error) {
			$this->error = self::$preparation_error;
			return false;
		}
		self::$prepared = true;
		return new mysqli_stmt($q, $p);
	}
	
	public function begin_transaction() {
		if (self::$transaction_error) {
			$this->error = self::$transaction_error;
			return false;
		}
		self::$transactions = true;
		return true;
	}
	public function rollback() {
		if (self::$rollback_error) {
			$this->error = self::$rollback_error;
			return false;
		}
		self::$rollbacks = true;
		return true;
	}
	public function commit() {
		if (self::$commit_error) {
			$this->error = self::$commit_error;
			return false;
		}
		self::$commits = true;
		return true;
	}
	public function close() {
		self::$closed = true;
	}
}

class ConnectionTest extends TestCase {

	public function testConstruction() {
		mysqli::$transaction = false;
		$c = new Connection("a","b","c","d");
		$this->assertTrue(mysqli::$transaction);
		$this->assertEquals("a",mysqli::$host);
		$this->assertEquals("b",mysqli::$user);
		$this->assertEquals("c",mysqli::$pass);
		$this->assertEquals("d",mysqli::$db);
		mysqli::$transaction = false;
	}

	public function testExecute() {
		$c = new Connection("","","","");
		$r = $c->execute(new QueryPiece("query","param"));
		$this->assertEquals("query", $r->query);
		$this->assertEquals(["param"], $r->params);
	}
	
	public function testTransaction() {
		$c = new Connection("","","","");
		mysqli::$transaction = false;
		$c->transaction();
		$this->assertTrue(mysqli::$transaction);
		mysqli::$transaction = false;
	}

	public function testRollback() {
		$c = new Connection("","","","");
		mysqli::$rollback = false;
		$c->rollback();
		$this->assertTrue(mysqli::$rollback);
		mysqli::$rollback = false;
	}
	
	public function testCommit() {
		$c = new Connection("","","","");
		mysqli::$commit = false;
		$c->commit();
		$this->assertTrue(mysqli::$commit);
		mysqli::$commit = false;
	}

	public function testDestruction() {
		$c = new Connection("","","","");
		mysqli::$commit = false;
		mysqli::$closed = false;
		unset($c);
		$this->assertTrue(mysqli::$commit);
		$this->assertTrue(mysqli::$closed);
		mysqli::$closed = false;
	}
	
	public function testConnectError() {
		mysqli::$connection_error = "c_error";

		try {
			new Connection("","","","");
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("c_error", (string)$e);
		}

		mysqli::$connection_error = null;
	}
	
	public function testPreparationError() {
		mysqli::$preparation_error = "p_error";
		$c = new Connection("","","","");

		try {
			$c->execute("query");
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("p_error", (string)$e);
		}

		mysqli::$preparation_error = null;
	}
	
	public function testExecutionError() {
		mysqli::$execution_error = "e_error";
		$c = new Connection("","","","");

		try {
			$c->execute("query");
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("e_error", (string)$e);
		}

		mysqli::$execution_error = null;
	}
	
	public function testTransactionError() {
		mysqli::$transaction_error = "t_error";

		try {
			// perform transaction on construction
			$c = new Connection("","","","");
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("t_error", (string)$e);
		}

		mysqli::$transaction_error = null;
		
		$c = new Connection("","","","");
		mysqli::$transaction_error = "t_error";

		try {
			$c->transaction();
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("t_error", (string)$e);
		}

		mysqli::$transaction_error = null;
	}
	
	public function testRollbackError() {
		mysqli::$rollback_error = "r_error";
		$c = new Connection("","","","");
		
		try {
			$c->rollback();
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("r_error", (string)$e);
		}

		mysqli::$rollback_error = null;
	}
	
	public function testCommitError() {
		mysqli::$commit_error = "c_error";
		$c = new Connection("","","","");

		try {
			$c->commit();
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("c_error", (string)$e);
		}

		try {
			// commit on destruction
			unset($c);
			$this->assertFalse(true);
		} catch (\Exception $e) {
			$this->assertEquals("c_error", (string)$e);
		}

		mysqli::$commit_error = null;
	}
}

?>
