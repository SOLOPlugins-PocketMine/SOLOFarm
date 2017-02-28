<?php

namespace solo\solofarm;

use pocketmine\utils\Config;

class Setting{
	
	private function __construct(){
		
	}
	
	public static $CHECK_CROP_INTERVAL;
	public static $CHECK_SLEEP_CROP_INTERVAL;
	
	public static function init()/* : void */{
		$config = new Config(Main::getInstance()->getDataFolder() . "setting.yml", Config::YAML, [
			"checkCropInterval" => 480,
			"checkSleepCropInterval" => 2400
		]);
		
		self::$CHECK_CROP_INTERVAL = (int) $config->get("checkCropInterval");
		self::$CHECK_SLEEP_CROP_INTERVAL = (int) $config->get("checkSleepCropInterval");
	}
	
}