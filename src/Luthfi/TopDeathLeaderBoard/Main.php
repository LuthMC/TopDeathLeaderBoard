<?php

declare(strict_types=1);

namespace Luthfi\TopDeathLeaderBoard;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\world\particle\FloatingTextParticle;

class Main extends PluginBase implements Listener {

    private $deaths;
    private $leaderboardLocation;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->deaths = new Config($this->getDataFolder() . "deaths.yml", Config::YAML, []);
        $this->leaderboardLocation = new Config($this->getDataFolder() . "leaderboard.yml", Config::YAML, [
            "x" => null,
            "y" => null,
            "z" => null,
            "world" => null
        ]);
    }

    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        $currentDeaths = $this->deaths->get($name, 0);
        $this->deaths->set($name, $currentDeaths + 1);
        $this->deaths->save();

        $this->updateFloatingText();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "topdeath") {
            if ($sender instanceof Player) {
                if ($sender->hasPermission("topdeath.cmd")) {
                    $this->showTopDeaths($sender);
                    return true;
                } else {
                    $sender->sendMessage("You do not have permission to use this command.");
                    return false;
                }
            } else {
                $sender->sendMessage("This command can only be used in-game.");
                return false;
            }
        } elseif ($command->getName() === "settopdeath") {
            if ($sender instanceof Player) {
                if ($sender->hasPermission("settopdeath.cmd")) {
                    $this->setLeaderboardLocation($sender);
                    return true;
                } else {
                    $sender->sendMessage("You do not have permission to use this command.");
                    return false;
                }
            } else {
                $sender->sendMessage("This command can only be used in-game.");
                return false;
            }
        }
        return false;
    }

    private function showTopDeaths(Player $player): void {
        $deathsArray = $this->deaths->getAll();
        arsort($deathsArray);
        $topDeaths = array_slice($deathsArray, 0, 10, true);

        $player->sendMessage("Top 10 Deaths:");
        $rank = 1;
        foreach ($topDeaths as $name => $deaths) {
            $player->sendMessage("#$rank $name - $deaths deaths");
            $rank++;
        }
    }

    private function setLeaderboardLocation(Player $player): void {
        $location = $player->getLocation();
        $this->leaderboardLocation->set("x", $location->getX());
        $this->leaderboardLocation->set("y", $location->getY());
        $this->leaderboardLocation->set("z", $location->getZ());
        $this->leaderboardLocation->set("world", $player->getWorld()->getFolderName());
        $this->leaderboardLocation->save();

        $player->sendMessage("Top deaths leaderboard location set!");

        $this->updateFloatingText();
    }

    private function updateFloatingText(): void {
        $worldName = $this->leaderboardLocation->get("world");
        if ($worldName === null) {
            return;
        }

        $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($world instanceof World) {
            $x = $this->leaderboardLocation->get("x");
            $y = $this->leaderboardLocation->get("y");
            $z = $this->leaderboardLocation->get("z");

            if ($x === null || $y === null || $z === null) {
                return;
            }

            $location = new Vector3($x, $y, $z);

            $deathsArray = $this->deaths->getAll();
            arsort($deathsArray);
            $topDeaths = array_slice($deathsArray, 0, 10, true);

            $text = "Top 10 Deaths:\n";
            $rank = 1;
            foreach ($topDeaths as $name => $deaths) {
                $text .= "#$rank $name - $deaths deaths\n";
                $rank++;
            }

            $particle = new FloatingTextParticle($text, "");
            $world->addParticle($location, $particle);
        }
    }
}