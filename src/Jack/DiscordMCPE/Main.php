<?php
# +-------------------------------------------------+
# |               McToDiscord - VER 1               |
# |-------------------------------------------------|
# |                                                 |
# | Made by : Jackthehack21 (gangnam253@gmail.com)  |
# |                                                 |
# | Build   : 037#A                                 |
# |                                                 |
# | Details : This plugin is aimed to give players  |
# |           A simple but fun view of what plugins |
# |           Can do to modify your MCPE experience.|
# |                                                 |
# +-------------------------------------------------+

namespace Jack\DiscordMCPE;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as C;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\{PlayerJoinEvent,PlayerQuitEvent, PlayerDeathEvent, PlayerChatEvent};;


class Main extends PluginBase implements Listener{
		
	public function onEnable(){
        if (!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
            //Use default, not PM.
        }
        $this->saveResource("config.yml");
        $this->cfg = new Config($this->getDataFolder()."config.yml", Config::YAML, []);
        $tmp = $this->cfg->get("discord");
        $this->enabled = false;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if($tmp == "false"){
            $this->getLogger()->info(C::RED."Plugin is disabled because discord was set to false in config.yml");
            return;
        }
        if($tmp == "true"){
            $url = $this->cfg->get("webhook_url");
            $query = "https://discordapp.com/api/webhooks/";
            if(substr($url, 0, strlen($query)) == $query) {
                $this->enabled = true;
                $this->getLogger()->info(C::GREEN."Plugin is Enabled working on: ".$this->getServer()->getIp());
                if($this->cfg->get('other_pluginEnabled?') === true){
                    $this->sendMessage($this->cfg->get('other_pluginEnabledFormat'));
                }
                return;
            } else {
                $this->getLogger()->warning(C::RED."You specified a invalid webhook link in config.yml");
                return;
            }
        } 
        $this->getLogger()->warning(C::RED."The config.yml option discord is NOT set to true / false the plugin is disabled");
        return;
	}
	
	public function onDisable(){
        $this->getLogger()->info(C::RED."Plugin Disabled");
        if($this->cfg->get('other_pluginDisabled?') === true){
            $this->sendMessage($this->cfg->get('other_pluginDisabledFormat'));
        }
    }

    /**
     * @param CommandSender $sender
     * @param Command $cmd
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
		if($cmd->getName() == "discord"){
            if(!$this->enabled) {
                $sender->sendMessage(C::RED."Plugin Disabled");
                return true;
            }
			if(!isset($args[0])) {
				$sender->sendMessage(C::RED."Please provide an argument! Usage: /discord (message).");
                return true;
			}
            if(!$sender instanceof Player){
                //$this->sendMessage("[**Console**] : ".implode(" ", $args));
                $sender->sendMessage(C::RED."Please run this command in-game");
                return true;
            }
			else{
                //gmmm
                $name = $sender->getNameTag();
                $msg = implode(" ", $args);
                $check = $this->getConfig()->get("discord");
                $this->getLogger()->info($check);
                $sender->sendMessage($check);
                if($this->enabled == false){ 
                    $sender->sendMessage(C::RED."Command is disabled by config.yml");
                    return true;
                } else {
                    $this->sendMessage("[".$sender->getNameTag()."] : ".implode(" ", $args));
                    $sender->sendMessage(C::AQUA.implode(" ", $args).C::GREEN." Was sent to discord.");
                }
			}
            return true;
		}
	    return false;
	}
    
    /**
     * @param PlayerJoinEvent $event
     */
	public function onJoin(PlayerJoinEvent $event){
        $playername = $event->getPlayer()->getNameTag();
        if($this->cfg->get("webhook_playerJoin?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerJoinFormat");
        $msg = str_replace("{player}",$playername,$format);
        $this->sendMessage($msg);
        // BASICHUD -> $event->getPlayer()->sendPopup(C::BOLD . C::GREEN . $playername. " ". C::BLACK." Welcome");
    }

    public function onQuit(PlayerQuitEvent $event){
        $playername = $event->getPlayer()->getNameTag();
        if($this->cfg->get("webhook_playerLeave?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerLeaveFormat");
        $msg = str_replace("{player}",$playername,$format);
        $this->sendMessage($msg);
    }

    public function onDeath(PlayerDeathEvent $event){
        $playername = $event->getPlayer()->getNameTag();
        if($this->cfg->get("webhook_playerDeath?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerDeathFormat");
        $msg = str_replace("{player}",$playername,$format);
        $this->sendMessage($msg);
    }

    public function onChat(PlayerChatEvent $event){
        $message = $event->getMessage();
        $ar = getdate();
        $time = $ar['hours'].":".$ar['minutes'];
        if($this->cfg->get("webhook_playerChat?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerChatFormat");
        $msg = str_replace("{msg}",$message, str_replace("{time}",$time, str_replace("{player}",$playername,$format)));
        $this->sendMessage($msg);
    }

    /**
     * @param $message
     */
    public function sendMessage(string $msg){
        if(!$this->enabled){
            return;
        }
        $name = $this->cfg->get("webhook_name");
        $webhook = $this->cfg->get("webhook_url");
        $curlopts = [
	        "content" => $msg,
            "username" => $name
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $webhook);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curlopts));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        $curlerror = curl_error($curl);
        curl_close($curl);
        return true;
    }
}
