<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\domain;

/**
 * Account repository interface.
 */
interface AccountRepository {

	/**
	 * Runs the operation inside a transaction.
	 *
	 * @template T
	 * @param callable(self): T $operation
	 *
	 * @return T
	 */
	public function transaction(callable $operation);

	/**
	 * Finds an account balance.
	 *
	 * @param string $name Name.
	 *
	 * @return ?int Value or null if not available.
	 */
	public function findBalance(string $name) :?int;

	/**
	 * Inserts a new account.
	 *
	 * @param string $name Name.
	 * @param int $balance Balance value.
	 *
	 * @return bool True on success.
	 */
	public function insert(string $name, int $balance) :bool;

	/**
	 * Updates an account balance.
	 *
	 * @param string $name Name.
	 * @param int $balance Balance value.
	 *
	 * @return bool True on success.
	 */
	public function updateBalance(string $name, int $balance) :bool;

	/**
	 * Returns all balances keyed by player name.
	 *
	 * @return array<string, int>
	 */
	public function listAll() :array;

	/**
	 * Returns a sorted page of balances.
	 *
	 * @return array<string, int>
	 */
	public function listTop(int $limit, int $offset, array $excludeNames = []) :array;

	/**
	 * Counts all.
	 *
	 * @param array $excludeNames Exclude names.
	 *
	 * @return int Count value.
	 */
	public function countAll(array $excludeNames = []) :int;

	/**
	 * Returns the sum of balances.
	 *
	 * @return int
	 */
	public function sumBalances() :int;

	/**
	 * Closes the connection.
	 */
	public function close() :void;
}
