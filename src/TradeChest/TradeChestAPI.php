<?php
namespace TradeChest;

use pocketmine\Player;
use pocketmine\Server;
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
use TradeChest\data\data;
use TradeChest\Provider\JsonProvider;

use TradeChest\TradeChest;


class TradeChestAPI{

	const MAX_ITEM_COUNT = 65535;//内部にて使用している値であるため、可能であれば編集しないことをオススメします。

	public $data;
	public $tap = [];

	public function __construct(data $data){
		$this->data = $data;
	}

	public function buyItem($player,$block){
		if(($Rawdata = $this->data->getRawdata($block->asVector3(),$block->getLevel()->getName())) === null){
			$player->sendMessage("§e物々交換ショップの内部データは見つかりませんでした。");
			return;
		}

		$return = $this->data->decodemini($Rawdata);
		$BeforeItem = $return->getBeforeItem();
		if(!$player->getInventory()->contains($BeforeItem)){
			$player->sendMessage("§eインベントリに交換前のアイテム(".$BeforeItem->getId().":".$BeforeItem->getDamage()."(".$BeforeItem->getCount()."個))が存在しないため、取引することは出来ません。");
			return;
		}

		$sides = [
			2 => Vector3::SIDE_SOUTH,
			3 => Vector3::SIDE_NORTH,
			4 => Vector3::SIDE_EAST,
			5 => Vector3::SIDE_WEST,
		];

		if(!isset($sides[$block->getDamage()])){
			$player->sendMessage("§eタップした看板に張り付いているブロックはチェストではない可能性があるため、処理をすることは出来ません。");
			return;
		}

		$chestblock =  $block->getSide($sides[$block->getDamage()]);
		if($chestblock->getId() !== 54){
			$player->sendMessage("§eタップした看板に張り付いているブロックはチェストでは無いため、処理をすることは出来ません。");
			return;
		}

		$chest = $block->getLevel()->getTile($chestblock->asVector3());
		if(!$chest instanceof TileChest){
			$player->sendMessage("§eチェストの中身を取得出来ませんでした。");
			return;
		}

		$AfterItem = $return->getAfterItem();
		if(!$chest->getInventory()->contains($AfterItem)){
			$player->sendMessage("§b在庫切れの為、物々交換することは出来ません。");
			return;
		}

		if(!$chest->getInventory()->canAddItem($BeforeItem)){
			$player->sendMessage("§bチェストの中身はいっぱいの為、物々交換することは出来ません。");
			return;
		}

		$chest->getInventory()->removeItem($AfterItem);
		$player->getInventory()->addItem($AfterItem);

		$player->getInventory()->removeItem($BeforeItem);
		$chest->getInventory()->addItem($BeforeItem);

		$player->sendMessage("§a物々交換しました！");
	}

	public function createChestTrade(Player $player,Block $block): void{
		/*
			[TSHOP]
			1:0:64
			5:0:64
		*/
		$sides = [
			2 => Vector3::SIDE_SOUTH,
			3 => Vector3::SIDE_NORTH,
			4 => Vector3::SIDE_EAST,
			5 => Vector3::SIDE_WEST,
		];
		if(!isset($sides[$block->getDamage()])){
			$player->sendMessage("§eタップした看板に張り付いているブロックはチェストではない可能性があるため、物々交換ショップを作成出来ません。");
			return;
		}

		$chestblock = $block->getSide($sides[$block->getDamage()]);
		if($chestblock->getId() !== 54){
			$player->sendMessage("§eこの看板に張り付いているブロックはチェストでは無いため、物々交換ショップを作成することは出来ません。");
			return;
		}

		$tilesign = $block->getLevel()->getTile($block);
		if(!($tilesign instanceof Sign)){
			return;
		}

		$sign = $tilesign->getText();
		$data1 = explode(":",$sign[1]);
		$data2 = explode(":",$sign[2]);
		if(!self::is_natural($data1[0])||!self::is_natural($data2[0])){
			$player->sendMessage("不正な値があります。");
			return;
		}

		$beforeitem = self::getitem($data1[0],$data1[1] ?? 0,$data1[2] ?? 64);
		$afteritem = self::getitem($data2[0],$data2[1] ?? 0,$data2[2] ?? 64);
		if($beforeitem->getcount() > self::MAX_ITEM_COUNT||$afteritem->getcount() >  self::MAX_ITEM_COUNT){
			$player->sendMessage(self::MAX_ITEM_COUNT."個以上の個数のアイテムを指定することは出来ません。");
			return;
		}

		$tilesign->setText(
			"§b[物々交換]",
			"§a交換前§r: ".$beforeitem->getName()."(".$beforeitem->getCount()."個)",
			"§b交換後§r: ".$afteritem->getName()."(".$afteritem->getCount()."個)",
			"§aオーナー§r: ".$player->getName()
		);
		$this->data->setRawdata($block->asVector3(),$block->getLevel()->getName(),$this->data->encodemini($beforeitem,$afteritem,$player->getName()));
		$player->sendMessage("§b[TSHOP]看板を活性化しました。");
	}

