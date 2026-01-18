<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\presentation\form;

use Closure;
use JsonSerializable;
use pocketmine\form\Form;
use pocketmine\player\Player;
use function is_int;

/**
 * Simple form definition.
 */
final class SimpleForm implements Form, JsonSerializable {

	private string $title;

	private string $content;

	/** @var array<int, array<string, string>> */
	private array $buttons = [];

	/** @var array<int, Closure(Player): void>|array<int, null> */
	private array $handlers = [];

	/**
	 * Creates a new instance.
	 *
	 * @param string $title Title.
	 * @param string $content Content.
	 */
	public function __construct(string $title, string $content = "") {
		$this->title = $title;
		$this->content = $content;
	}

	/**
	 * Adds button.
	 *
	 * @param string $text Text.
	 * @param ?Closure $handler Handler.
	 */
	public function addButton(string $text, ?Closure $handler = null) :void {
		$this->buttons[] = ["text" => $text];
		$this->handlers[] = $handler;
	}

	/**
	 * Handles response.
	 *
	 * @param Player $player Player instance.
	 * @param mixed $data Data.
	 */
	public function handleResponse(Player $player, $data) :void {
		if ($data === null || !is_int($data)) {
			return;
		}
		$handler = $this->handlers[$data] ?? null;
		if ($handler === null) {
			return;
		}
		$handler($player);
	}

	/**
	 * Serializes the form to JSON.
	 *
	 * @return array List of values.
	 */
	public function jsonSerialize() :array {
		return [
			"type"    => "form",
			"title"   => $this->title,
			"content" => $this->content,
			"buttons" => $this->buttons,
		];
	}
}
