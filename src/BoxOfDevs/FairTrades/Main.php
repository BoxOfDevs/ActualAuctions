<?php
namespace BoxOfDevs\FairTrades ; 
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerChatEvent;
use BoxOfDevs\FairTrades\chatTask;
use BoxOfDevs\FairTrades\ItemStore;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as C;
use pocketmine\Server;
 use pocketmine\Player;

class Main extends PluginBase implements Listener{
public function onEnable(){
$this->getServer()->getPluginManager()->registerEvents($this, $this);
$this->trade_part = [];
$this->line_breaker = C::GOLD . "--------------------------------------\n";
$this->trade_with = [];
$this->items = [];
 }
 public function onChat(PlayerChatEvent $event) {
     if($this->trade_part[$event->getPlayer()->getName()] === 2) {
         $event->setCancelled();
         $this->trade_with[$event->getPlayer()->getName()]->sendMessage("§l§b[Trade mate] " . $event->getPlayer()->getName() . ">§r§f " . $event->getMessage());
         $event->getPlayer()->sendMessage("§l§a[You] " . $event->getPlayer()->getName() . ">§r§f " . $event->getMessage());
     }
 }
public function setTradePhase(Player $player, $tradephase) {
	if(!is_numeric($tradephase)) {
		return false;
	} else {
		$this->trade_part[$player->getName()] = $tradephase;
		return true;
	}
}
 public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
switch($cmd->getName()){
	case "trade":
    $test = new ItemStore($this, $sender);
    if(!isset($this->trade_part[$sender->getName()])) {
        $this->trade_part[$sender->getName()] = 0;
    }
    if($args[0] === "part") {
        $sender->sendMessage(C::GREEN . "Your part" .$this->trade_part[$sender->getName()]);
    } elseif($args[0] === "setpart") {
        $this->setTradePhase($sender, $args[1]);
    }
    if($sender instanceof Player) {
	switch($this->trade_part[$sender->getName()]) {
		case 0:
		if(isset($args[0]) and $this->getServer()->getPlayer($args[0]) instanceof Player) {
			$player2 = $this->getServer()->getPlayer($args[0]);
            $sender->sendMessage($this->line_breaker .C::GREEN . "You asked " . $player2->getName() . " to trade with you");
			$player2->sendMessage($this->line_breaker .C::GREEN  . $sender->getName(). " want to start trading with you. Do /trade accept ". $sender->getName() . " to accept the trade or /trade decline ". $sender->getName() . " to decline the trade");
			$this->trade_part[$sender->getName()] = 1;
			$this->trade_part[$player2->getName()] = 1;
			$this->trade_with[$sender->getName()] = $player2;
		} else {
			$sender->sendMessage($this->line_breaker . C::RED ."Usage: /trade <player>");
		}
		break;
		case 1:
		if(isset($args[1]) and $this->getServer()->getPlayer($args[1]) instanceof Player) {
			$player2 = $this->getServer()->getPlayer($args[1]);
			if($this->trade_part[$player2->getName()] === 1 and $this->trade_with[$player2->getName()] === $sender and $args[0] === "accept") { //If the trade is accepted
				$player2->sendMessage($this->line_breaker . C::GREEN . $sender->getName() . " accepted your trade! You have 45 seconds to talk together about the trade\n" . $this->line_breaker);
				$sender->sendMessage($this->line_breaker . C::GREEN . "You accepted the trade! You have 45 seconds to talk together about the trade\n" . $this->line_breaker);
				$this->trade_part[$player2->getName()] = 2;
			    $this->trade_part[$sender->getName()] = 2;
				$this->getServer()->getScheduler()->scheduleDelayedTask(new  chatTask($this, $sender), 900); //shedule task so in 45 seconds, they will be switched to part 3;
				$this->getServer()->getScheduler()->scheduleDelayedTask(new  chatTask($this, $player2), 900);
				$this->getServer()->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $sender);
				$this->getServer()->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $player2);
				$this->trade_with[$sender->getName()] = $player2;
				
			} elseif($this->trade_part[$player2->getName()] === 1 and $this->trade_with[$player2->getName()] === $sender and $args[0] === "decline") {
				$player2->sendMessage($this->line_breaker . C::RED . $sender->getName() . " declined your trade.");
				$this->trade_part[$player2->getName()] = 0;
			    $this->trade_part[$sender->getName()] = 0;
				unset($this->trade_with[$player2->getName()]);
			} elseif($this->trade_part[$player2->getName()] === 1 and $this->trade_with[$player2->getName()] === $sender) {
				$sender->sendMessage($this->line_breaker . C::RED ."Please enter a correct choice: /trade accept <player> or /trade decline <player>");
			} elseif(!$this->trade_part[$player2->getName()] === 1 or !$this->trade_with[$player2->getName()] === $sender) {
				$sender->sendMessage($this->line_breaker . C::RED . $player2->getName(). " is not trading with you");
			}
		} else {
			$sender->sendMessage($this->line_breaker . C::GREEN . "Usage: /trade accept <player> or /trade decline <player>");
		}
		break;
		case 3:
		case 3.5:
		if(isset($args[0])) {
			$player2 = $this->trade_with[$sender->getName()];
			if($args[0] === "accept") { //If the trade is accepted
				$player2->sendMessage($this->line_breaker . C::GREEN .$sender->getName() . " accepted the trade.");
				$sender->sendMessage($this->line_breaker . C::GREEN . " You accepted the trade, waiting for " . $player2->getName() . " to accept the trade.");
				$this->trade_part[$sender->getName()] = 3.5;
				if($this->trade_part[$player2->getName()] === 3.5) { // if the other player already accepted the trade, they would have both accepted
				      $this->trade_part[$sender->getName()] = 4;
					  $this->trade_part[$player2->getName()] = 4;
				     $player2->sendMessage($this->line_breaker . C::GREEN . "You have both accepted the trade. Use /trade additem <item:damage> <count> and /trade removeitem  <item:damage> <count> to chose items to trade");
				     $sender->sendMessage($this->line_breaker . C::GREEN . "You have both accepted the trade. Use /trade additem <item:damage> <count> and /trade removeitem  <item:damage> <count> to chose items to trade");
				}
			} elseif($args[0] === "decline") {
				$player2->sendMessage($this->line_breaker . C::RED . $sender->getName() . " declined the trade.");
				$this->trade_part[$player2->getName()] = 0;
			    $this->trade_part[$sender->getName()] = 0;
				unset($this->trade_with[$player2->getName()]);
				unset($this->trade_with[$sender->getName()]);
			} else {
				$sender->sendMessage($this->line_breaker . C::RED ."Please enter a correct choice: /trade accept or /trade decline");
			}
		} else {
			$sender->sendMessage($this->line_breaker . C::RED . "Usage: /trade accept or /trade decline");
		}
		break;
		case 4:
		if(isset($args[2])) {
			$player2 = $this->trade_with[$sender->getName()];
            if(!isset($this->items[$sender->getName()])) {
                $this->items[$sender->getName()] = new ItemStore($this, $sender);
            }
            if(!isset($this->items[$player2->getName()])) {
                $this->items[$player2->getName()] = new ItemStore($this, $player2);
            }
			if($args[0] === "additem") {
                $item = Item::fromString($args[1]);
                $item->setCount($args[2]);
                $this->items[$sender->getName()]->addItem($item);
			} elseif($args[0] === "removeitem") {
                $item = Item::fromString($args[1]);
                $item->setCount($args[2]);
                $this->items[$sender->getName()]->removeItem($item);
            } else {
				$sender->sendMessage($this->line_breaker .  C::RED . "Please enter a correct choice: /trade additem <item:damage> <count>,  /trade removeitem <item:damage> <count>, /trade check or /trade finish");
			}
		} elseif($args[0] === "check") {
			$player2 = $this->trade_with[$sender->getName()];
            $sender->sendMessage($this->line_breaker .  C::YELLOW . "You purpose " . $this->items[$sender->getName()]->getAll());
            $sender->sendMessage(C::YELLOW .$player2->getName() . " purpose " . $this->items[$player2->getName()]->getAll());
        } elseif($args[0] === "finish") {
			$player2 = $this->trade_with[$sender->getName()];
			$this->trade_part[$sender->getName()] = 5;
			$this->trade_part[$player2->getName()] = 5;
            $player2->sendMessage($this->line_breaker . C::GREEN . $sender->getName(). " stopped the trade. ");
            $sender->sendMessage($this->line_breaker . C::GREEN . "You stopped the trade.");
            $player2->sendMessage($this->line_breaker . C::GREEN . "You purpose " .  $this->items[$player2->getName()]->getAll());
            $player2->sendMessage(C::GREEN . $sender->getName() . " purpose " . $this->items[$sender->getName()]->getAll());
            $sender->sendMessage($this->line_breaker .  C::YELLOW . "You purpose " . $this->items[$sender->getName()]->getAll());
            $sender->sendMessage(C::YELLOW .$player2->getName() . " purpose " . $this->items[$player2->getName()]->getAll());
        } else {
			$sender->sendMessage($this->line_breaker . C::RED . "Usage: /trade additem <item:damage> <count>,  /trade removeitem <item:damage> <count> or /trade finish");
		}
		break;
        case 6:
        case 5:
		$player2 = $this->trade_with[$sender->getName()];
        switch($args[0]) {
            case "accept":
			$this->trade_part[$sender->getName()] = 6;
            if($this->trade_part[$player2->getName()] = 6) {
                $player2->sendMessage($this->line_breaker . C::GREEN . $sender->getName(). " accepted the trade  !");
                $sender->sendMessage($this->line_breaker . C::GREEN . "You accepted the trade.");
                $player2->sendMessage($this->line_breaker .  C::GREEN . "You have both accepted the trade. Processing transfer...");
                $sender->sendMessage($this->line_breaker . C::GREEN . "You have both accepted the trade. Processing transfer...");
                $this->items[$sender->getName()]->transferItems($player2);
                $this->items[$player2->getName()]->transferItems($sender);
                $player2->sendMessage($this->line_breaker . C::GREEN . "Trade done !");
                $sender->sendMessage($this->line_breaker . C::GREEN . "Trade done !");
            unset($this->trade_part[$sender->getName()]);
            unset($this->trade_part[$player2->getName()]);
            unset($this->trade_witht[$sender->getName()]);
            unset($this->trade_with[$player2->getName()]);
            unset($this->items[$sender->getName()]);
            unset($this->items[$player2->getName()]);
            } else {
                $player2->sendMessage($this->line_breaker . C::GREEN . $sender->getName(). " accepted the trade. ");
                $sender->sendMessage($this->line_breaker . C::GREEN . "You accepted the trade. You can cancel this while the other player haven't accepted by doing /trade decline");
            }
            break;
            case "decline":
            $player2->sendMessage($this->line_breaker . C::RED . $sender->getName(). " declined the trade. All the trade has been cancelled.");
            $sender->sendMessage($this->line_breaker . C::RED . "You declined the trade. All the trade has been cancelled.");
            unset($this->trade_part[$sender->getName()]);
            unset($this->trade_part[$player2->getName()]);
            unset($this->trade_witht[$sender->getName()]);
            unset($this->trade_with[$player2->getName()]);
            unset($this->items[$sender->getName()]);
            unset($this->items[$player2->getName()]);
            break;
        }
        break;
	}
	return true;
	break;
    }
}
return false;
 }
}
