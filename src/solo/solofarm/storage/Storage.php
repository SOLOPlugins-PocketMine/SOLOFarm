<?php

namespace solo\solofarm\storage;

use pocketmine\utils\Config;

use solo\solofarm\Main;

abstract class Storage{
	
	public $config;
	public $data;
	
	public abstract function getName() : string;
	
	public function getAll() : array{
		return $this->data;
	}
	
	public function load()/* : void */{
		$this->config = new Config(Main::getInstance()->getDataFolder() . $this->getName() . ".yml", Config::YAML);
		$this->data = $this->config->getAll();
	}
	
	public function save()/* : void */{
		$this->config->setAll($this->data);
		$this->config->save();
	}
	
}