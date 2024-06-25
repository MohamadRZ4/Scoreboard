<?php

declare(strict_types=1);

namespace MohamadRZ\Scoreboard;

use MohamadRZ\Placeholder\PlaceholderAPI;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\{
	SetDisplayObjectivePacket,
	SetScorePacket
};

class Scoreboard {

	/** @var string */
	private $title;

	/** @var array */
	private $lines = [];

	public function __construct(string $title, array $lines) {
		$this->title = $title;
		$this->lines = $lines;
	}

	public function setLine(int $line, string $text): void {
		$this->lines[$line] = $text;
	}

	public function removeLine(int $line): void {
		unset($this->lines[$line]);
	}

	public function send(Player $player): void {
		$this->clear($player);

		$replacedLines = [];
		foreach ($this->lines as $score => $text) {
			$replacedLines[$score] = PlaceholderAPI::replace($text, $player);
		}

		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = "Scoreboard";
		$pk->displayName = PlaceholderAPI::replace($this->title, $player);
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->sendDataPacket($pk);

		foreach ($replacedLines as $score => $text) {
			$entry = new ScorePacketEntry();
			$entry->objectiveName = "Scoreboard";
			$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
			$entry->customName = $text;
			$entry->score = $score;
			$entry->scoreboardId = $score;
			$pk = new SetScorePacket();
			$pk->type = SetScorePacket::TYPE_CHANGE;
			$pk->entries[] = $entry;
			$player->sendDataPacket($pk);
		}
	}

	public function clear(Player $player): void {
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = "";
		$pk->displayName = "";
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->sendDataPacket($pk);
	}

	public function setTitle(string $title): void {
		$this->title = $title;
		foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
			$this->send($player);
		}
	}

	public function setScoreboardLines(array $lines): void {
		$this->lines = $lines;
		$config = new Config(Main::getInstance()->getDataFolder() . "config.yml", Config::YAML);
		$config->set("scoreboard_lines", $lines);
		$config->save();
	}

	public function getScoreboardLines(): array {
		return $this->lines;
	}
}