	public static function getTradeChestbyChest(Block $chestblock): ?Block{ 
		$sides = [
			Vector3::SIDE_NORTH => 2,
			Vector3::SIDE_SOUTH => 3,
			Vector3::SIDE_WEST => 4,
			Vector3::SIDE_EAST => 5
		];
		$return = [];
		foreach($sides as $side => $damage){
			$signblock = $chestblock->getSide($side);
			if($signblock->getId() !== 68){
				continue;
			}
			//var_dump($signblock->getDamage());
			if($signblock->getDamage() !== $damage){
				continue;
			}
			if(self::isTradeChestBySign($signblock)){
				return $signblock;
			}
		}
		return null;
	}

	public static function isTradeChestByChest(Block $chestblock): bool{
		$sides = [
			Vector3::SIDE_WEST,
			Vector3::SIDE_EAST,
			Vector3::SIDE_NORTH,
			Vector3::SIDE_SOUTH
		];
		foreach($sides as $side){
			$signblock = $chestblock->getSide($side);
			if($signblock->getId() !== 68){
				continue;
			}
			if(self::isTradeChestBySign($signblock)){
				return true;
			}
		}
		return false;
	}

	public static function isTradeChestBySign(Block $block,String $signlabel = "§b[物々交換]"): bool{
		if($block->getId() !== 68){
			return false;
		}
		$tilesign = $block->getLevel()->getTile($block->asVector3());
		if(!$tilesign instanceof Sign){
			return false;
		}
		$sign = $tilesign->getText();
		if($sign[0] === $signlabel){
			return true;
		}
		return false;
	}

	public function isTapRestricted($player,$limit = 2): bool{//...?
		$name = $player->getName();
		$now = time();
		if(isset($this->tap[$name]) and $now - $this->tap[$name] < $limit){
			unset($this->tap[$name]);
			return false;
		}else{
			$this->tap[$name] = $now;
			return true;
		}
	}

	public static function getitem($id,$damage = 0,$count = 0): Item{
		return Item::get((int) $id,(int) $damage,(int) $count);
	}

	public static function is_natural($val){
		return (bool) preg_match('/\A[1-9][0-9]*\z/', $val);
	}

	public static function getPositionHash($x,?int $y = null,?int $z = null,$level = null): string{
		$levelName = $level;//
		if($x instanceof Position){
			$levelName = $x->getLevel()->getName();
		}else if($level instanceof Level){
			$levelName = $level->getName();
		}else if($level === null){
			$levelName = null;
		}
		return self::getVector3Hash($x,$y,$z).":".$levelName;
	}

	public static function getVector3Hash($x,?int $y = null,?int $z = null): string{
		if($x instanceof Vector3){
			return $x->x.",".$x->y.",".$x->z;
 		}
		return $x.",".$y.",".$z;
	}
}
