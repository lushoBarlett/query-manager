<?php

namespace QueryManager;

use PHPUnit\Framework\TestCase;

// extremely hacky, mask the global namespace classes to prevent their use
class mysqli_result {}
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

	public function get_result() {
		assert($this->query);
		return new mysqli_result($this->query);
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

	public $transactions = 0;
	public $commits = 0;
	public $rollbacks = 0;

	public function __consruct($h, $u, $p, $d = "") {
		if (self::$connection_error) {
			$this->connect_errno = 1;
			$this->connect_error = self::$connection_error;
		}
		$this->host = $h;
		$this->user = $u;
		$this->pass = $p;
		$this->db = $d;
	}

	public function prepare(string $q) {
		if (self::$preparation_error) {
			$this->error = self::$preparation_error;
			return false;
		}
		$this->prepared = true;
		return new mysqli_stmt(self::$execution_error);
	}
	
	public function begin_transaction() {
		if (self::$transaction_error) {
			$this->error = self::$transaction_error;
			return false;
		}
		$this->transactions++;
		return true;
	}
	public function rollback() {
		if (self::$rollback_error) {
			$this->error = self::$rollback_error;
			return false;
		}
		$this->rollbacks++;
		return true;
	}
	public function commit() {
		if (self::$commit_error) {
			$this->error = self::$commit_error;
			return false;
		}
		$this->commits++;
		return true;
	}
	public function close() {
		$this->closed = true;
	}
}

class ConnectionTest extends TestCase {

	testConstructionAndConnectError() {}
}

?>
