<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation;

use net\mydeacy\myeconomy\api\MyEconomyAPI;
use net\mydeacy\myeconomy\application\ResultCode;
use net\mydeacy\myeconomy\infrastructure\config\PluginConfig;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_keys;
use function array_values;
use function ceil;
use function implode;
use function max;
use function min;
use function round;
use function str_replace;
use function strtolower;

/**
 * Command action handlers.
 */
final class CommandActions {

	private const TOPMONEY_PER_PAGE = 5;

	private MyEconomyAPI $api;

	private PluginConfig $config;

	private Server $server;

	/**
	 * Creates a new instance.
	 *
	 * @param MyEconomyAPI $api Api.
	 * @param PluginConfig $config Config.
	 * @param Server $server Server.
	 */
	public function __construct(MyEconomyAPI $api, PluginConfig $config, Server $server) {
		$this->api = $api;
		$this->config = $config;
		$this->server = $server;
	}

	/**
	 * Shows the sender's balance.
	 *
	 * @param CommandSender $sender Sender.
	 *
	 * @return bool True on success.
	 */
	public function showMyMoney(CommandSender $sender) :bool {
		$player = $this->requirePlayer($sender);
		if ($player === null) {
			return true;
		}
		$balance = $this->resolveBalance($player);
		$player->sendMessage($this->api->getMessage("mymoney-mymoney", [$balance], $player->getName()));
		return true;
	}

	/**
	 * Shows the top balances page.
	 *
	 * @param CommandSender $sender Sender.
	 * @param int $page Page number.
	 *
	 * @return bool True on success.
	 */
	public function showTopMoney(CommandSender $sender, int $page) :bool {
		$normalizedPage = max(1, $page);
		$excludeNames = [];
		foreach ($this->server->getNameBans()->getEntries() as $entry) {
			$excludeNames[] = strtolower($entry->getName());
		}
		if (!$this->config->shouldAddOpAtRank()) {
			foreach ($this->server->getOps()->getAll() as $name => $value) {
				$excludeNames[] = strtolower((string)$name);
			}
		}
		$perPage = self::TOPMONEY_PER_PAGE;
		$total = $this->api->countAccounts($excludeNames);
		$maxPage = max(1, (int)ceil($total / $perPage));
		$boundedPage = min($maxPage, $normalizedPage);
		$offset = ($boundedPage - 1) * $perPage;
		$entries = $this->api->getTopBalances($perPage, $offset, $excludeNames);
		$tag = $this->api->getMessage("topmoney-tag", [$boundedPage, $maxPage], $sender->getName());
		$format = $this->api->getMessage("topmoney-format", [], $sender->getName());
		$names = array_keys($entries);
		$balances = array_values($entries);
		$lines = [];
		foreach ($names as $index => $name) {
			$rank = $offset + $index + 1;
			$lines[] = str_replace(["%1", "%2", "%3"], [$rank, $name, $balances[$index]], $format);
		}
		$entriesText = $lines === [] ? "" : "\n" . implode("\n", $lines);
		$output = $tag . $entriesText;
		if ($sender->getName() === "CONSOLE") {
			$this->server->getLogger()->info($output);
			return true;
		}
		$sender->sendMessage($output);
		return true;
	}

	/**
	 * Sets money.
	 *
	 * @param CommandSender $sender Sender.
	 * @param string $targetName Target name.
	 * @param int $amount Amount.
	 * @param ?Player $target Target name.
	 */
	public function setMoney(CommandSender $sender, string $targetName, int $amount, ?Player $target) :void {
		$result = $this->api->setMoney($targetName, $amount, false, "economyapi.command.set");
		switch ($result) {
			case ResultCode::RET_INVALID:
				$sender->sendMessage($this->api->getMessage("setmoney-invalid-number", [$amount], $sender->getName()));
				break;
			case ResultCode::RET_NO_ACCOUNT:
				$sender->sendMessage($this->api->getMessage("player-never-connected", [$targetName],
					$sender->getName()));
				break;
			case ResultCode::RET_CANCELLED:
				$sender->sendMessage($this->api->getMessage("setmoney-failed", [], $sender->getName()));
				break;
			case ResultCode::RET_SUCCESS:
				$sender->sendMessage($this->api->getMessage("setmoney-setmoney", [$targetName, $amount],
					$sender->getName()));
				if ($target instanceof Player) {
					$target->sendMessage($this->api->getMessage("setmoney-set", [$amount], $target->getName()));
				}
				break;
		}
	}

	/**
	 * Shows a target player's balance.
	 *
	 * @param CommandSender $sender Sender.
	 * @param string $targetName Target name.
	 */
	public function seeMoney(CommandSender $sender, string $targetName) :void {
		$money = $this->api->myMoney($targetName);
		if ($money === false) {
			$sender->sendMessage($this->api->getMessage("player-never-connected", [$targetName], $sender->getName()));
		} else {
			$sender->sendMessage($this->api->getMessage("seemoney-seemoney", [$targetName, $money],
				$sender->getName()));
		}
	}

