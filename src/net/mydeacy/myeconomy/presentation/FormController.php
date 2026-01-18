<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation;

use net\mydeacy\myeconomy\api\MyEconomyAPI;
use net\mydeacy\myeconomy\presentation\form\CustomForm;
use net\mydeacy\myeconomy\presentation\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_merge;
use function array_search;
use function is_int;
use function trim;

/**
 * Form controller for MyEconomy UI.
 */
final class FormController {

	private MyEconomyAPI $api;

	private CommandActions $actions;

	private TargetResolver $resolver;

	/**
	 * Creates a new instance.
	 *
	 * @param MyEconomyAPI $api Api.
	 * @param CommandActions $actions Actions.
	 * @param TargetResolver $resolver Resolver.
	 */
	public function __construct(MyEconomyAPI $api, CommandActions $actions, TargetResolver $resolver) {
		$this->api = $api;
		$this->actions = $actions;
		$this->resolver = $resolver;
	}

	/**
	 * Opens main menu.
	 *
	 * @param Player $player Player instance.
	 */
	public function openMainMenu(Player $player) :void {
		$title = $this->api->getMessage("menu-title", [], $player->getName());
		$content = $this->api->getMessage("menu-content", [], $player->getName());
		$form = new SimpleForm($title, $content);
		if ($player->hasPermission("economyapi.command.mymoney")) {
			$form->addButton($this->api->getMessage("menu-button-mymoney", [], $player->getName()),
				function(Player $player) :void {
					$this->actions->showMyMoney($player);
				});
		}
		if ($player->hasPermission("economyapi.command.topmoney")) {
			$form->addButton($this->api->getMessage("menu-button-topmoney", [], $player->getName()),
				function(Player $player) :void {
					$this->openTopMoneyForm($player);
				});
		}
		if ($player->hasPermission("economyapi.command.pay")) {
			$form->addButton($this->api->getMessage("menu-button-pay", [], $player->getName()),
				function(Player $player) :void {
					$this->openPayForm($player);
				});
		}
		if ($player->hasPermission("economyapi.command.seemoney")) {
			$form->addButton($this->api->getMessage("menu-button-seemoney", [], $player->getName()),
				function(Player $player) :void {
					$this->openSeeMoneyForm($player);
				});
		}
		if ($player->hasPermission("economyapi.command.setmoney")) {
			$form->addButton($this->api->getMessage("menu-button-setmoney", [], $player->getName()),
				function(Player $player) :void {
					$this->openSetMoneyForm($player);
				});
		}
		if ($player->hasPermission("economyapi.command.givemoney")) {
			$form->addButton($this->api->getMessage("menu-button-givemoney", [], $player->getName()),
				function(Player $player) :void {
					$this->openGiveMoneyForm($player);
				});
		}
		if ($player->hasPermission("economyapi.command.takemoney")) {
			$form->addButton($this->api->getMessage("menu-button-takemoney", [], $player->getName()),
				function(Player $player) :void {
					$this->openTakeMoneyForm($player);
				});
		}
		if ($player->hasPermission("economyapi.command.mystatus")) {
			$form->addButton($this->api->getMessage("menu-button-mystatus", [], $player->getName()),
				function(Player $player) :void {
					$this->actions->showMyStatus($player);
				});
		}
		if ($player->hasPermission("economyapi.command.setlang")) {
			$form->addButton($this->api->getMessage("menu-button-setlang", [], $player->getName()),
				function(Player $player) :void {
					$this->openSetLangForm($player);
				});
		}
		$this->sendForm($player, $form);
	}

	/**
	 * Opens top money form.
	 *
	 * @param Player $player Player instance.
	 */
	public function openTopMoneyForm(Player $player) :void {
		$form = new CustomForm($this->api->getMessage("form-topmoney-title", [], $player->getName()),
			function(Player $player, array $data) :void {
				$input = trim((string)($data[0] ?? ""));
				$pageInput = $input === "" ? "1" : $input;
				$page = CommandInputParser::parsePositiveInt($pageInput);
				if ($page === null) {
					$this->sendFormError($player);
					return;
				}
				$this->actions->showTopMoney($player, $page);
			});
		$form->addInput($this->api->getMessage("form-topmoney-page", [], $player->getName()), "1", "1");
		$this->sendForm($player, $form);
	}

	/**
	 * Opens pay form.
	 *
	 * @param Player $player Player instance.
	 */
	public function openPayForm(Player $player) :void {
		$onlineNames = $this->resolver->listOnlineNames();
		$manualOption = $this->api->getMessage("form-pay-online-manual", [], $player->getName());
		$options = array_merge([$manualOption], $onlineNames);
		$form = new CustomForm($this->api->getMessage("form-pay-title", [], $player->getName()),
			function(Player $player, array $data) use ($onlineNames) :void {
				$selected = $data[0] ?? null;
				$targetInput = trim((string)($data[1] ?? ""));
				$amountInput = (string)($data[2] ?? "");
				$amount = CommandInputParser::parsePositiveAmount($amountInput);
				$selectedName = is_int($selected) && $selected > 0 && isset($onlineNames[$selected - 1])
					? $onlineNames[$selected - 1]
					: null;
				$targetName = $targetInput !== "" ? $targetInput : ($selectedName ?? "");
				if ($targetName === "" || $amount === null) {
					$this->sendFormError($player);
					return;
				}
				$target = $this->resolver->resolve($targetName)[1];
				$this->actions->payMoney($player, $targetName, $amount, $target);
			});
		$form->addDropdown($this->api->getMessage("form-pay-online", [], $player->getName()), $options, 0);
		$form->addInput($this->api->getMessage("form-pay-target", [], $player->getName()));
		$form->addInput($this->api->getMessage("form-pay-amount", [], $player->getName()));
		$this->sendForm($player, $form);
	}

