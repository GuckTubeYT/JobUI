<?php

namespace posmp\economyjob;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

class EconomyJob extends PluginBase implements Listener{
	/** @var Config */
	private $jobs;
	/** @var Config */
	private $player;

	/** @var  EconomyAPI */
	private $api;

	/** @var EconomyJob   */
	private static $instance;

	public function onEnable(){
		@mkdir($this->getDataFolder());
		if(!is_file($this->getDataFolder()."jobs.yml")){
			$this->jobs = new Config($this->getDataFolder()."jobs.yml", Config::YAML, yaml_parse($this->readResource("jobs.yml")));
		}else{
			$this->jobs = new Config($this->getDataFolder()."jobs.yml", Config::YAML);
		}
		$this->player = new Config($this->getDataFolder()."players.yml", Config::YAML);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->api = EconomyAPI::getInstance();
		self::$instance = $this;
	}

	private function readResource($res){
		$path = $this->getFile()."resources/".$res;
		$resource = $this->getResource($res);
		if(!is_resource($resource)){
			$this->getLogger()->debug("Tried to load unknown resource ".TextFormat::AQUA.$res.TextFormat::RESET);
			return false;
		}
		$content = stream_get_contents($resource);
		@fclose($content);
		return $content;
	}

	public function onDisable(){
		$this->player->save();
	}

	/**
	 * @priority LOWEST
	 * @ignoreCancelled true
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$job = $this->jobs->get($this->player->get($player->getName()));
		if($job !== false){
			if(isset($job[$block->getID().":".$block->getDamage().":break"])){
				$money = $job[$block->getID().":".$block->getDamage().":break"];
				if($money > 0){
					$this->api->addMoney($player, $money);
					$player->sendPopup("§b+ Money for Job");
				}else{
					$this->api->reduceMoney($player, $money);
				}
			}
		}
	}

	/**
	 * @priority LOWEST
	 * @ignoreCancelled true
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$job = $this->jobs->get($this->player->get($player->getName()));
		if($job !== false){
			if(isset($job[$block->getID().":".$block->getDamage().":place"])){
				$money = $job[$block->getID().":".$block->getDamage().":place"];
				if($money > 0){
					$this->api->addMoney($player, $money);
					$player->sendPopup("§b+ Money for Job");
				}else{
					$this->api->reduceMoney($player, $money);
				}
			}
		}
	}

	/**
	 * @return EconomyJob
	*/
	public static function getInstance(){
		return static::$instance;
	}

	/**
	 * @return array
	 */
	public function getJobs(){
		return $this->jobs->getAll();
	}

	/**
	 * @return array
	 *
	 */
	public function getPlayers(){
		return $this->player->getAll();
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
		switch(array_shift($params)){
			default:
				$this->FormJob($sender);
		}
		return true;
	}
	
	public function FormJob($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
				}
				switch($result){
					case "0";
					$this->FormJobJoin($player);
					break;
					
					case "1";
					$player->sendMessage("§l§7[§6§lJobsUI§r§7] §aYour Job : ".$this->player->get($player->getName()));
					break;
					
					case "2";
					$this->FormInfo($player);
					break;
					
					case "3";
					$job = $this->player->get($player->getName());
					$this->player->remove($player->getName());
					$player->sendMessage("§l§7[§6§lJobsUI§r§7] §cYou have been out from this job \"$job\"");
					break;
					
				}
			});
			$form->setTitle("§7§lJobUI");
			$form->setContent("Welcome To §l§aJobUI");
			$form->addButton("Join Job");
			$form->addButton("My Job");
			$form->addButton("Info JobUI");
			$form->addButton("Retire Job");
			$form->sendToPlayer($player);
			return $form;
	}
	
	public function FormJobJoin($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
				}
				switch($result){
					case "0";
					$this->player->set($player->getName(), "tree-cutter");
					$player->sendMessage("§l§7[§6§lJobsUI§r§7] §aApplied Career §e§lTree Cutter");
					break;
					
					case "1";
					$this->player->set($player->getName(), "miner");
					$player->sendMessage("§l§7[§6§lJobsUI§r§7] §aApplied Career §eMiner");
					break;
					
					case "2";
					$this->player->set($player->getName(), "melon");
					$player->sendMessage("§l§7[§6§lJobsUI§r§7] §aApplied Career §eMelon");
					break;
					
					case "3";
					$this->player->set($player->getName(), "pumpkin");
					$player->sendMessage("§l§7[§6§lJobsUI§r§7] §aApplied Career §ePumpkin");
					break;
					
					case "4";
					$this->player->set($player->getName(), "flower");
					$player->sendMessage("§l§7[§6§lJobsUI§r§7] §aApplied Career §eFlower");
					break;
					
				}
			});
			$form->setTitle("§7JobUI");
			$form->addButton("Tree Cutter\n4$", 1, "http://avengetech.me/items/17-0.png");
			$form->addButton("Miner\n8$", 1, "http://avengetech.me/items/1-0.png");
			$form->addButton("Melon\n5$", 1, "http://avengetech.me/items/103-0.png");
			$form->addButton("Pumpkin\n5$", 1, "http://avengetech.me/items/86-0.png");
			$form->addButton("Flower\n3$", 1, "http://avengetech.me/items/37-0.png");
			$form->sendToPlayer($player);
			return $form;
	}
	
	public function FormInfo($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data = null){
		$result = $data[0];
					
		if($result === null){
			return true;
		}
			switch($result){
				case 0:
				break;
			}
		});
		$form->setTitle("§7Info JobUI");
		$form->setContent("JobUI\n\nCreated By: GuckTubeYT\nThanks Poor GT For Helping Me\nDont Forget to Subscribe: \nPoor GT\nKocak Z\nGuckTube YT\nJacky Harmonis\n\n§c**Dont Edit §lConfig.yml§r§c**");
		$form->addButton("Okay!");	
		$form->sendToPlayer($player);
	}
}
