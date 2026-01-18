<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\application;

/**
 * Result object for money operations.
 */
final class MoneyOperationResult {

	private int $code;

	private ?int $oldBalance;

	private ?int $newBalance;

	/**
	 * Creates a new instance.
	 *
	 * @param int $code One of ResultCode::RET_*.
	 * @param int|null $oldBalance Previous balance when available.
	 * @param int|null $newBalance New balance when available.
	 */
	public function __construct(int $code, ?int $oldBalance = null, ?int $newBalance = null) {
		$this->code = $code;
		$this->oldBalance = $oldBalance;
		$this->newBalance = $newBalance;
	}

	/**
	 * Returns the result code.
	 *
	 * @return int ResultCode::RET_*.
	 */
	public function getCode() :int {
		return $this->code;
	}

	/**
	 * Returns the balance before the change.
	 *
	 * @return int|null Balance before the change.
	 */
	public function getOldBalance() :?int {
		return $this->oldBalance;
	}

	/**
	 * Returns the balance after the change.
	 *
	 * @return int|null Balance after the change.
	 */
	public function getNewBalance() :?int {
		return $this->newBalance;
	}
}
