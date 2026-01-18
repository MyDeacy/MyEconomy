<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\event\account;

use net\mydeacy\myeconomy\event\CancellableEconomyEvent;

/**
 * Fired before an account is created.
 *
 * This event is cancellable and allows changing the default money.
 */
final class AccountCreateEvent extends CancellableEconomyEvent {

	private string $username;

	private int $defaultMoney;

	/**
	 * Creates a new instance.
	 *
	 * @param string $username Username.
	 * @param int $defaultMoney Default money.
	 * @param string $issuer Issuer.
	 */
	public function __construct(string $username, int $defaultMoney, string $issuer) {
		parent::__construct($issuer);
		$this->username = $username;
		$this->defaultMoney = $defaultMoney;
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
	 * Returns default money.
	 *
	 * @return int
	 */
	public function getDefaultMoney() :int {
		return $this->defaultMoney;
	}

	/**
	 * Sets default money.
	 *
	 * @param int $money Money amount.
	 */
	public function setDefaultMoney(int $money) :void {
		$this->defaultMoney = $money;
	}
}
