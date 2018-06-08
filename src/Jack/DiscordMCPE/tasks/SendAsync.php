<?php
# +-------------------------------------------------+
# |             MCPEToDiscord - VER 1.3             |
# |-------------------------------------------------|
# |                                                 |
# | Made by : Jackthehack21 (gangnam253@gmail.com)  |
# |                                                 |
# | Build   : 055#A                                 |
# |                                                 |
# | Details : This plugin is aimed to give players  |
# |           A simple but fun view of what plugins |
# |           Can do to modify your MCPE experience.|
# |                                                 |
# +-------------------------------------------------+

namespace Jack\DiscordMCPE\tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SendAsync extends AsyncTask
{
    private $player, $webhook, $curlopts, $serv;

    public function __construct($player, $webhook, $curlopts, $serv)
    {
        $this->player = $player;
        $this->webhook = $webhook;
        $this->curlopts = $curlopts;
        $this->server = $serv
    }

    public function onRun()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->webhook);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(unserialize($this->curlopts)));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        $curlerror = curl_error($curl);

        $responsejson = json_decode($response, true);

        $success = false;
        
        $plugin = $this->$server->getPluginManager()->getPlugin('MCPEToDiscord');
        $error = $plugin->responses->get('send_fail');

        if($curlerror != ""){
            $error = $curlerror;
        }

        elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
            $error = $responsejson['message'];
        }

        elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204 OR $response === ""){
            $success = true;
        }

        $result = ["Response" => $response, "Error" => $error, "success" => $success];

        $this->setResult($result, true);
    }

    public function onCompletion(Server $server)
    {
        $plugin = $this->$server->getPluginManager()->getPlugin('MCPEToDiscord');
        if(!$plugin instanceof Main){
            return;
        }
        if(!$plugin->isEnabled()){
            return;
        }
        $plugin->backFromAsync($this->player, $this->getResult());
    }
}
