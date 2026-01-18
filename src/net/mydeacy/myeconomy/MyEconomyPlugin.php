<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy;

use net\mydeacy\myeconomy\api\MyEconomyAPI;
use net\mydeacy\myeconomy\application\AccountService;
use net\mydeacy\myeconomy\domain\AccountRepository;
use net\mydeacy\myeconomy\infrastructure\config\PluginConfig;
use net\mydeacy\myeconomy\infrastructure\language\PlayerLanguageStore;
use net\mydeacy\myeconomy\infrastructure\persistence\MysqlAccountRepository;
use net\mydeacy\myeconomy\infrastructure\persistence\SqliteAccountRepository;
use net\mydeacy\myeconomy\infrastructure\text\MessageCatalog;
use net\mydeacy\myeconomy\presentation\CommandHandler;
use net\mydeacy\myeconomy\presentation\PlayerListener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use RuntimeException;
use function is_dir;
use function mkdir;

/**
 * Main plugin entrypoint.
 */
final class MyEconomyPlugin extends PluginBase {

	private static ?self $instance = null;

	private ?MyEconomyAPI $api = null;

	private ?CommandHandler $commandHandler = null;

	private ?AccountRepository $repository = null;

	private ?PlayerLanguageStore $languageStore = null;

	private ?PluginConfig $configModel = null;

	/**
	 * Returns the plugin instance if loaded.
	 */
	public static function getInstance() :?self {
		return self::$instance;
	}

	/**
	 * Handles load.
	 */
	protected function onLoad() :void {
		self::$instance = $this;
	}

	/**
	 * Handles enable.
	 */
	protected function onEnable() :void {
		if (!is_dir($this->getDataFolder())) {
			mkdir($this->getDataFolder(), 0777, true);
		}
		$this->saveDefaultConfig();
		$this->configModel = new PluginConfig($this->getConfig());
		try {
			$this->repository = $this->createRepository($this->configModel);
		} catch (RuntimeException $e) {
			$this->getLogger()->error($e->getMessage());
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$service = new AccountService($this->repository, $this->configModel->getDefaultMoney(),
			$this->configModel->getMaxMoney());
		$messages = MessageCatalog::fromPlugin($this, $this->configModel->getDefaultLang());
		$this->languageStore = new PlayerLanguageStore(
			$this->getDataFolder() . "player_lang.json",
			MessageCatalog::normalizeLanguage($this->configModel->getDefaultLang())
		);
		$this->api = new MyEconomyAPI($service, $this->configModel, $messages, $this->languageStore);
		$this->commandHandler = new CommandHandler($this->api, $this->configModel, $this->getServer());
		$this->getServer()->getPluginManager()->registerEvents(
			new PlayerListener($this->api, $this->languageStore),
			$this
		);
	}

	/**
	 * Handles disable.
	 */
	protected function onDisable() :void {
		if ($this->languageStore !== null) {
			$this->languageStore->save();
		}
		if ($this->repository !== null) {
			$this->repository->close();
		}
		self::$instance = null;
	}

	/**
	 * Handles command.
	 *
	 * @param CommandSender $sender Sender.
	 * @param Command $command Command.
	 * @param string $label Label.
	 * @param array $args Args.
	 *
	 * @return bool True on success.
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) :bool {
		if ($this->commandHandler === null) {
			return false;
		}
		return $this->commandHandler->handle($sender, $command, $args);
	}

	/**
	 * Returns the public MyEconomy API.
	 *
	 * @throws RuntimeException if the plugin is not initialized.
	 */
	public function getApi() :MyEconomyAPI {
		if ($this->api === null) {
			throw new RuntimeException("MyEconomy is not initialized.");
		}
		return $this->api;
	}

	/**
	 * Returns the config model.
	 *
	 * @throws RuntimeException if the plugin is not initialized.
	 */
	public function getConfigModel() :PluginConfig {
		if ($this->configModel === null) {
			throw new RuntimeException("MyEconomy is not initialized.");
		}
		return $this->configModel;
	}

	private function createRepository(PluginConfig $config) :AccountRepository {
		$provider = $config->getProvider();
		$settings = $config->getProviderSettings();
		return match ($provider) {
			"sqlite" => new SqliteAccountRepository($this->getDataFolder() . (string)($settings["sqlite-file"] ?? "economy.sqlite")),
			"mysql" => new MysqlAccountRepository($settings),
			default => throw new RuntimeException("Invalid database provider: " . $provider),
		};
	}
}
