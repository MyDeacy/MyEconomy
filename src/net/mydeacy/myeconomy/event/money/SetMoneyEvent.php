<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\event\money;

use net\mydeacy\myeconomy\event\CancellableEconomyEvent;

/**
 * Fired before an account balance is set.
 */
final class SetMoneyEvent extends CancellableEconomyEvent {

	private string $username;

	private int $money;

	/**
	 * Creates a new instance.
	 *
	 * @param string $username Username.
	 * @param int $money Money amount.
	 * @param string $issuer Issuer.
	 */
	public function __construct(string $username, int $money, string $issuer) {
		parent::__construct($issuer);
		$this->username = $username;
		$this->money = $money;
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
	 * Returns money.
	 *
	 * @return int
	 */
	public function getMoney() :int {
		return $this->money;
	}
}
