<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\event\money;

use net\mydeacy\myeconomy\event\MyEconomyEvent;

/**
 * Fired after an account balance is changed.
 */
final class MoneyChangedEvent extends MyEconomyEvent {

	private string $username;

	private int $newMoney;

	private ?int $oldMoney;

	/**
	 * Creates a new instance.
	 *
	 * @param string $username Username.
	 * @param int $newMoney New money.
	 * @param ?int $oldMoney Old money.
	 * @param string $issuer Issuer.
	 */
	public function __construct(string $username, int $newMoney, ?int $oldMoney, string $issuer) {
		parent::__construct($issuer);
		$this->username = $username;
		$this->newMoney = $newMoney;
		$this->oldMoney = $oldMoney;
	}

	/**
	 * Returns username.
	 *
	 * @return string
	 */
	public function getUsername() :string {
		return $this->username;
	}

	/**
	 * Returns new money.
	 *
	 * @return int
	 */
	public function getNewMoney() :int {
		return $this->newMoney;
	}

	/**
	 * Returns old money.
	 *
	 * @return ?int Value or null if not available.
	 */
	public function getOldMoney() :?int {
		return $this->oldMoney;
	}
}
