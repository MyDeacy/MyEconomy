<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\domain;

/**
 * Immutable account record.
 */
final class Account {

	private string $name;

	private int $balance;

	/**
	 * Creates a new instance.
	 *
	 * @param string $name Name.
	 * @param int $balance Balance value.
	 */
	public function __construct(string $name, int $balance) {
		$this->name = self::normalizeName($name);
		$this->balance = max(0, $balance);
	}

	/**
	 * Normalizes a player name.
	 *
	 * @param string $name Name.
	 *
	 * @return string
	 */
	public static function normalizeName(string $name) :string {
		return strtolower($name);
	}

	/**
	 * Returns name.
	 *
	 * @return string
	 */
	public function getName() :string {
		return $this->name;
	}

	/**
	 * Returns balance.
	 *
	 * @return int
	 */
	public function getBalance() :int {
		return $this->balance;
	}

	/**
	 * Returns a new instance with updated balance.
	 *
	 * @param int $balance Balance value.
	 *
	 * @return self
	 */
	public function withBalance(int $balance) :self {
		return new self($this->name, $balance);
	}
}
