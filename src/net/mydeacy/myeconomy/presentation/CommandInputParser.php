<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation;

use function preg_match;
use function trim;

/**
 * Command input parser.
 */
final class CommandInputParser {

	private function __construct() {
	}

	/**
	 * Parses a non-negative amount.
	 *
	 * @param string $value Value.
	 *
	 * @return ?int Value or null if not available.
	 */
	public static function parseNonNegativeAmount(string $value) :?int {
		$trimmedValue = trim($value);
		if ($trimmedValue === "" || !preg_match('/^\d+$/', $trimmedValue)) {
			return null;
		}
		return (int)$trimmedValue;
	}

	/**
	 * Parses a positive amount.
	 *
	 * @param string $value Value.
	 *
	 * @return ?int Value or null if not available.
	 */
	public static function parsePositiveAmount(string $value) :?int {
		$parsed = self::parseNonNegativeAmount($value);
		if ($parsed === null || $parsed <= 0) {
			return null;
		}
		return $parsed;
	}

	/**
	 * Parses a positive integer.
	 *
	 * @param string $value Value.
	 *
	 * @return ?int Value or null if not available.
	 */
	public static function parsePositiveInt(string $value) :?int {
		$trimmedValue = trim($value);
		if ($trimmedValue === "" || !preg_match('/^\d+$/', $trimmedValue)) {
			return null;
		}
		$parsed = (int)$trimmedValue;
		return $parsed > 0 ? $parsed : null;
	}
}
