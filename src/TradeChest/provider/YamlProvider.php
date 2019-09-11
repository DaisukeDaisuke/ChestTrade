<?php
namespace TradeChest\Provider;

use pocketmine\utils\Config;
use pocketmine\item\Item;

class YamlProvider implements Provider{
	public $filename;
	public $config;
	
	public function __construct(String $filename){
		$this->filename = $filename;
		$this->config = new Config($filename,Config::YAML);
	}

	public function save(): void{
		$this->config->save();
	}

	public function set($key,$data): void{
		$this->config->set($key,$data);
	}

	public function get($key){
		return $this->config->get($key);
	}

	public function delete($key): void{
		$this->config->delete($key);
	}

	public function exists($key): bool{
		return $this->config->exists($key);
	}

	public function setAll(array $data): void{
		$this->data = $data;
	}

	public function getAll($key): array{
		return $this->data;
	}
}
