<?php

namespace solo\solofarm;

use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\scheduler\Task;

use solo\solofarm\storage\CropStorage;
use solo\solofarm\storage\FarmStorage;
use solo\solofarm\storage\SleepStorage;

class CropControl{
	
	private function __construct(){
		
	}
	
	public static $cropStorage;
	public static $farmStorage;
	public static $sleepStorage;
	
	public static function init()/* : void */{
		self::$cropStorage = new CropStorage();
		self::$farmStorage = new FarmStorage();
		self::$sleepStorage = new SleepStorage();
		
		self::$cropStorage->load();
		self::$farmStorage->load();
		self::$sleepStorage->load();
		
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new class extends Task{
			public function onRun($currentTick){
				CropControl::$farmStorage->check();
			}
		}, 19);
		
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new class extends Task{
			public function onRun($currentTick){
				CropControl::$sleepStorage->check();
			}
		}, 300);
	}
	
	public static function save()/* : void */{
		self::$cropStorage->save();
		self::$farmStorage->save();
		self::$sleepStorage->save();
	}
	
	public static function getHash(Position $pos) : string{
		return $pos->getLevel()->getFolderName() . ":" . $pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ();
	}

	public static function isCrop($block) : bool{
		$id;
		if($block instanceof Block){
			$id = $block->getId();
		}else if($block instanceof Position){
			$id = $block->getLevel()->getBlockIdAt($block->getFloorX(), $block->getFloorY(), $block->getFloorZ());
		}else{
			return false;
		}

		switch($id){
			case Block::BEETROOT_BLOCK:
			case Block::CARROT_BLOCK:
			case Block::POTATO_BLOCK:
			case Block::WHEAT_BLOCK:
			case Block::COCOA_BLOCK:
			case Block::MELON_STEM:
			case Block::PUMPKIN_STEM:
			case Block::NETHER_WART_BLOCK:
				return true;

			case Block::SUGARCANE_BLOCK:
			case Block::CACTUS:
				return ($pos->getLevel()->getBlockIdAt($pos->getFloorX(), $pos->getFloorY() - 1, $pos->getFloorZ()) !== $id);
		}
		return false;
	}
	
	public static function getNextTime() : int{
		return time() + Setting::$CHECK_CROP_INTERVAL;
	}
	
	public static function getNextSleepTime() : int{
		return time() + Setting::$CHECK_SLEEP_CROP_INTERVAL;
	}
	
	//if you register crops, they are continously growing unless their chunk is unloaded
	public static function registerCrop(Position $pos)/* : void */{
		self::$farmStorage->data[self::getHash($pos)] = self::getNextTime();
	}
	
	//just return true when crops are full grown.
	//actually using at sleep crops or unregiser.
	public static function isFullGrown(Position $pos) : bool{
		$blockId = $pos->getLevel()->getBlockIdAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
		$blockData = $pos->getLevel()->getBlockDataAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
		
		switch($blockId){
			case Block::BEETROOT_BLOCK:
			case Block::CARROT_BLOCK:
			case Block::POTATO_BLOCK:
			case Block::WHEAT_BLOCK:
				return $blockData >= 7;

			case Block::MELON_STEM:
			case Block::PUMPKIN_STEM:
				if($blockData >= 7){
					$outputId = ($blockId === Block::MELON_STEM) ? Block::MELON_BLOCK : Block::PUMPKIN;
					$isBlocked = true;
					$check = [
							$pos->getLevel()->getBlockIdAt($pos->getFloorX() + 1, $pos->getFloorY(), $pos->getFloorZ()),
							$pos->getLevel()->getBlockIdAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ() + 1),
							$pos->getLevel()->getBlockIdAt($pos->getFloorX() - 1, $pos->getFloorY(), $pos->getFloorZ()),
							$pos->getLevel()->getBlockIdAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ() - 1)
					];
					foreach($check as $sideId){
						if($sideId === $outputId){
							return true;
						}else if($sideId === Block::AIR){
							$isBlocked = false;
						}
					}
					if($isBlocked){
						return true;
					}
				}
				return false;

			case Block::NETHER_WART_BLOCK:
				return $blockData >= 3;

			case Block::COCOA_BLOCK:
				return $blockData >= 8;

			case Block::CACTUS:
			case Block::SUGARCANE_BLOCK:
				$x = $pos->getFloorX();
				$y = $pos->getFloorY();
				$z = $pos->getFloorZ();
				for($up = 1; $up <= 2; ++$up){
					$atUp = $pos->getLevel()->getBlockIdAt($x, $y + $up, $z);
					if($atUp === Block::AIR){
						return false;
					}else if($atUp !== $blockId){
						return true;
					}
				}
				return true;
		}
		return true;
	}
	
	public static function canContinuouslyGrow(Position $pos) : bool{
		$id = $pos->getLevel()->getBlockIdAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
		return (
				$id === Block::MELON_STEM
				|| $id === Block::PUMPKIN_STEM
				|| $id === Block::CACTUS
				|| $id === Block::SUGARCANE_BLOCK
			);
	}
	
	//if no more need to grow, return true.
	//if block is not crop, return true.
	public static function updateCrop(Position $pos) : bool{
		$block = $pos->getLevel()->getBlock($pos);
		switch($block->getId()){
			case Block::BEETROOT_BLOCK:
			case Block::CARROT_BLOCK:
			case Block::POTATO_BLOCK:
			case Block::WHEAT_BLOCK:
			case Block::COCOA_BLOCK:
			case Block::MELON_STEM:
			case Block::PUMPKIN_STEM:
			case Block::CACTUS:
			case Block::NETHER_WART_BLOCK:
			case Block::SUGARCANE_BLOCK:
				$block->onUpdate(Level::BLOCK_UPDATE_RANDOM);
				return self::isFullGrown($block);

				/*
				
				if(block.getSide(Vector3.SIDE_DOWN).getId() == Block.SUGARCANE_BLOCK){
					return true;
				}
				if(new Random().nextInt(6) != 1){
					return false;
				}
				for(int y = 1; y <= 2; ++y){
					Block above = block.getLevel().getBlock(new Vector3(block.x, block.y + y, block.z));
					if(above.getId() == Block.SUGARCANE_BLOCK){
						continue;
					}
					if(above.getId() == Block.AIR){
						Block newState = Block.get(block.getId());
						BlockGrowEvent ev = new BlockGrowEvent(above, newState);
						Server.getInstance().getPluginManager().callEvent(ev);
						if(!ev.isCancelled()){
							block.getLevel().setBlock(above, ev.getNewState(), true);
						}
						break;
					}
				}
				return isFullGrown(block);
				
				*/
		}
		return false;
	}
	
	//for prevent leek server performance
	//if crops should grow continously, use this method.
	//cactus, sugarcane, pumpkin stem, melon stem....etc
	public static function sleepCrop(Position $pos)/* : void */{
		self::$sleepStorage->data[self::getHash($pos)] = self::getNextSleepTime();
	}
	
}