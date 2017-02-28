<?php

namespace solo\solofarm;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	public static $instance;
	
	public static function getInstance() : Main{
		return self::$instance;
	}

	//register blocks
	public function onLoad(){
		self::$instance = $this;
	}

	public function onEnable(){
		@mkdir($this->getDataFolder());

		Setting::init();
		CropControl::init();
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		CropControl::save();
	}

	/**
	 * @priority EventPriority::MONITOR
	 */
	public function onPlace(BlockPlaceEvent $event){
		if(CropControl::isCrop($event->getBlock())){
			CropControl::registerCrop($event->getBlock());
		}
	}
}
