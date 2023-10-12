<?php

declare(strict_types=1);

namespace WildanDev;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class SimpleEconomy extends PluginBase {

    private static SimpleEconomy $instance;
    private array $playerBalances = [];

    public static function getInstance(): SimpleEconomy {
        return self::$instance;
    }

    public function onEnable(): void {
        self::$instance = $this;
        $this->saveResource("balances.yml");
        $this->loadBalances();
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if($cmd->getName() === "pays" && $sender instanceof Player && isset($args[0], $args[1]) && is_numeric($args[1])){
            $recipient = $this->getServer()->getPlayerByPrefix($args[0]);
            if($recipient !== null){
                $amount = (float) $args[1];
                if($this->reduceBalance($sender, $amount)){
                    $this->addBalance($recipient, $amount);
                    $sender->sendMessage("You paid $$amount to $recipient.");
                    $recipient->sendMessage("$sender paid you $$amount.");
                } else {
                    $sender->sendMessage("You don't have enough money.");
                }
            } else {
                $sender->sendMessage("Recipient not found or is not online.");
            }
            return true;
        } elseif($cmd->getName() === "balance" && $sender instanceof Player){
            $sender->sendMessage("Your balance: $" . $this->getBalance($sender));
            return true;
        } elseif($cmd->getName() === "seebal" && isset($args[0])){
            $player = $this->getServer()->getPlayerByPrefix($args[0]);
            if($player !== null && $player instanceof Player){
                $sender->sendMessage("$player's balance: $" . $this->getBalance($player));
            } else {
                $sender->sendMessage("Player not found or is not online.");
            }
            return true;
        } elseif($cmd->getName() === "topbal"){
            $topBalances = $this->getTopBalances(10); // Mengambil 10 pemain dengan saldo tertinggi
            $sender->sendMessage("Top 10 Balances:");
            foreach($topBalances as $index => $data){
                $playerName = $data['name'];
                $balance = $data['balance'];
                $sender->sendMessage(($index + 1) . ". $playerName: $$balance");
            }
            return true;
        } elseif($cmd->getName() === "addbal" && $sender->hasPermission("simpleeconomy.addbal") && isset($args[0], $args[1]) && is_numeric($args[1])){
            $target = $this->getServer()->getPlayerByPrefix($args[0]);
            if($target !== null && $target instanceof Player){
                $amount = (float) $args[1];
                $this->addBalance($target, $amount);
                $sender->sendMessage("Added $$amount to $args[0]'s balance.");
                $target->sendMessage("An amount of $$amount has been added to your balance.");
                return true;
            } else {
                $sender->sendMessage("Target player not found or is not online.");
                return false;
            }
        } elseif($cmd->getName() === "removebal" && $sender->hasPermission("simpleeconomy.removebal") && isset($args[0], $args[1]) && is_numeric($args[1])){
            $target = $this->getServer()->getPlayerByPrefix($args[0]);
            if($target !== null && $target instanceof Player){
                $amount = (float) $args[1];
                if($this->reduceBalance($target, $amount)){
                    $sender->sendMessage("Removed $$amount from $args[0]'s balance.");
                    $target->sendMessage("An amount of $$amount has been removed from your balance.");
                } else {
                    $sender->sendMessage("$args[0] doesn't have enough money.");
                }
                return true;
            } else {
                $sender->sendMessage("Target player not found or is not online.");
                return false;
            }
        }
        return false;
    }

    private function loadBalances(): void {
        $config = new Config($this->getDataFolder() . "balances.yml", Config::YAML);
        $this->playerBalances = $config->getAll();
    }

    public function onDisable(): void {
        $this->saveBalances();
    }

    public function saveBalances(): void {
        $config = new Config($this->getDataFolder() . "balances.yml", Config::YAML);
        $config->setAll($this->playerBalances);
        $config->save();
    }

    public function getBalance(Player $player): float {
        $playerName = $player->getName();
        return $this->playerBalances[$playerName] ?? 0.0;
    }

    public function addBalance(Player $player, float $amount): void {
        $playerName = $player->getName();
        if (!isset($this->playerBalances[$playerName])) {
            $this->playerBalances[$playerName] = 0.0;
        }
        $this->playerBalances[$playerName] += $amount;
        $this->saveBalances();
    }

    public function reduceBalance(Player $player, float $amount): bool {
        $playerName = $player->getName();
        if (!isset($this->playerBalances[$playerName])) {
            return false;
        }
        if ($this->playerBalances[$playerName] >= $amount) {
            $this->playerBalances[$playerName] -= $amount;
            $this->saveBalances();
            return true;
        }
        return false;
    }

    public function getTopBalances(int $limit): array {
        $topBalances = [];

        // Membuat salinan dari $this->playerBalances untuk diurutkan
        $sortedBalances = $this->playerBalances;
        arsort($sortedBalances);

        $count = 0;
        foreach($sortedBalances as $playerName => $balance){
            $topBalances[] = ['name' => $playerName, 'balance' => $balance];
            $count++;
            if($count >= $limit){
                break;
            }
        }

        return $topBalances;
    }
}
