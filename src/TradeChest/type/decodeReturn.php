<?php
namespace TradeChest\type;

use pocketmine\item\Item;

class decodeReturn{
	public $beforeitem;
	public $afteritem;
	public $owner;

	public function __construct(Item $beforeitem,Item $afteritem,String $owner){
		$this->beforeitem = $beforeitem;
		$this->afteritem = $afteritem;
		$this->owner = $owner;
	}

	public function getBeforeItem(): Item{
		return $this->beforeitem;
	}

	public function getAfterItem(): Item{
		return $this->afteritem;
	}

	public function getOwner(): String{
		return $this->owner;
	}
}
