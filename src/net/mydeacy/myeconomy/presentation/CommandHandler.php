<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation;

use net\mydeacy\myeconomy\api\MyEconomyAPI;
use net\mydeacy\myeconomy\infrastructure\config\PluginConfig;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_shift;
use function count;
use function is_string;
use function trim;

/**
 * Command handler for MyEconomy commands.
 */
final class CommandHandler {

	private CommandActions $actions;

	private FormController $forms;

	private TargetResolver $targetResolver;

	/**
	 * Creates a new instance.
	 *
	 * @param MyEconomyAPI $api Api.
	 * @param PluginConfig $config Config.
	 * @param Server $server Server.
	 */
	public function __construct(MyEconomyAPI $api, PluginConfig $config, Server $server) {
		$this->actions = new CommandActions($api, $config, $server);
		$this->targetResolver = new TargetResolver($server);
		$this->forms = new FormController($api, $this->actions, $this->targetResolver);
	}

	/**
	 * Handles command execution.
	 *
	 * @param CommandSender $sender Sender.
	 * @param Command $command Command.
	 * @param array $args Args.
	 *
	 * @return bool True on success.
	 */
	public function handle(CommandSender $sender, Command $command, array $args) :bool {
		if (!$command->testPermission($sender)) {
			return true;
		}
		return match ($command->getName()) {
			"mymoney" => $this->actions->showMyMoney($sender),
			"topmoney" => $this->handleTopMoney($sender, $args),
			"setmoney" => $this->handleSetMoney($sender, $args),
			"seemoney" => $this->handleSeeMoney($sender, $args),
			"givemoney" => $this->handleGiveMoney($sender, $args),
			"takemoney" => $this->handleTakeMoney($sender, $args),
			"pay" => $this->handlePay($sender, $args),
			"setlang" => $this->handleSetLang($sender, $args),
			"mystatus" => $this->actions->showMyStatus($sender),
			"money" => $this->handleMenu($sender),
			default => false,
		};
	}

	private function handleMenu(CommandSender $sender) :bool {
		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
			return true;
		}
		$this->forms->openMainMenu($sender);
		return true;
	}

	private function handleTopMoney(CommandSender $sender, array $args) :bool {
		$firstArg = count($args) > 0 && is_string($args[0]) ? $args[0] : null;
		$parsedPage = $firstArg === null ? null : CommandInputParser::parsePositiveInt($firstArg);
		$page = $parsedPage ?? 1;
		return $this->actions->showTopMoney($sender, $page);
	}

	private function handleSetMoney(CommandSender $sender, array $args) :bool {
		$player = array_shift($args);
		$amount = array_shift($args);
		if (!is_string($player) || $player === "" || !is_string($amount)) {
			return $this->sendUsage($sender, "/setmoney <player> <amount>");
		}
		$parsed = CommandInputParser::parseNonNegativeAmount($amount);
		if ($parsed === null) {
			return $this->sendUsage($sender, "/setmoney <player> <amount>");
		}
		[$targetName, $target] = $this->targetResolver->resolve($player);
		$this->actions->setMoney($sender, $targetName, $parsed, $target);
		return true;
	}

	private function handleSeeMoney(CommandSender $sender, array $args) :bool {
		$player = array_shift($args);
		if (!is_string($player) || trim($player) === "") {
			return $this->sendUsage($sender, "/seemoney <player>");
		}
		[$targetName] = $this->targetResolver->resolve($player);
		$this->actions->seeMoney($sender, $targetName);
		return true;
	}

	private function handleGiveMoney(CommandSender $sender, array $args) :bool {
		$player = array_shift($args);
		$amount = array_shift($args);
		if (!is_string($player) || $player === "" || !is_string($amount)) {
			return $this->sendUsage($sender, "/givemoney <player> <amount>");
		}
		$parsed = CommandInputParser::parseNonNegativeAmount($amount);
		if ($parsed === null) {
			return $this->sendUsage($sender, "/givemoney <player> <amount>");
		}
		[$targetName, $target] = $this->targetResolver->resolve($player);
		$this->actions->giveMoney($sender, $targetName, $parsed, $target);
		return true;
	}

	private function handleTakeMoney(CommandSender $sender, array $args) :bool {
		$player = array_shift($args);
		$amount = array_shift($args);
		if (!is_string($player) || $player === "" || !is_string($amount)) {
			return $this->sendUsage($sender, "/takemoney <player> <amount>");
		}
		$parsed = CommandInputParser::parseNonNegativeAmount($amount);
		if ($parsed === null) {
			return $this->sendUsage($sender, "/takemoney <player> <amount>");
		}
		[$targetName, $target] = $this->targetResolver->resolve($player);
		$this->actions->takeMoney($sender, $targetName, $parsed, $target);
		return true;
	}

	private function handlePay(CommandSender $sender, array $args) :bool {
		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
			return true;
		}
		$player = array_shift($args);
		$amount = array_shift($args);
		if (!is_string($player) || $player === "" || !is_string($amount)) {
			return $this->sendUsage($sender, "/pay <player> <amount>");
		}
		$parsed = CommandInputParser::parsePositiveAmount($amount);
		if ($parsed === null) {
			return $this->sendUsage($sender, "/pay <player> <amount>");
		}
		[$targetName, $target] = $this->targetResolver->resolve($player);
		$this->actions->payMoney($sender, $targetName, $parsed, $target);
		return true;
	}

	private function handleSetLang(CommandSender $sender, array $args) :bool {
		$lang = array_shift($args);
		if (!is_string($lang) || trim($lang) === "") {
			return $this->sendUsage($sender, "/setlang <language>");
		}
		return $this->actions->setLang($sender, $lang);
	}

	private function sendUsage(CommandSender $sender, string $usage) :bool {
		$sender->sendMessage(TextFormat::RED . "Usage: " . $usage);
		return true;
	}
}
