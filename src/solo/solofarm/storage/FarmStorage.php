<?php

namespace solo\solofarm\storage;

use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\level\Position;

use solo\solofarm\CropControl;

class FarmStorage extends Storage{
	
	public function getName() : string{
		return "farmStorage";
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
					$this->data[$coord] = CropControl::getNextTime();
					continue;
				}
				
				$pos = new Position((int) $dat[1], (int) $dat[2], (int) $dat[3], $level);
				
				if(CropControl::isCrop($pos)){
					if(! CropControl::updateCrop($pos)){ // need to grow continously?
						$this->data[$coord] = CropControl::getNextTime(); //grow continously
					}else if(CropControl::canContinuouslyGrow($pos)){
						CropControl::sleepCrop($pos); // full grown, now sleep
					}else{
						// full grown, need not to grow continously
					}
				}
			}else if(++$check > 10){
				break;
			}
		}
	}
	
}