<?php

namespace solo\solofarm\storage;

use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\level\Position;

use solo\solofarm\CropControl;

class SleepStorage extends Storage{

	public function getName() : string{
		return "sleepStorage";
	}
	
	public function check()/* : void */{
		$level = null;
		$check = 0;
		
		foreach($this->data as $coord => $time){
			if($time < time()){
				$dat = explode(":", $coord);
				if($level === null || $level->getFolderName() !== $dat[0]){
					$level = Server::getInstance()->getLevelByName($dat[0]);
				}
				
				unset($this->data[$coord]);
				
				if($level === null){
					$this->data[$coord] = CropControl::getNextSleepTime();
					continue;
				}

				$pos = new Position((int) $dat[1], (int) $dat[2], (int) $dat[3], $level);
				
				if(CropControl::canContinuouslyGrow($pos)){
					if(CropControl::isFullGrown($pos)){ //if full grown, continously sleep
						$this->data[$coord] = CropControl::getNextSleepTime(); // until full grown? sleep again
					}else{
						CropControl::$farmStorage->data[$coord] = CropControl::getNextTime(); // break sleep
					}
				}
			}else if(++$check > 10){
				break;
			}
		}
	}
	
}