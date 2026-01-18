<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\api;

use net\mydeacy\myeconomy\application\AccountService;
use net\mydeacy\myeconomy\application\MoneyOperationResult;
use net\mydeacy\myeconomy\infrastructure\config\PluginConfig;
use net\mydeacy\myeconomy\infrastructure\language\PlayerLanguageStore;
use net\mydeacy\myeconomy\infrastructure\text\MessageCatalog;
use pocketmine\player\Player;

/**
 * Public API surface for MyEconomy.
 *
 * This is the supported integration point for other plugins.
 */
final class MyEconomyAPI {

	private AccountService $service;

	private PluginConfig $config;

	private MessageCatalog $messages;

	private PlayerLanguageStore $languageStore;

	/**
	 * Creates a new instance.
	 *
	 * @param AccountService $service Service.
	 * @param PluginConfig $config Config.
	 * @param MessageCatalog $messages Messages.
	 * @param PlayerLanguageStore $languageStore Language store.
	 */
	public function __construct(
		AccountService $service,
		PluginConfig $config,
		MessageCatalog $messages,
		PlayerLanguageStore $languageStore
	) {
		$this->service = $service;
		$this->config = $config;
		$this->messages = $messages;
		$this->languageStore = $languageStore;
	}

	/**
	 * Returns the balance for a player name or Player.
	 *
	 * @param Player|string $player Player instance or name.
	 *
	 * @return int|bool Balance, or false if the account does not exist.
	 */
	public function myMoney(Player|string $player) :int|bool {
		$balance = $this->service->getBalance($this->resolveName($player));
		if ($balance === null) {
			return false;
		}
		return $balance;
	}

	/**
	 * Creates an account if it does not already exist.
	 *
	 * @param Player|string $player Player instance or name.
	 * @param int|null $defaultMoney When null, uses the configured default.
	 * @param bool $force If true, ignores cancellation from events.
	 * @param string $issuer Identifier for auditing/events.
	 */
	public function createAccount(
		Player|string $player,
		?int $defaultMoney = null,
		bool $force = false,
		string $issuer = "none"
	) :bool {
		return $this->service->createAccount($this->resolveName($player), $defaultMoney, $issuer, $force);
	}

	/**
	 * Checks if an account exists for the player.
	 *
	 * @param Player|string $player Player instance or name.
	 */
	public function accountExists(Player|string $player) :bool {
		return $this->service->accountExists($this->resolveName($player));
	}

	/**
	 * Sets the player's balance.
	 *
	 * @param Player|string $player Player instance or name.
	 * @param int $amount New balance.
	 * @param bool $force If true, ignores cancellation from events.
	 * @param string $issuer Identifier for auditing/events.
	 *
	 * @return int Result code from ResultCode::RET_*.
	 */
	public function setMoney(Player|string $player, int $amount, bool $force = false, string $issuer = "none") :int {
		return $this->service->setMoney($this->resolveName($player), $amount, $issuer, $force)->getCode();
	}

	/**
	 * Adds money to the player's balance.
	 *
	 * @param Player|string $player Player instance or name.
	 * @param int $amount Amount to add.
	 * @param bool $force If true, ignores cancellation from events.
	 * @param string $issuer Identifier for auditing/events.
	 *
	 * @return int Result code from ResultCode::RET_*.
	 */
	public function addMoney(Player|string $player, int $amount, bool $force = false, string $issuer = "none") :int {
		return $this->service->addMoney($this->resolveName($player), $amount, $issuer, $force)->getCode();
	}

	/**
	 * Reduces money from the player's balance.
	 *
	 * @param Player|string $player Player instance or name.
	 * @param int $amount Amount to reduce.
	 * @param bool $force If true, ignores cancellation from events.
	 * @param string $issuer Identifier for auditing/events.
	 *
	 * @return int Result code from ResultCode::RET_*.
	 */
	public function reduceMoney(Player|string $player, int $amount, bool $force = false, string $issuer = "none") :int {
		return $this->service->reduceMoney($this->resolveName($player), $amount, $issuer, $force)->getCode();
	}

	/**
	 * Transfers money between two accounts.
	 *
	 * @param string $payer Sender name.
	 * @param string $target Receiver name.
	 * @param int $amount Amount to transfer.
	 * @param bool $force If true, ignores cancellation from events.
	 * @param string $issuer Identifier for auditing/events.
	 */
	public function payMoney(
		string $payer,
		string $target,
		int $amount,
		bool $force = false,
		string $issuer = "PayCommand"
	) :MoneyOperationResult {
		return $this->service->payMoney($payer, $target, $amount, $issuer, $force);
	}

	/**
	 * Returns all balances keyed by player name.
	 *
	 * @return array<string, int>
	 */
	public function getAllMoney() :array {
		return $this->service->getAllBalances();
	}

	/**
	 * Returns a sorted page of balances in descending order.
	 *
	 * @param int $limit Max number of entries.
	 * @param int $offset Offset for paging.
	 * @param string[] $excludeNames Names to exclude.
	 *
	 * @return array<string, int>
	 */
	public function getTopBalances(int $limit, int $offset, array $excludeNames = []) :array {
		return $this->service->getTopBalances($limit, $offset, $excludeNames);
	}

	/**
	 * Counts the number of accounts.
	 *
	 * @param string[] $excludeNames Names to exclude.
	 */
	public function countAccounts(array $excludeNames = []) :int {
		return $this->service->countAccounts($excludeNames);
	}

	/**
	 * Returns the total sum of all balances.
	 */
	public function getTotalMoney() :int {
		return $this->service->getTotalBalance();
	}

	/**
	 * Returns the monetary unit (currency symbol).
	 */
	public function getMonetaryUnit() :string {
		return $this->config->getMonetaryUnit();
	}

	/**
	 * Sets a player's language preference.
	 *
	 * @param string $player Player name.
	 * @param string $language Language code.
	 *
	 * @return bool True if the language is supported.
	 */
	public function setPlayerLanguage(string $player, string $language) :bool {
		$language = MessageCatalog::normalizeLanguage($language);
		if (!$this->messages->hasLanguage($language)) {
			return false;
		}
		$this->languageStore->set($player, $language);
		return true;
	}

	/**
	 * Returns command metadata for a language.
	 *
	 * @param string $command Command name.
	 * @param string|null $language Language code, or null for default.
	 *
	 * @return array<string, string>
	 */
	public function getCommandMessage(string $command, ?string $language = null) :array {
		$language = $language === null ? $this->config->getDefaultLang() : $language;
		return $this->messages->getCommandMessage($command, $language);
	}

	/**
	 * Returns a localized message for the player.
	 *
	 * @param string $key Message key.
	 * @param array<int, mixed> $params Message parameters.
	 * @param string $player Player name (used for language resolution).
	 */
	public function getMessage(string $key, array $params = [], string $player = "console") :string {
		$lang = $this->languageStore->get($player);
		return $this->messages->getMessage($key, $params, $lang, $this->config->getMonetaryUnit());
	}

	/**
	 * Returns the list of available language codes.
	 *
	 * @return string[]
	 */
	public function getAvailableLanguages() :array {
		return $this->messages->listLanguages();
	}

	/**
	 * Returns the stored language for a player.
	 */
	public function getPlayerLanguage(string $player) :string {
		return $this->languageStore->get($player);
	}

	/**
	 * Returns the default language code.
	 */
	public function getDefaultLang() :string {
		return $this->config->getDefaultLang();
	}

	private function resolveName(Player|string $player) :string {
		return $player instanceof Player ? $player->getName() : $player;
	}
}
