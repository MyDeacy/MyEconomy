<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\infrastructure\persistence;

use mysqli;
use mysqli_stmt;
use net\mydeacy\myeconomy\domain\AccountRepository;
use RuntimeException;
use Throwable;

/**
 * MySQL account repository.
 */
final class MysqlAccountRepository implements AccountRepository {

	private mysqli $db;

	private mysqli_stmt $findStmt;

	private mysqli_stmt $insertStmt;

	private mysqli_stmt $updateStmt;

	private mysqli_stmt $sumStmt;

	private mysqli_stmt $countStmt;

	private mysqli_stmt $listTopStmt;

	/**
	 * Creates a new instance.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	public function __construct(array $settings) {
		$host = (string)($settings["host"] ?? "127.0.0.1");
		$port = (int)($settings["port"] ?? 3306);
		$user = (string)($settings["user"] ?? "root");
		$password = (string)($settings["password"] ?? "");
		$database = (string)($settings["database"] ?? "economy");
		$this->db = new mysqli($host, $user, $password, $database, $port);
		if ($this->db->connect_errno !== 0) {
			throw new RuntimeException("MySQL connection failed: " . $this->db->connect_error);
		}
		$this->db->set_charset("utf8mb4");
		$this->db->query("CREATE TABLE IF NOT EXISTS accounts (name VARCHAR(64) PRIMARY KEY, balance BIGINT UNSIGNED NOT NULL)");
		$this->findStmt = $this->prepare("SELECT balance FROM accounts WHERE name = ? LIMIT 1");
		$this->insertStmt = $this->prepare("INSERT IGNORE INTO accounts (name, balance) VALUES (?, ?)");
		$this->updateStmt = $this->prepare("UPDATE accounts SET balance = ? WHERE name = ?");
		$this->sumStmt = $this->prepare("SELECT COALESCE(SUM(balance), 0) FROM accounts");
		$this->countStmt = $this->prepare("SELECT COUNT(1) FROM accounts");
		$this->listTopStmt = $this->prepare("SELECT name, balance FROM accounts ORDER BY balance DESC LIMIT ? OFFSET ?");
	}

	/**
	 * Runs a transaction.
	 *
	 * @param callable $operation Operation.
	 */
	public function transaction(callable $operation) {
		$this->db->begin_transaction();
		try {
			$result = $operation($this);
			$this->db->commit();
			return $result;
		} catch (Throwable $e) {
			$this->db->rollback();
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
		$this->findStmt->bind_param("s", $name);
		$this->findStmt->execute();
		$this->findStmt->bind_result($balance);
		if (!$this->findStmt->fetch()) {
			$this->findStmt->free_result();
			return null;
		}
		$this->findStmt->free_result();
		return (int)$balance;
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
		$this->insertStmt->bind_param("si", $name, $balance);
		$this->insertStmt->execute();
		return $this->insertStmt->affected_rows > 0;
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
		$this->updateStmt->bind_param("is", $balance, $name);
		$this->updateStmt->execute();
		return $this->updateStmt->affected_rows > 0;
	}

	/**
	 * Lists all.
	 *
	 * @return array List of values.
	 */
	public function listAll() :array {
		$result = $this->db->query("SELECT name, balance FROM accounts");
		if ($result === false) {
			return [];
		}
		$entries = [];
		while ($row = $result->fetch_assoc()) {
			$entries[(string)$row["name"]] = (int)$row["balance"];
		}
		$result->free();
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
			$this->listTopStmt->bind_param("ii", $limit, $normalizedOffset);
			$this->listTopStmt->execute();
			$this->listTopStmt->bind_result($name, $balance);
			$entries = [];
			while ($this->listTopStmt->fetch()) {
				$entries[(string)$name] = (int)$balance;
			}
			$this->listTopStmt->free_result();
			return $entries;
		}
		$placeholders = implode(",", array_fill(0, count($excludeNames), "?"));
		$stmt = $this->prepare("SELECT name, balance FROM accounts WHERE name NOT IN ($placeholders) ORDER BY balance DESC LIMIT ? OFFSET ?");
		$values = array_values($excludeNames);
		$values[] = $limit;
		$values[] = $normalizedOffset;
		$types = str_repeat("s", count($excludeNames)) . "ii";
		$this->bindParams($stmt, $types, $values);
		$stmt->execute();
		$stmt->bind_result($name, $balance);
		$entries = [];
		while ($stmt->fetch()) {
			$entries[(string)$name] = (int)$balance;
		}
		$stmt->close();
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
			$this->countStmt->execute();
			$this->countStmt->bind_result($count);
			$this->countStmt->fetch();
			$this->countStmt->free_result();
			return (int)$count;
		}
		$placeholders = implode(",", array_fill(0, count($excludeNames), "?"));
		$stmt = $this->prepare("SELECT COUNT(1) FROM accounts WHERE name NOT IN ($placeholders)");
		$values = array_values($excludeNames);
		$types = str_repeat("s", count($excludeNames));
		$this->bindParams($stmt, $types, $values);
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();
		return (int)$count;
	}

	/**
	 * Returns the sum of balances.
	 *
	 * @return int
	 */
	public function sumBalances() :int {
		$this->sumStmt->execute();
		$this->sumStmt->bind_result($total);
		$this->sumStmt->fetch();
		$this->sumStmt->free_result();
		return (int)$total;
	}

	/**
	 * Closes the connection.
	 */
	public function close() :void {
		$this->findStmt->close();
		$this->insertStmt->close();
		$this->updateStmt->close();
		$this->sumStmt->close();
		$this->countStmt->close();
		$this->listTopStmt->close();
		$this->db->close();
	}

	private function prepare(string $sql) :mysqli_stmt {
		$stmt = $this->db->prepare($sql);
		if ($stmt === false) {
			throw new RuntimeException("MySQL prepare failed: " . $this->db->error);
		}
		return $stmt;
	}

	private function bindParams(mysqli_stmt $stmt, string $types, array &$values) :void {
		$refs = [];
		foreach ($values as $index => &$value) {
			$refs[$index] = &$value;
		}
		$stmt->bind_param($types, ...$refs);
		unset($value);
	}
}
