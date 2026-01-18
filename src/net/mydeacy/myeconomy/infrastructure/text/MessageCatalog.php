<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\infrastructure\text;

use pocketmine\plugin\PluginBase;
use function array_keys;
use function fclose;
use function is_array;
use function is_resource;
use function json_decode;
use function str_replace;
use function stream_get_contents;
use function strtolower;

/**
 * Message catalog.
 */
final class MessageCatalog {

	/** @var array<string, array<string, mixed>> */
	private array $languages;

	private string $defaultLang;

	/**
	 * Creates a new instance.
	 *
	 * @param array<string, array<string, mixed>> $languages Languages.
	 * @param string $defaultLang Default language.
	 */
	public function __construct(array $languages, string $defaultLang) {
		$this->languages = $languages;
		$this->defaultLang = $defaultLang;
	}

	/**
	 * Loads messages from plugin resources.
	 *
	 * @param PluginBase $plugin Plugin.
	 * @param string $defaultLang Default lang.
	 *
	 * @return self
	 */
	public static function fromPlugin(PluginBase $plugin, string $defaultLang) :self {
		$languages = [];
		$languages["en"] = self::loadLanguage($plugin, "lang_en.json");
		$languages["ja"] = self::loadLanguage($plugin, "lang_ja.json");
		return new self($languages, self::normalizeLanguage($defaultLang));
	}

	/**
	 * Checks whether a language is available.
	 *
	 * @param string $language Language code.
	 *
	 * @return bool True on success.
	 */
	public function hasLanguage(string $language) :bool {
		$normalizedLanguage = self::normalizeLanguage($language);
		return isset($this->languages[$normalizedLanguage]);
	}

	/**
	 * Returns the default language code.
	 *
	 * @return string
	 */
	public function getDefaultLanguage() :string {
		return $this->defaultLang;
	}

	/**
	 * Returns available language codes.
	 *
	 * @return string[]
	 */
	public function listLanguages() :array {
		return array_keys($this->languages);
	}

	/**
	 * Returns command metadata for a language.
	 *
	 * @return array<string, string>
	 */
	public function getCommandMessage(string $command, string $language) :array {
		$lang = $this->selectLanguage($language);
		$commands = $this->languages[$lang]["commands"] ?? [];
		if (is_array($commands) && isset($commands[$command]) && is_array($commands[$command])) {
			return $commands[$command];
		}
		return [];
	}

	/**
	 * Returns message.
	 *
	 * @param string $key Key.
	 * @param array $params Params.
	 * @param string $language Language code.
	 * @param string $monetaryUnit Monetary unit.
	 *
	 * @return string
	 */
	public function getMessage(string $key, array $params, string $language, string $monetaryUnit) :string {
		$lang = $this->selectLanguage($language);
		$message = $this->languages[$lang][$key] ?? $key;
		$search = ["%MONETARY_UNIT%"];
		$replace = [$monetaryUnit];
		foreach ($params as $index => $value) {
			$search[] = "%" . ($index + 1);
			$replace[] = (string)$value;
		}
		return str_replace($search, $replace, (string)$message);
	}

	/**
	 * Normalizes a language code.
	 *
	 * @param string $language Language code.
	 *
	 * @return string
	 */
	public static function normalizeLanguage(string $language) :string {
		$loweredLanguage = strtolower($language);
		return match ($loweredLanguage) {
			"jp", "jpn" => "ja",
			"eng" => "en",
			default => $language,
		};
	}

	private function selectLanguage(string $language) :string {
		$normalizedLanguage = self::normalizeLanguage($language);
		if (isset($this->languages[$normalizedLanguage])) {
			return $normalizedLanguage;
		}
		return isset($this->languages[$this->defaultLang]) ? $this->defaultLang : "en";
	}

	/**
	 * Loads a language file.
	 *
	 * @return array<string, mixed>
	 */
	private static function loadLanguage(PluginBase $plugin, string $filename) :array {
		$resource = $plugin->getResource($filename);
		if ($resource === null || !is_resource($resource)) {
			return [];
		}
		$content = stream_get_contents($resource);
		fclose($resource);
		$decoded = json_decode($content ?: "", true);
		return is_array($decoded) ? $decoded : [];
	}
}
