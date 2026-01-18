<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\event\money;

use net\mydeacy\myeconomy\event\CancellableEconomyEvent;

/**
 * Fired before money is reduced from an account.
 */
final class ReduceMoneyEvent extends CancellableEconomyEvent {

	private string $username;

	private int $amount;

	/**
	 * Creates a new instance.
	 *
	 * @param string $username Username.
	 * @param int $amount Amount.
	 * @param string $issuer Issuer.
	 */
	public function __construct(string $username, int $amount, string $issuer) {
		parent::__construct($issuer);
		$this->username = $username;
		$this->amount = $amount;
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
	 * Returns amount.
	 *
	 * @return int
	 */
	public function getAmount() :int {
		return $this->amount;
	}
}
