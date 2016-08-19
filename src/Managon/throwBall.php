<?php

namespace Managon;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\math\Vector3;
use pocketmine\level\particle\DustParticle;

class throwBall extends PluginBase implements Listener{

	const GRAVITY = 9.80;//Acceleration of gravity...g
	const INI_VELO = 12;//Initial velocity ...Vo
	public $thrower;

	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		//do process of the throw every 1 tick
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new ThrowTask($this), 1);
	}

	public function onReceive(DataPacketReceiveEvent $event)
	{
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if ($packet instanceof UseItemPacket) {
			$this->throwBall($player);
			return;
		}
	}

	public function throwBall(PLayer $player)
	{
		$x = $player->x;
		$y = $player->y;
		$z = $player->z;
		$yaw = $player->getYaw() + 90;
		$pitch = -$player->getPitch();//Pitch in a game is contrast.
		$ini_height = $y + $player->getEyeHeight();//initial height

		$this->thrower[] = [new Vector3($x, $ini_height, $z), $yaw, $pitch, 0.05, $ini_height, $player->getDirection()];
	}

	public function throw()
	{
		$level = $this->getServer()->getDefaultLevel();
		for ($i=0; $i < count($this->thrower); $i++) { 
			$yaw = $this->thrower[$i][1];
			$pitch = $this->thrower[$i][2];
			if($pitch > 40) $pitch += -6;//微調整
      			elseif($pitch <= -40) $pitch += 10;//微調整
			//echo "Pitch = ".$pitch." Yaw = ".$yaw."\n";
			$t = $this->thrower[$i][3];
			$ini_height = $this->thrower[$i][4];
			$direction = $this->thrower[$i][5];

			//-gt^2/2 + Votsinθ

			$vecY = -((self::GRAVITY * $t**2) / 2) + (self::INI_VELO * $t * sin(deg2rad($pitch)));
			
//			自分用に説明
//			二次元の場合、$baseはx成分となるが三次元空間の場合、
// 			$baseは斜辺なので三角関数を使ってベクトルのx成分、y成分を出す。
//			これによって$yawがどんな値でもまっすぐに投射することができる。

			$base = self::INI_VELO * $t * cos(deg2rad($pitch));
			$vecX = $base * cos(deg2rad($yaw));
			$vecZ = $base * sin(deg2rad($yaw));
			$nextPos = $this->thrower[$i][0]->add($vecX, $vecY, $vecZ);

			$particle = new DustParticle($nextPos, 255, 255, 255);
			$level->addParticle($particle);
			if($level->getBlock($nextPos)->getId() !== 0 || $nextPos->y < 0)//If the ball hits the ground
			{
				unset($this->thrower[$i]);
				$this->thrower = array_values($this->thrower);
				return;
			}
			$this->thrower[$i][3] += 0.05;//cuz 1tick = 0.05sec
		}
	}
}

class ThrowTask extends PluginTask
{
	public function __construct(PluginBase $owner)
	{
		$this->owner = $owner;
	}

	public function onRun($currenttick)
	{
		$this->owner->throw();
	}
}
