<?php
namespace TradeChest;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use TradeChest\data\data;
use TradeChest\type\decodeReturn;
use TradeChest\Provider\JsonProvider;

use TradeChest\TradeChest;


class TradeChest extends PluginBase{
	//public $blocks = [];

	public function onEnable(){
		$this->saveResource("config.yml");
		$config = new Config($this->getDataFolder()."config.yml",Config::YAML,[
			"dataformat" => "json"
		]);
		$Provider = null;
		$ProviderName = "";
		switch($config->get("dataformat")){
			case "yaml":
			case "yml":
				$Provider = new YamlProvider($this->getDataFolder()."blocks.yml");
				$ProviderName = "yaml";
			break;
			case "json":
			default:
				$Provider = new JsonProvider($this->getDataFolder()."blocks.json");
				$ProviderName = "json";
			break;
		}
		//$this->getLogger()->info("§eデータフォーマットを「".$ProviderName."」に指定致しました！");
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($Provider), $this);
	}
}