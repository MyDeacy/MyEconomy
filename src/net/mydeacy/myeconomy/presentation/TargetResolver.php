<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation;

use pocketmine\player\Player;
use pocketmine\Server;
use function trim;

/**
 * Resolves target player names.
 */
final class TargetResolver {

	private Server $server;

	/**
	 * Creates a new instance.
	 *
	 * @param Server $server Server.
	 */
	public function __construct(Server $server) {
		$this->server = $server;
	}

	/**
	 * Resolves a player name to a canonical name and online player.
	 *
	 * @param string $player Player name.
	 *
	 * @return array{0: string, 1: Player|null}
	 */
	public function resolve(string $player) :array {
		$trimmedPlayer = trim($player);
		$target = $this->server->getPlayerExact($trimmedPlayer);
		$targetName = $target instanceof Player ? $target->getName() : $trimmedPlayer;
		return [$targetName, $target instanceof Player ? $target : null];
	}

	/**
	 * Returns online player names.
	 *
	 * @return string[]
	 */
	public function listOnlineNames() :array {
		$names = [];
		foreach ($this->server->getOnlinePlayers() as $online) {
			$names[] = $online->getName();
		}
		return $names;
	}
}
