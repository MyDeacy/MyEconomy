<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\application;

/**
 * Result codes for money operations.
 */
final class ResultCode {

	/** Account does not exist. */
	public const RET_NO_ACCOUNT = -3;
	/** Operation was cancelled by an event. */
	public const RET_CANCELLED = -2;
	/** Target was not found. */
	public const RET_NOT_FOUND = -1;
	/** Invalid amount or state. */
	public const RET_INVALID = 0;
	/** Operation succeeded. */
	public const RET_SUCCESS = 1;

	private function __construct() {
	}
}
