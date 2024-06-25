<?php

declare(strict_types=1);

namespace MohamadRZ\Scoreboard;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\scheduler\Task;

class Main extends PluginBase implements Listener {

	/** @var Main */
	private static $instance;

	/** @var Scoreboard */
	private $scoreboard;

	public function onLoad() {
		self::$instance = $this;
	}

	public static function getInstance(): Main {
		return self::$instance;
	}

	public function onEnable() {
		$this->getLogger()->info("Scoreboard plugin has been enabled!");

		$this->saveResource("config.yml");
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$title = $config->get("scoreboard_title", "Scoreboard");
		$lines = $config->get("scoreboard_lines", []);

		$this->scoreboard = new Scoreboard($title, $lines);

		$updateTitle = (bool) $config->get("update_title", false);
		if ($updateTitle) {
			$titleTasks = $config->get("scoreboard_title_task", ["Scoreboard"]);
			$titleTaskInterval = (int) $config->get("scoreboard_title_task_update_interval", 20);

			$this->getScheduler()->scheduleRepeatingTask(new class($this, $titleTasks) extends Task {
				private $plugin;
				private $titleTasks;
				private $currentTaskIndex = 0;

				public function __construct(Main $plugin, array $titleTasks) {
					$this->plugin = $plugin;
					$this->titleTasks = $titleTasks;
				}

				public function onRun(int $currentTick) {
					$title = $this->titleTasks[$this->currentTaskIndex++ % count($this->titleTasks)];
					$this->plugin->getScoreboard()->setTitle($title);
					foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
						$this->plugin->getScoreboard()->send($player);
					}
				}
			}, $titleTaskInterval);
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$updateInterval = (int) $config->get("update_interval", 20);

		$this->getScheduler()->scheduleRepeatingTask(new ScoreboardUpdateTask($this), $updateInterval);
	}

	public function onDisable() {
		$this->getLogger()->info("Scoreboard plugin has been disabled!");
	}

	/**
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$this->scoreboard->send($player);
	}

	public function getScoreboard(): Scoreboard {
		return $this->scoreboard;
	}
}
