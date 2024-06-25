<?php

declare(strict_types=1);

namespace MohamadRZ\Scoreboard;

use pocketmine\scheduler\Task;
use pocketmine\Server;

class ScoreboardUpdateTask extends Task {

	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}

	public function onRun(int $currentTick) {
		foreach (Server::getInstance()->getOnlinePlayers() as $player) {
			if ($player->isOnline()) {
				$scoreboard = $this->plugin->getScoreboard();
				$scoreboard->send($player);
			}
		}
	}
}
