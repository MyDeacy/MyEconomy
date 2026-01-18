<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\infrastructure\language;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function strtolower;

/**
 * Player language storage.
 */
final class PlayerLanguageStore {

	private string $path;

	private string $defaultLang;

	/** @var array<string, string> */
	private array $map;

	/**
	 * Creates a new instance.
	 *
	 * @param string $path Path.
	 * @param string $defaultLang Default lang.
	 */
	public function __construct(string $path, string $defaultLang) {
		$this->path = $path;
		$this->defaultLang = $defaultLang;
		$this->map = [];
		$this->load();
	}

	/**
	 * Checks if a language is stored for the player.
	 *
	 * @param string $playerName Player name.
	 *
	 * @return bool True on success.
	 */
	public function has(string $playerName) :bool {
		return isset($this->map[strtolower($playerName)]);
	}

	/**
	 * Returns the stored language for a player.
	 *
	 * @param string $playerName Player name.
	 *
	 * @return string
	 */
	public function get(string $playerName) :string {
		$key = strtolower($playerName);
		return $this->map[$key] ?? $this->defaultLang;
	}

	/**
	 * Stores a language for a player.
	 *
	 * @param string $playerName Player name.
	 * @param string $language Language code.
	 */
	public function set(string $playerName, string $language) :void {
		$this->map[strtolower($playerName)] = $language;
	}

	/**
	 * Persists the language store.
	 */
	public function save() :void {
		$encoded = json_encode($this->map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		if ($encoded !== false) {
			file_put_contents($this->path, $encoded);
		}
	}

	private function load() :void {
		if (!file_exists($this->path)) {
			$this->map = [];
			return;
		}
		$decoded = json_decode((string)file_get_contents($this->path), true);
		$this->map = is_array($decoded) ? $decoded : [];
	}
}
