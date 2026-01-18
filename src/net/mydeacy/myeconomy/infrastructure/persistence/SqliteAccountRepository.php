<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\infrastructure\persistence;

use net\mydeacy\myeconomy\domain\AccountRepository;
use SQLite3;
use SQLite3Stmt;
use Throwable;
use function dirname;
use function is_array;
use function is_dir;
use function mkdir;

/**
 * SQLite account repository.
 */
final class SqliteAccountRepository implements AccountRepository {

	private SQLite3 $db;

	private SQLite3Stmt $findStmt;

	private SQLite3Stmt $insertStmt;

	private SQLite3Stmt $updateStmt;

	private SQLite3Stmt $sumStmt;

	private SQLite3Stmt $countStmt;

	private SQLite3Stmt $listTopStmt;

	/**
	 * Creates a new instance.
	 *
	 * @param string $path Path.
	 */
	public function __construct(string $path) {
		$directory = dirname($path);
		if (!is_dir($directory)) {
			mkdir($directory, 0777, true);
		}
		$this->db = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		$this->db->busyTimeout(2000);
		$this->db->exec("PRAGMA journal_mode=WAL;");
		$this->db->exec("CREATE TABLE IF NOT EXISTS accounts (name TEXT PRIMARY KEY, balance INTEGER NOT NULL);");
		$this->db->exec("CREATE INDEX IF NOT EXISTS idx_accounts_balance ON accounts(balance);");
		$this->findStmt = $this->db->prepare("SELECT balance FROM accounts WHERE name = :name LIMIT 1;");
		$this->insertStmt = $this->db->prepare("INSERT OR IGNORE INTO accounts (name, balance) VALUES (:name, :balance);");
		$this->updateStmt = $this->db->prepare("UPDATE accounts SET balance = :balance WHERE name = :name;");
		$this->sumStmt = $this->db->prepare("SELECT COALESCE(SUM(balance), 0) AS total FROM accounts;");
		$this->countStmt = $this->db->prepare("SELECT COUNT(1) AS cnt FROM accounts;");
		$this->listTopStmt = $this->db->prepare("SELECT name, balance FROM accounts ORDER BY balance DESC LIMIT :limit OFFSET :offset;");
	}

	/**
	 * Runs a transaction.
	 *
	 * @param callable $operation Operation.
	 */
	public function transaction(callable $operation) {
		$this->db->exec("BEGIN IMMEDIATE;");
		try {
			$result = $operation($this);
			$this->db->exec("COMMIT;");
			return $result;
		} catch (Throwable $e) {
			$this->db->exec("ROLLBACK;");
			throw $e;
		}
	}

	/**
	 * Finds an account balance.
	 *
	 * @param string $name Name.
	 *
	 * @return ?int Value or null if not available.
	 */
	public function findBalance(string $name) :?int {
		$this->findStmt->reset();
		$this->findStmt->bindValue(":name", $name, SQLITE3_TEXT);
		$result = $this->findStmt->execute();
		if ($result === false) {
			return null;
		}
		$row = $result->fetchArray(SQLITE3_ASSOC);
		if (!is_array($row)) {
			return null;
		}
		return (int)$row["balance"];
	}

	/**
	 * Inserts a new account.
	 *
	 * @param string $name Name.
	 * @param int $balance Balance value.
	 *
	 * @return bool True on success.
	 */
	public function insert(string $name, int $balance) :bool {
		$this->insertStmt->reset();
		$this->insertStmt->bindValue(":name", $name, SQLITE3_TEXT);
		$this->insertStmt->bindValue(":balance", $balance, SQLITE3_INTEGER);
		$this->insertStmt->execute();
		return $this->db->changes() > 0;
	}

