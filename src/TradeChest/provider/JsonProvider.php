<?php
namespace TradeChest\Provider;

use pocketmine\item\Item;

class JsonProvider implements Provider{
	public $filename;
	public $data;
	
	public function __construct(String $filename){
		$this->filename = $filename;
		$this->data = $this->read($this->filename);
	}

	public function save(): void{
		$this->write($this->filename,$this->data);
	}

	public function set($key,$data): void{
		$this->data[$key] = $data;
	}

	public function get($key){
		return $this->data[$key];
	}

	public function delete($key): void{
		unset($this->data[$key]);
	}

	public function exists($key): bool{
		return isset($this->data[$key]);
	}

	public function setAll(array $data): void{
		$this->data = $data;
	}

	public function getAll($key): array{
		return $this->data;
	}

	public function read($filename){
		if(file_exists($filename)){
			$data = file_get_contents($filename);
			return json_decode($data,true);
		}else{
			return [];
		}
	}

	public function write($filename,$data){
		$json = json_encode($data,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING);
		file_put_contents($filename,$json);
	}
}
