<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\event\money;

use net\mydeacy\myeconomy\event\CancellableEconomyEvent;

/**
 * Fired before a payment is executed.
 */
final class PayMoneyEvent extends CancellableEconomyEvent {

	private string $payer;

	private string $target;

	private int $amount;

	/**
	 * Creates a new instance.
	 *
	 * @param string $payer Payer name.
	 * @param string $target Target name.
	 * @param int $amount Amount.
	 * @param string $issuer Issuer.
	 */
	public function __construct(string $payer, string $target, int $amount, string $issuer) {
		parent::__construct($issuer);
		$this->payer = $payer;
		$this->target = $target;
		$this->amount = $amount;
	}

	/**
	 * Returns payer.
	 *
	 * @return string
	 */
	public function getPayer() :string {
		return $this->payer;
	}

	/**
	 * Returns target.
	 *
	 * @return string
	 */
	public function getTarget() :string {
		return $this->target;
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
