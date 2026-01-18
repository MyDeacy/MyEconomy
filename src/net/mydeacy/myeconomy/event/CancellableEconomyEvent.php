<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

/**
 * Base class for cancellable MyEconomy events.
 */
abstract class CancellableEconomyEvent extends MyEconomyEvent implements Cancellable {

	use CancellableTrait;
}
