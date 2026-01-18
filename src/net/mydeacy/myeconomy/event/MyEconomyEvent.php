<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\event;

use pocketmine\event\Event;

/**
 * Base event for MyEconomy.
 *
 * The issuer indicates the source of the change.
 */
abstract class MyEconomyEvent extends Event {

	private string $issuer;

	/**
	 * Creates a new instance.
	 *
	 * @param string $issuer Issuer.
	 */
	public function __construct(string $issuer) {
		$this->issuer = $issuer;
	}

	/**
	 * Returns issuer.
	 *
	 * @return string
	 */
	public function getIssuer() :string {
		return $this->issuer;
	}
}
