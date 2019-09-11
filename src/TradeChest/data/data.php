<?php
namespace TradeChest\data;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\tile\Sign;
use pocketmine\item\Item;
use pocketmine\block\Block;

use TradeChest\TradeChest;

use TradeChest\type\decodeReturn;
use TradeChest\TradeChestAPI;

use pocketmine\utils\Config;


class data{
	public $Config;

	public function __construct(Config $config){
		$this->Config = $config;
	}

	public function encodemini(item $beforeitem,item $afteritem,String $owner): string{
		return base64_encode(chr($beforeitem->getId()).chr($beforeitem->getDamage()).$this->countencode($beforeitem->getcount()).chr($afteritem->getId()).chr($afteritem->getDamage()).$this->countencode($afteritem->getCount()).chr(strlen($owner)).$owner);
	}

	public function decodemini(string $data): decodeReturn{
		$data1 = base64_decode($data);
		$beforeitem = Item::get(ord($data1[0]), ord($data1[1]), $this->countdecode($data1[2],$data1[3]));
		$afteritem = Item::get(ord($data1[4]), ord($data1[5]), $this->countdecode($data1[6],$data1[7]));
		return (new decodeReturn($beforeitem,$afteritem,$this->decodeowner($data)));
	}

	public function decodeowner(string $data): string{
		$data1 = base64_decode($data);
		return substr($data1,9,ord($data1[8]));
	}

	public function countencode(int $count): string{
		return chr($count >> 8).chr($count & 255);
	}

	public function countdecode(string $count,string $count1):  int{
		$test = ord($count);
		$test1 = ord($count1);
		return $test << 8 | $test1;
	}

	public function hasRawdata(Vector3 $vector3,string $levelname): bool{
		return $this->Config->exists(TradeChestAPI::getPositionHash($vector3,null,null,$levelname));
	}

	public function setRawdata(Vector3 $vector3,string $levelname,String $data): void{
		$this->Config->set(TradeChestAPI::getPositionHash($vector3,null,null,$levelname),$data);
		$this->save();
	}

	public function getRawdata(Vector3 $vector3,string $levelname): ?string{
		if(!$this->hasRawdata($vector3,$levelname)){
			return null;
		}
		return $this->Config->get(TradeChestAPI::getPositionHash($vector3,null,null,$levelname));
	}

	public function deleteRawdata(Vector3 $vector3,string $levelname): bool{
		if(!$this->hasRawdata($vector3,$levelname)){
			return false;
		}
		$this->Config->remove(TradeChestAPI::getPositionHash($vector3,null,null,$levelname));
		$this->save();
		return true;
	}

	public function IsOwner(Vector3 $Vector3,String $levelName,Player $player): ?String{
		return $this->getOwner($Vector3,$levelName) === $player->getName()||$player->isOP();
	}

	public function getOwner(Vector3 $Vector3,String $levelName): ?String{
		if(($Rawdata = $this->getRawdata($Vector3,$levelName)) === null){
			return null;
		}
		return $this->decodeowner($Rawdata);
	}

	public function save(){
		$this->Config->save();
	}
}