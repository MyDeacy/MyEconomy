<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation\form;

use Closure;
use JsonSerializable;
use pocketmine\form\Form;
use pocketmine\player\Player;
use function is_array;

/**
 * Custom form definition.
 */
final class CustomForm implements Form, JsonSerializable {

	private string $title;

	/** @var array<int, array<string, mixed>> */
	private array $content = [];

	private ?Closure $handler;

	/**
	 * Creates a new instance.
	 *
	 * @param string $title Title.
	 * @param ?Closure $handler Handler.
	 */
	public function __construct(string $title, ?Closure $handler = null) {
		$this->title = $title;
		$this->handler = $handler;
	}

	/**
	 * Adds label.
	 *
	 * @param string $text Text.
	 */
	public function addLabel(string $text) :void {
		$this->content[] = [
			"type" => "label",
			"text" => $text,
		];
	}

	/**
	 * Adds input.
	 *
	 * @param string $text Text.
	 * @param string $placeholder Placeholder.
	 * @param string $default Default.
	 */
	public function addInput(string $text, string $placeholder = "", string $default = "") :void {
		$this->content[] = [
			"type"        => "input",
			"text"        => $text,
			"placeholder" => $placeholder,
			"default"     => $default,
		];
	}

	/**
	 * Adds dropdown.
	 *
	 * @param string $text Text.
	 * @param string[] $options Options.
	 * @param int $defaultIndex Default index.
	 */
	public function addDropdown(string $text, array $options, int $defaultIndex = 0) :void {
		$this->content[] = [
			"type"    => "dropdown",
			"text"    => $text,
			"options" => $options,
			"default" => $defaultIndex,
		];
	}

	/**
	 * Handles response.
	 *
	 * @param Player $player Player instance.
	 * @param mixed $data Data.
	 */
	public function handleResponse(Player $player, $data) :void {
		if ($data === null || !is_array($data)) {
			return;
		}
		if ($this->handler !== null) {
			($this->handler)($player, $data);
		}
	}

	/**
	 * Serializes the form to JSON.
	 *
	 * @return array List of values.
	 */
	public function jsonSerialize() :array {
		return [
			"type"    => "custom_form",
			"title"   => $this->title,
			"content" => $this->content,
		];
	}
}
