<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\infrastructure\config;

use pocketmine\utils\Config;

/**
 * Plugin configuration.
 */
final class PluginConfig {

	private string $monetaryUnit;

	private bool $addOpAtRank;

	private int $defaultMoney;

	private int $maxMoney;

	private bool $allowPayOffline;

	private string $defaultLang;

	private string $provider;

	/** @var array<string, mixed> */
	private array $providerSettings;

	/**
	 * Creates a new instance.
	 *
	 * @param Config $config Config.
	 */
	public function __construct(Config $config) {
		$this->monetaryUnit = (string)$config->get("monetary-unit", "$");
		$this->addOpAtRank = (bool)$config->get("add-op-at-rank", false);
		$this->defaultMoney = (int)$config->get("default-money", 1000);
		$this->maxMoney = (int)$config->get("max-money", 9999999999);
		$this->allowPayOffline = (bool)$config->get("allow-pay-offline", true);
		$this->defaultLang = strtolower((string)$config->get("default-lang", "en"));
		$this->provider = strtolower((string)$config->get("provider", "sqlite"));
		$settings = $config->get("provider-settings", []);
		$this->providerSettings = is_array($settings) ? $settings : [];
	}

	/**
	 * Returns monetary unit.
	 *
	 * @return string
	 */
	public function getMonetaryUnit() :string {
		return $this->monetaryUnit;
	}

	/**
	 * Returns whether ops are included in top rankings.
	 *
	 * @return bool True on success.
	 */
	public function shouldAddOpAtRank() :bool {
		return $this->addOpAtRank;
	}

	/**
	 * Returns default money.
	 *
	 * @return int
	 */
	public function getDefaultMoney() :int {
		return max(0, $this->defaultMoney);
	}

	/**
	 * Returns max money.
	 *
	 * @return int
	 */
	public function getMaxMoney() :int {
		return max(0, $this->maxMoney);
	}

	/**
	 * Returns whether offline payments are allowed.
	 *
	 * @return bool True on success.
	 */
	public function canPayOffline() :bool {
		return $this->allowPayOffline;
	}

	/**
	 * Returns the default language code.
	 *
	 * @return string
	 */
	public function getDefaultLang() :string {
		return $this->defaultLang;
	}

	/**
	 * Returns the configured provider name.
	 *
	 * @return string
	 */
	public function getProvider() :string {
		return $this->provider;
	}

	/**
	 * Returns provider settings.
	 *
	 * @return array<string, mixed>
	 */
	public function getProviderSettings() :array {
		return $this->providerSettings;
	}
}