	/**
	 * Gives money to a target account.
	 *
	 * @param CommandSender $sender Sender.
	 * @param string $targetName Target name.
	 * @param int $amount Amount.
	 * @param ?Player $target Target name.
	 */
	public function giveMoney(CommandSender $sender, string $targetName, int $amount, ?Player $target) :void {
		$result = $this->api->addMoney($targetName, $amount, false, "economyapi.command.give");
		switch ($result) {
			case ResultCode::RET_INVALID:
				$sender->sendMessage($this->api->getMessage("givemoney-invalid-number", [$amount], $sender->getName()));
				break;
			case ResultCode::RET_SUCCESS:
				$sender->sendMessage($this->api->getMessage("givemoney-gave-money", [$amount, $targetName],
					$sender->getName()));
				if ($target instanceof Player) {
					$target->sendMessage($this->api->getMessage("givemoney-money-given", [$amount],
						$target->getName()));
				}
				break;
			case ResultCode::RET_CANCELLED:
				$sender->sendMessage($this->api->getMessage("request-cancelled", [], $sender->getName()));
				break;
			case ResultCode::RET_NO_ACCOUNT:
				$sender->sendMessage($this->api->getMessage("player-never-connected", [$targetName],
					$sender->getName()));
				break;
		}
	}

	/**
	 * Takes money from a target account.
	 *
	 * @param CommandSender $sender Sender.
	 * @param string $targetName Target name.
	 * @param int $amount Amount.
	 * @param ?Player $target Target name.
	 */
	public function takeMoney(CommandSender $sender, string $targetName, int $amount, ?Player $target) :void {
		$result = $this->api->reduceMoney($targetName, $amount, false, "economyapi.command.take");
		switch ($result) {
			case ResultCode::RET_INVALID:
				$current = $this->api->myMoney($targetName);
				$sender->sendMessage($this->api->getMessage("takemoney-player-lack-of-money",
					[$targetName, $amount, $current ?: 0], $sender->getName()));
				break;
			case ResultCode::RET_SUCCESS:
				$sender->sendMessage($this->api->getMessage("takemoney-took-money", [$targetName, $amount],
					$sender->getName()));
				if ($target instanceof Player) {
					$target->sendMessage($this->api->getMessage("takemoney-money-taken", [$amount],
						$target->getName()));
				}
				break;
			case ResultCode::RET_CANCELLED:
				$sender->sendMessage($this->api->getMessage("takemoney-failed", [], $sender->getName()));
				break;
			case ResultCode::RET_NO_ACCOUNT:
				$sender->sendMessage($this->api->getMessage("player-never-connected", [$targetName],
					$sender->getName()));
				break;
		}
	}

	/**
	 * Transfers money.
	 *
	 * @param Player $sender Sender.
	 * @param string $targetName Target name.
	 * @param int $amount Amount.
	 * @param ?Player $target Target name.
	 */
	public function payMoney(Player $sender, string $targetName, int $amount, ?Player $target) :void {
		if ($target === null && !$this->config->canPayOffline()) {
			$sender->sendMessage($this->api->getMessage("player-not-connected", [$targetName], $sender->getName()));
			return;
		}
		$result = $this->api->payMoney($sender->getName(), $targetName, $amount, false, "economyapi.command.pay");
		switch ($result->getCode()) {
			case ResultCode::RET_SUCCESS:
				$sender->sendMessage($this->api->getMessage("pay-success", [$amount, $targetName], $sender->getName()));
				if ($target instanceof Player) {
					$target->sendMessage($this->api->getMessage("money-paid", [$sender->getName(), $amount],
						$target->getName()));
				}
				break;
			case ResultCode::RET_NO_ACCOUNT:
				$sender->sendMessage($this->api->getMessage("player-never-connected", [$targetName],
					$sender->getName()));
				break;
			default:
				$sender->sendMessage($this->api->getMessage("pay-failed", [$targetName, $amount], $sender->getName()));
				break;
		}
	}

	/**
	 * Sets lang.
	 *
	 * @param CommandSender $sender Sender.
	 * @param string $lang Language code.
	 *
	 * @return bool True on success.
	 */
	public function setLang(CommandSender $sender, string $lang) :bool {
		if ($this->api->setPlayerLanguage($sender->getName(), $lang)) {
			$sender->sendMessage($this->api->getMessage("language-set", [$lang], $sender->getName()));
		} else {
			$sender->sendMessage(TextFormat::RED . "There is no language such as $lang");
		}
		return true;
	}

	/**
	 * Shows the sender's account status.
	 *
	 * @param CommandSender $sender Sender.
	 *
	 * @return bool True on success.
	 */
	public function showMyStatus(CommandSender $sender) :bool {
		$player = $this->requirePlayer($sender);
		if ($player === null) {
			return true;
		}
		$total = $this->api->getTotalMoney();
		$balance = $this->api->myMoney($player);
		$balanceValue = $balance === false ? 0 : $balance;
		$rate = $total > 0 && $balanceValue > 0 ? round(($balanceValue / $total) * 100, 2) : 0.0;
		$player->sendMessage($this->api->getMessage("mystatus-show", [$rate], $player->getName()));
		return true;
	}

	private function resolveBalance(Player $player) :int|bool {
		$balance = $this->api->myMoney($player);
		if ($balance !== false) {
			return $balance;
		}
		$this->api->createAccount($player, null, true, "command.mymoney");
		return $this->api->myMoney($player);
	}

	private function requirePlayer(CommandSender $sender) :?Player {
		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
			return null;
		}
		return $sender;
	}
}
