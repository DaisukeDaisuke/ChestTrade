<?php
namespace TradeChest;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\tile\Sign;
use pocketmine\item\Item;
use pocketmine\block\Block;


use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Chest as TileChest;

use TradeChest\type\decodeReturn;
use TradeChest\Provider\Provider;
use TradeChest\Provider\JsonProvider;
use TradeChest\data\data;

class EventListener implements Listener{
	public $data;
	public $TradeChestAPI;

	public function __construct(Provider $Provider){
		$this->data = new data($Provider);
		$this->TradeChestAPI = new TradeChestAPI($this->data);
	}
	
	public function playerBlockTouch(PlayerInteractEvent $event){
		if($event->getBlock()->getID() == 68){
			$player = $event->getPlayer();
			$block = $event->getBlock();
			$name = $player->getName();
			if(TradeChestAPI::isTradeChestBySign($block)){
				if(!$this->TradeChestAPI->isTapRestricted($player)){
					$this->TradeChestAPI->buyItem($player,$block);
				}else{
					$player->sendMessage("アイテムを物々交換するならもう一度タップ");
				}
			}else if(TradeChestAPI::isTradeChestBySign($block,"[TSHOP]")||TradeChestAPI::isTradeChestBySign($block,"[物々交換]")){
				 $this->TradeChestAPI->createChestTrade($player,$block);
			}
		}else if($event->getBlock()->getId() === 54){
			$block = $event->getBlock();
			$player = $event->getPlayer();
			
			$signblock = TradeChestAPI::getTradeChestbyChest($block);
			if(!$signblock instanceof Block){
				return;
			}
			if(!$this->data->hasRawdata($signblock->asVector3(),$signblock->getLevel()->getName())){
				//物々交換ショップの内部データーは存在致しません。
				return;
			}
			if(!$this->data->IsOwner($signblock->asVector3(),$signblock->getLevel()->getName(),$player)){
				$player->sendMessage("§d[物々交換]あなたはこのチェストを開く権限を持っておりません。");
				$event->setCancelled();
			}
		}
	}

	public function Place(BlockPlaceEvent $event){
		if($event->getItem()->getID() === 54){
			$sides = [
				Vector3::SIDE_WEST,
				Vector3::SIDE_EAST,
				Vector3::SIDE_NORTH,
				Vector3::SIDE_SOUTH
			];
			$block = $event->getBlock();
			$player = $event->getPlayer();
			foreach($sides as $side){
				$sideblock = $block->getSide($side);
				if($sideblock->getId() !== 54){
					continue;
				}
				if(!$this->data->hasRawdata($sideblock->asVector3(),$sideblock->getLevel()->getName())){
					//物々交換ショップの内部データーは存在致しません。
					continue;
				}
				if(!TradeChestAPI::isTradeChestByChest($sideblock)){
					continue;
				}
				$player->sendMessage("§d[物々交換]物々交換ショップの近くにチェストを設置することは出来ません。");
				$event->setCancelled();
				return;
			}
		}
	}

	public function onblockBreak(BlockBreakEvent $event){
		if($event->getBlock()->getId() === 54){
			$block = $event->getBlock();
			$player = $event->getPlayer();
			$signblock = TradeChestAPI::getTradeChestbyChest($block);
			if(!$signblock instanceof Block){
				return;
			}
			if(!$this->data->hasRawdata($signblock->asVector3(),$signblock->getLevel()->getName())){
				//物々交換ショップの内部データーは存在致しません。
				return;
			}
			if($player->isOP()&&$this->TradeChestAPI->isTapRestricted($player,10)){
				$player->sendMessage("§b[物々交換]物々交換ショップを本当に壊しますか...?");
				$event->setCancelled();
				return;
			}
			if($this->data->IsOwner($signblock->asVector3(),$signblock->getLevel()->getName(),$player)){
				$player->sendMessage("§b物々交換ショップのデータを削除しました。");
				$this->data->deleteRawdata($signblock->asVector3(),$signblock->getLevel()->getName());
			}else{
				$player->sendMessage("§d[物々交換]あなたはこのショップを壊す権限を持っていません。");
				$event->setCancelled();
			}
		}else if($event->getBlock()->getId() === 68){
			$block = $event->getBlock();
			$player = $event->getPlayer();
			if(!TradeChestAPI::isTradeChestBySign($block)){
				return;
			}
			if(!$this->data->hasRawdata($block->asVector3(),$block->getLevel()->getName())){
				//物々交換ショップの内部データーは存在致しません。
				return;
			}
			if($player->isOP()&&$this->TradeChestAPI->isTapRestricted($player,10)){
				$player->sendMessage("§b[物々交換]物々交換ショップを本当に壊しますか...?");
				$event->setCancelled();
				return;
			}
			if($this->data->IsOwner($block->asVector3(),$block->getLevel()->getName(),$player)){
				$player->sendMessage("§b物々交換ショップのデータを削除しました。");
				$this->data->deleteRawdata($block->asVector3(),$block->getLevel()->getName());
			}else{
				$player->sendMessage("§d[物々交換]あなたはこのショップを壊す権限を持っていません。");
				$event->setCancelled();
			}
		}
	}
}