	/**
	 * Opens see money form.
	 *
	 * @param Player $player Player instance.
	 */
	public function openSeeMoneyForm(Player $player) :void {
		$form = new CustomForm($this->api->getMessage("form-seemoney-title", [], $player->getName()),
			function(Player $player, array $data) :void {
				$targetInput = trim((string)($data[0] ?? ""));
				if ($targetInput === "") {
					$this->sendFormError($player);
					return;
				}
				[$targetName] = $this->resolver->resolve($targetInput);
				$this->actions->seeMoney($player, $targetName);
			});
		$form->addInput($this->api->getMessage("form-seemoney-player", [], $player->getName()));
		$this->sendForm($player, $form);
	}

	/**
	 * Opens set money form.
	 *
	 * @param Player $player Player instance.
	 */
	public function openSetMoneyForm(Player $player) :void {
		$form = new CustomForm($this->api->getMessage("form-setmoney-title", [], $player->getName()),
			function(Player $player, array $data) :void {
				$targetInput = trim((string)($data[0] ?? ""));
				$amountInput = (string)($data[1] ?? "");
				$amount = CommandInputParser::parseNonNegativeAmount($amountInput);
				if ($targetInput === "" || $amount === null) {
					$this->sendFormError($player);
					return;
				}
				[$targetName, $target] = $this->resolver->resolve($targetInput);
				$this->actions->setMoney($player, $targetName, $amount, $target);
			});
		$form->addInput($this->api->getMessage("form-setmoney-player", [], $player->getName()));
		$form->addInput($this->api->getMessage("form-setmoney-amount", [], $player->getName()));
		$this->sendForm($player, $form);
	}

	/**
	 * Opens give money form.
	 *
	 * @param Player $player Player instance.
	 */
	public function openGiveMoneyForm(Player $player) :void {
		$form = new CustomForm($this->api->getMessage("form-givemoney-title", [], $player->getName()),
			function(Player $player, array $data) :void {
				$targetInput = trim((string)($data[0] ?? ""));
				$amountInput = (string)($data[1] ?? "");
				$amount = CommandInputParser::parseNonNegativeAmount($amountInput);
				if ($targetInput === "" || $amount === null) {
					$this->sendFormError($player);
					return;
				}
				[$targetName, $target] = $this->resolver->resolve($targetInput);
				$this->actions->giveMoney($player, $targetName, $amount, $target);
			});
		$form->addInput($this->api->getMessage("form-givemoney-player", [], $player->getName()));
		$form->addInput($this->api->getMessage("form-givemoney-amount", [], $player->getName()));
		$this->sendForm($player, $form);
	}

	/**
	 * Opens take money form.
	 *
	 * @param Player $player Player instance.
	 */
	public function openTakeMoneyForm(Player $player) :void {
		$form = new CustomForm($this->api->getMessage("form-takemoney-title", [], $player->getName()),
			function(Player $player, array $data) :void {
				$targetInput = trim((string)($data[0] ?? ""));
				$amountInput = (string)($data[1] ?? "");
				$amount = CommandInputParser::parseNonNegativeAmount($amountInput);
				if ($targetInput === "" || $amount === null) {
					$this->sendFormError($player);
					return;
				}
				[$targetName, $target] = $this->resolver->resolve($targetInput);
				$this->actions->takeMoney($player, $targetName, $amount, $target);
			});
		$form->addInput($this->api->getMessage("form-takemoney-player", [], $player->getName()));
		$form->addInput($this->api->getMessage("form-takemoney-amount", [], $player->getName()));
		$this->sendForm($player, $form);
	}

	/**
	 * Opens set lang form.
	 *
	 * @param Player $player Player instance.
	 */
	public function openSetLangForm(Player $player) :void {
		$languages = $this->api->getAvailableLanguages();
		if ($languages === []) {
			$this->sendFormError($player);
			return;
		}
		$current = $this->api->getPlayerLanguage($player->getName());
		$index = array_search($current, $languages, true);
		$defaultIndex = is_int($index) ? $index : 0;
		$form = new CustomForm($this->api->getMessage("form-setlang-title", [], $player->getName()),
			function(Player $player, array $data) use ($languages) :void {
				$index = $data[0] ?? null;
				if (!is_int($index) || !isset($languages[$index])) {
					$this->sendFormError($player);
					return;
				}
				$lang = $languages[$index];
				if ($this->api->setPlayerLanguage($player->getName(), $lang)) {
					$player->sendMessage($this->api->getMessage("language-set", [$lang], $player->getName()));
				} else {
					$player->sendMessage(TextFormat::RED . "There is no language such as $lang");
				}
			});
		$form->addDropdown($this->api->getMessage("form-setlang-label", [], $player->getName()), $languages,
			$defaultIndex);
		$this->sendForm($player, $form);
	}

	private function sendForm(Player $player, SimpleForm|CustomForm $form) :void {
		$player->sendForm($form);
	}

	private function sendFormError(Player $player) :void {
		$player->sendMessage($this->api->getMessage("form-invalid", [], $player->getName()));
	}
}
