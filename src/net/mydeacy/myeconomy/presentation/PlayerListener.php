<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation;

use net\mydeacy\myeconomy\api\MyEconomyAPI;
use net\mydeacy\myeconomy\infrastructure\language\PlayerLanguageStore;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

/**
 * Player event listener.
 */
final class PlayerListener implements Listener {

	private MyEconomyAPI $api;

	private PlayerLanguageStore $languageStore;

	/**
	 * Creates a new instance.
	 *
	 * @param MyEconomyAPI $api Api.
	 * @param PlayerLanguageStore $languageStore Language store.
	 */
	public function __construct(MyEconomyAPI $api, PlayerLanguageStore $languageStore) {
		$this->api = $api;
		$this->languageStore = $languageStore;
	}

	/**
	 * Handles join.
	 *
	 * @priority HIGHEST
	 * @param PlayerJoinEvent $event Event.
	 */
	public function onJoin(PlayerJoinEvent $event) :void {
		$player = $event->getPlayer();
		$this->api->createAccount($player, null, true, "auto");
		if (!$this->languageStore->has($player->getName())) {
			$this->languageStore->set($player->getName(), $this->api->getDefaultLang());
		}
	}
}