	/**
	 * Updates an account balance.
	 *
	 * @param string $name Name.
	 * @param int $balance Balance value.
	 *
	 * @return bool True on success.
	 */
	public function updateBalance(string $name, int $balance) :bool {
		$this->updateStmt->reset();
		$this->updateStmt->bindValue(":name", $name, SQLITE3_TEXT);
		$this->updateStmt->bindValue(":balance", $balance, SQLITE3_INTEGER);
		$this->updateStmt->execute();
		return $this->db->changes() > 0;
	}

	/**
	 * Lists all.
	 *
	 * @return array List of values.
	 */
	public function listAll() :array {
		$result = $this->db->query("SELECT name, balance FROM accounts;");
		if ($result === false) {
			return [];
		}
		$entries = [];
		while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
			$entries[(string)$row["name"]] = (int)$row["balance"];
		}
		return $entries;
	}

	/**
	 * Lists top.
	 *
	 * @param int $limit Limit.
	 * @param int $offset Offset.
	 * @param array $excludeNames Exclude names.
	 *
	 * @return array List of values.
	 */
	public function listTop(int $limit, int $offset, array $excludeNames = []) :array {
		if ($limit <= 0) {
			return [];
		}
		$normalizedOffset = max(0, $offset);
		if ($excludeNames === []) {
			$this->listTopStmt->reset();
			$this->listTopStmt->bindValue(":limit", $limit, SQLITE3_INTEGER);
			$this->listTopStmt->bindValue(":offset", $normalizedOffset, SQLITE3_INTEGER);
			$result = $this->listTopStmt->execute();
		} else {
			$placeholders = implode(",", array_fill(0, count($excludeNames), "?"));
			$stmt = $this->db->prepare("SELECT name, balance FROM accounts WHERE name NOT IN ($placeholders) ORDER BY balance DESC LIMIT ? OFFSET ?;");
			$index = 1;
			foreach ($excludeNames as $name) {
				$stmt->bindValue($index, $name, SQLITE3_TEXT);
				$index++;
			}
			$stmt->bindValue($index, $limit, SQLITE3_INTEGER);
			$index++;
			$stmt->bindValue($index, $normalizedOffset, SQLITE3_INTEGER);
			$result = $stmt->execute();
		}
		if ($result === false) {
			if (isset($stmt)) {
				$stmt->close();
			}
			return [];
		}
		$entries = [];
		while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
			$entries[(string)$row["name"]] = (int)$row["balance"];
		}
		if (isset($stmt)) {
			$stmt->close();
		}
		return $entries;
	}

	/**
	 * Counts all.
	 *
	 * @param array $excludeNames Exclude names.
	 *
	 * @return int Count value.
	 */
	public function countAll(array $excludeNames = []) :int {
		if ($excludeNames === []) {
			$this->countStmt->reset();
			$result = $this->countStmt->execute();
		} else {
			$placeholders = implode(",", array_fill(0, count($excludeNames), "?"));
			$stmt = $this->db->prepare("SELECT COUNT(1) AS cnt FROM accounts WHERE name NOT IN ($placeholders);");
			$index = 1;
			foreach ($excludeNames as $name) {
				$stmt->bindValue($index, $name, SQLITE3_TEXT);
				$index++;
			}
			$result = $stmt->execute();
		}
		if ($result === false) {
			if (isset($stmt)) {
				$stmt->close();
			}
			return 0;
		}
		$row = $result->fetchArray(SQLITE3_ASSOC);
		if (isset($stmt)) {
			$stmt->close();
		}
		if (!is_array($row)) {
			return 0;
		}
		return (int)$row["cnt"];
	}

	/**
	 * Returns the sum of balances.
	 *
	 * @return int
	 */
	public function sumBalances() :int {
		$this->sumStmt->reset();
		$result = $this->sumStmt->execute();
		if ($result === false) {
			return 0;
		}
		$row = $result->fetchArray(SQLITE3_ASSOC);
		if (!is_array($row)) {
			return 0;
		}
		return (int)$row["total"];
	}

	/**
	 * Closes the connection.
	 */
	public function close() :void {
		$this->db->close();
	}
}
