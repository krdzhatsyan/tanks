<?php
require_once('ai/AI.php');

class VMech {
    function __construct($db) {
		$this->db = $db;
	}

	// взять танк по id
	private function getTankById($id, $tanks) {
		for ($i = 0; $i < count($tanks); $i++) {
			if ($tanks[$i]->user_id === $id) {
				return $tanks[$i];
			}
		}
		return null;
	}

	// взять танк по (х, у)
    private function getTankByXY($x, $y, $tanks) {
        for ($i = 0; $i < count($tanks); $i++) {
            if (intval($tanks[$i]->x) === $x && intval($tanks[$i]->y) === $y) {
                return $tanks[$i];
            }
        }
        return null;
	}

	// проверка, находятся ли (x, y) в здании
	private function isInnerBuilding($x, $y, $buildings) {
		for ($i = 0; $i < count($buildings); $i++) {
			$building = $buildings[$i];
			if ($x >= $building->x && 
				$x <= $building->x + $building->width - 1 &&
				$y >= $building->y &&
				$y <= $building->y + $building->height - 1
			) {
				return $building;
			}
		}
		return false;
	}

	// подобрать объект в танк
	private function raiseObject($tank, $objects) {
		// изменить cargo танка, объекта и если нужно удалить
		for($i = 0; $i < count($objects); $i++) {
			if ($tank->cargo - $objects[$i]->count >= 0) {
				$this->db->updateTankCargo($tank->id, $tank->cargo - $objects[$i]->count);
				$this->db->deleteObjectById($objects[$i]->id);
			} else {
				$this->db->updateTankCargo($tank->id, 0);
				$this->db->updateObjectCount($objects[$i]->id,$objects[$i]->count - $tank->cargo);
			}
		}
	}

	//перемещение пуль
	private function updateBullets() {
		$bullets = $this->db->getBullets();
		$tanks = $this->db->getTanks(); 
		$buildings = $this->db->getBuildings();
		$battle = $this->db->getBattle();
		$blocks = $this->db->getField();
		$field = $this->getField(
			$battle->fieldX, 
			$battle->fieldY, 
			$blocks
		);
		// идем по массиву пуль
		for ($i = 0; $i < count($bullets); $i++) {
			$bullet = $bullets[$i];
			if ($bullet->rangeBullet > 0) {
				// уменьшить rangeBullet
				$bullet->rangeBullet--;
				// если rangeBullet < 0, то удалить пулю
				if ($bullet->rangeBullet <= 0) {
					$this->db->deleteBulletById($bullet->id);
					continue;
				}
				// подвинуть пулю
				$x = $bullet->x;
				$y = $bullet->y;
				switch ($bullet->direction) {
					case 'left' : $x --; break;
					case 'right': $x ++; break;
					case 'up'   : $y --; break; 
					case 'down' : $y ++; break;
				}
				// если пуля улетела за край карты - удалить пулю
				if ($y < 0 || $x < 0 || $y >= $battle->fieldY || $x >= $battle->fieldX) {
					$this->db->deleteBulletById($bullet->id);
					continue;
				}
				// если пуля воткнулась в стену - удалить пулю и нанести дамаг
				if($field[$y][$x] > 0){
					$gun = $this->db->getGun($bullet->type);
					$damage = $gun->damage;
					for ($j = 0; $j < count($blocks); $j++) {
						if($blocks[$j]->x == $x && $blocks[$j]->y == $y) {
							$this->db->deleteBulletById($bullet->id);
							$field[$y][$x] -= $damage;
							$blocks[$j]->hp -= $damage;
							if($blocks[$j]->hp <= 0) {
								$this->db->deleteBlockById($blocks[$j]->id);
							} else {
								$this->db->updateBlockById($blocks[$j]->id, $blocks[$j]->hp);
							}
							break;
						}
					}
					$this->db->addBoom($x, $y, 4, 'bullet');
					continue;
				}
				// если пуля воткнулась в танк - удалить пулю и нанести дамаг и удалить танк (если надо)
				if($tank = $this->getTankByXY(intval($x), intval($y), $tanks)){
					if($tank->user_id && $tank->user_id == $bullet->user_id) continue;//если попал в себя
					$gun = $this->db->getGun($bullet->type);
					$damage = $gun->damage;
					$tank->hp -= $damage;
					if($tank->hp <= 0) {
						$killerTank = $bullet->user_id ? $this->db->getTankByUserId($bullet->user_id) : null; //не брать танк бота
						// результат бота НЕ записываем
						if ($bullet->user_id) {
							$this->db->addResult($tank, $bullet->user_id);
						}
						$this->db->deleteTankById($tank->id);
						if (!isset($tank->user_id)) {
							$this->randomTank($tank->team);
						}
						if ($killerTank && $tank->team == $killerTank->team) {
							$this->db->updateUserMoneyById($bullet->user_id, -intval($battle->moneyTank));
						} else {
							$this->db->updateUserMoneyById($bullet->user_id, intval($battle->moneyTank));
						}
						$count = rand(0, 20);
						if ($count > 0){
							$this->db->addObject($tank->x, $tank->y, $count, 1);
						} 
					} else {
						$this->db->updateTankById($tank->id, $tank->hp);
					}
					$this->db->deleteBulletById($bullet->id);
					$this->db->addBoom($x, $y, 4, 'bullet');
					continue;
				}
				// если пуля воткнулась в строение - удалить пулю и нанести дамаг и удалить строение (если надо)
				if($building = $this->isInnerBuilding(intval($x), intval($y), $buildings)){
					$gun = $this->db->getGun($bullet->type);
					$damage = $gun->damage;
					$building->hp -= $damage;
					$this->db->deleteBulletById($bullet->id);
					if($building->hp <= 0){
						$this->db->deleteBuildingById($building->id);
						$killerTank = $this->db->getTankByUserId($bullet->user_id);
						if ($building->team == $killerTank->team) {
							$this->db->updateUserMoneyById($bullet->user_id, -intval($battle->moneyBase));
						}else {
							$this->db->updateUserMoneyById($bullet->user_id, intval($battle->moneyBase));
						}
					} else {
						$this->db->updateBuildingById($building->id, $building->hp);
					}
					$this->db->addBoom($x, $y, 4, 'bullet');
					continue;
				}
				// проапдейтить пулю
				$this->db->updateBulletById($bullet->id, $x, $y, $bullet->rangeBullet);
			}
		}
	}

	private function updateBooms(){
		$booms = $this->db->getBooms();
		for($i = 0; $i < count($booms); $i ++){
			$booms[$i]->timeLife--;
			if($booms[$i]->timeLife <= 0){
				$this->db->deleteBoomById($booms[$i]->id);
			} else {
				$this->db->updateBoomById($booms[$i]->id, $booms[$i]->timeLife);
			}
		}
	}

	private function calcDistance($x1, $y1, $x2, $y2){
		return sqrt(pow($x1-$x2, 2) + pow($y1-$y2, 2));
	}

	private function createScene() {
		$battle = $this->db->getBattle();
		// все удалить
		$this->db->deleteAllTank();	// удалить все танки
		$this->db->deleteAllBlock(); // удалить все блоки
		$this->db->deleteAllObject(); // удалить все объекты
		$this->db->deleteAllBuilding();	// удалить все строения
		$this->db->deleteAllBoom();	// удалить все booms
		$this->db->deleteAllBullet(); // удалить все bullets
		// все заново создать
		// нарандомить блоки 
		$this->randomBlock($battle);
		// нарандомить базы
		$this->randomBase($battle);
		// нарандомить танки
		$this->randomTanks($battle);
	}
	
	private function randomBlock($battle) {
		for($i = 0; $i < $battle->fieldX; $i++) {
			for($j = 0; $j < $battle->fieldY; $j++) {
				$random = rand(0, 100);
				if($random < 30) {
					$this->db->addBlock($i, $j, 100);
				}
			}
		}
	}

	private function randomBase($battle){
        $mapSize = $this->db->getBattle();
        $fieldX = $mapSize->fieldX-2;
        $fieldY = $mapSize->fieldY-2;
        $x1 = rand(0,$fieldX);
        if (($x1 <= ceil($fieldX * 0.1)) || ($x1 >= floor($fieldX * 0.9))){
            $y1 = rand(0,$fieldY);
        } else {
            $rndFront = random_int(0, 1);
            if ($rndFront == 0){
                $y1 = ceil(rand(0, $fieldY * 0.1));
            } else {
                $y1 = floor(rand($fieldY * 0.9, $fieldY));
            }
        }
        $x2 = $fieldX - $x1;
        $y2 = $fieldY - $y1;
        $this->db->addBuilding(1, $x1, $y1, $battle->healthBase, 2, 2, "base");
        $this->db->addBuilding(2, $x2, $y2, $battle->healthBase, 2, 2, "base");
	}

	private function randomTank($teamId) {
		return $this->addTank(null, // это userId
							$teamId, 
							$this->db->randomHull()->id, 
							$this->db->randomGun()->id, 
							$this->db->randomShassis()->id, 
							null, // это ядрена бомба
							1000000); // это бабло
	}
	
	private function randomTanks($battle) {
		$count = $battle->aiTeamCount; // количество танков, которые рандомим для каждой команды
		$teams = $this->db->getTeams();
		for ($i = 0; $i < count($teams); $i++){
			for ($j = 0; $j < $count; $j++) {
				$this->randomTank($teams[$i]->id);
			}
		}		
	}

	private function getField($fieldX, $fieldY, $field) {
		$grassField = array();
		for ($i = 0; $i < $fieldY; $i++) {
			$array = array();
			for ($j = 0; $j < $fieldX; $j++) {
				$array[] = 0;
			}
			$grassField[] = $array; 
		}
		for($i=0; $i<count($field); $i++) {
			$grassField[$field[$i]->y][$field[$i]->x] = intval($field[$i]->hp);
		}
		return $grassField;
	}

	private function isBlock($x, $y, $blocks){
		for($i = 0; $i < count($blocks); $i ++){
			if($x == $blocks[$i]->x && $y == $blocks[$i]->y){
				return $blocks[$i];
			}
		}
		return null;
	}

	private function randomPointAroudBase($base, $radius){
		$buildings = $this->db->getBuildings();
		$blocks = $this->db->getField();
		$battle = $this->db->getBattle();
		$points = array();//массив точек вокруг базы
		if($radius > 0){
			for($i = $base->x - $radius; $i < ($base->x + $base->width + $radius); $i ++){
				for($j = $base->y - $radius; $j < ($base->y + $base->height + $radius); $j ++){
					if( $i >= 0 && $i < $battle->fieldX &&
						$j >= 0 && $j < $battle->fieldY && 
						!$this->isInnerBuilding($i, $j, $buildings) && 
						!$this->isBlock($i, $j, $blocks)
						){//точка не может быть вне карты, на камне, в здании
						$point = new stdClass();
						$point->x = $i;
						$point->y = $j;
						$points[] = $point;
					}
				}
			}
		}
		$rnd = rand(0, count($points) - 1);//рандомим номер точки
		return $points[$rnd];
	}

	private function calcAi() {
		$battle = $this->db->getBattle(); // взять битву из БД
		$field = $this->db->getField(); // взять блоки
		$tanks = $this->db->getTanks(); // взять танки
		$buildings = $this->db->getBuildings();
		$guns = $this->db->getGuns();
		$ai = new AI($battle, $field, $tanks, $buildings, $guns);
		foreach ($tanks as $tank) {
			if (!isset($tank->user_id)) { // если танк - бот
				$tankCommand = $ai->updateTank($tank);
				if ($tankCommand) {
					if ($tankCommand['command'] === 'move') {
						$this->move(null, $tankCommand['direction'], $tank);
					}
					if ($tankCommand['command'] === 'shoot') {
						if($tank->direction != $tankCommand['direction']) { //если смотрит не в ту сторону
							$this->rotateTank($tank, $tankCommand['direction']);
						} else {
							$this->shoot(null, $tank);
						}
					}
				}
			}
		}
	}

	private function getScene($battle, $userId){
		$scene = new stdClass();
		$scene->field = $this->getField(
								$battle->fieldX, 
								$battle->fieldY, 
								$this->db->getField()
							);
		$scene->users = $this->db->getUsers();
		$scene->tanks = $this->db->getTanks();
		$scene->buildings = $this->db->getBuildings();
		$scene->bullets = $this->db->getBullets();
		$scene->spriteMap = $this->db->getSpriteMap();
		$scene->booms = $this->db->getBooms();
		$scene->userMoney = $this->db->getUserById($userId)->money;
		$scene->objects = $this->db->getObjects();
		$scene->battle = $this->db->getBattle();
		return $scene;
	}
	
	private function rotateTank($tank, $direction) {
		return $this->db->updateTankXY($tank->id, $tank->x, $tank->y, $direction, $tank->moveTimeStamp);
	}

	public function getConstructor() {
		$array = array();
		$array['CONSTRUCTOR'] = array(
			'TEAM' => $this->db->getTeams(),
			'GUN_TYPE' => $this->db->getGuns(),
			'SHASSIS_TYPE' => $this->db->getShassis(),
			'HULL_TYPE' => $this->db->getHulls(),
			'NUKE' => $this->db->getNukes()
		);
		$array['DEFAULT_MONEY'] = $this->db->getBattle()->defaultMoney;
		return $array;
	}

	public function getRating()	{
		$kills = $this->db->getKills();
		$deaths = $this->db->getDeaths();
		$friendFires = $this->db->getFriendFire();
		$users = $this->db->getUsers();
		$rating = [];
		for($i = 0; $i < count($users); $i ++) {
			$elem = new stdClass();
			$elem->id = $users[$i]->id;
			$elem->login = $users[$i]->login;
			$rating[] = $elem;
		}
		for($i = 0; $i < count($rating); $i ++) {
			for($j = 0; $j < count($kills); $j ++) {
				if($kills[$j]->id == $rating[$i]->id) $rating[$i]->kills = $kills[$j]->kills;
			}
			for($j = 0; $j < count($deaths); $j ++) {
				if($deaths[$j]->id == $rating[$i]->id) $rating[$i]->deaths = $deaths[$j]->deaths;
			}
			for($j = 0; $j < count($friendFires); $j ++) {
				if($friendFires[$j]->id == $rating[$i]->id) $rating[$i]->friendFires = $friendFires[$j]->friendFires;
			}
		}
		return $rating;
	}

	// переместить танк
    public function move($userId, $direction, $t = null) {
		$tanks = $this->db->getTanks(); 
		$buildings = $this->db->getBuildings();
		$tank = $userId ? $this->getTankById($userId, $tanks) : $t;
		if ($tank) {
			$speed = $this->db->getSpeed($tank->shassisType)->speed;
			$timeStamp = round(microtime(true) * 1000);
			$moveTimeStamp = $tank->moveTimeStamp;
			if($tank->direction != $direction) { // если не смотрит в нужную сторону
				return $this->rotateTank($tank, $direction);
			}
			if ($timeStamp - $moveTimeStamp >= $speed){
				$battle = $this->db->getBattle();
				$field = $this->getField(
					$battle->fieldX, 
					$battle->fieldY, 
					$this->db->getField()
				);
				$x = $tank->x;
				$y = $tank->y;
				switch ($direction) {
					case 'left': $x--; break;
					case 'right': $x++; break;
					case 'up': $y--; break;
					case 'down': $y++; break;
				}
				// проверить наличие препятствий движению
				if ($y < 0 || $x < 0 || // если пытается уехать совсем влево или вверх
					$y >= count($field) || // если пытается уехать вниз
					$x >= count($field[$y]) || // если пытается уехать вправо
					$field[$y][$x] > 0 || // если на пути стена
					$this->getTankByXY(intval($x), intval($y), $tanks) || // если на пути танк
					$this->isInnerBuilding(intval($x), intval($y), $buildings) // если на пути строение
				) {
					return false;
				}
				// если подъехали к своей базе
				$building = null; // база танка
				for($i = 0; $i < count($buildings); $i++) {
					if($buildings[$i]->team == $tank->team) {
						$building = $buildings[$i];
						break;
					}
				}
				if($building) {
					for($i = -1; $i < $building->width + 1; $i++) {
						for($j = -1; $j < $building->height + 1; $j++) {
							if($x == $building->x + $i && $y == $building->y + $j) {
								$value = intval($this->db->getHull($tank->hullType)->cargo) - intval($tank->cargo);
								$this->db->updateUserMoneyById($userId, $value*2);
								$this->db->updateHpBase($building->hp + $value, $tank->team);
								$this->db->updateTankCargo($tank->id, intval($this->db->getHull($tank->hullType)->cargo));
							}
						}
					}
				}
				// if(intval($this->db->getHull($tank->hullType)->cargo) - intval($tank->cargo) > 0){
				// 	$building = $this->db->getBuilding($tank->team);
				// 	$buildingX = $building->x;
				// 	$buildingY = $building->y;
				// 	if((($x == ($buildingX+2)) && ($y == ($buildingY     || ($buildingY+1)))) ||
				// 	   (($x == ($buildingX+1)) && ($y == (($buildingY+2) || ($buildingY-1)))) ||
				// 	   (($x ==  $buildingX)    && ($y == (($buildingY+2) || ($buildingY-1)))) ||
				// 	   (($x == ($buildingX-1)) && ($y == (($buildingY+1) || $buildingY)))
				// 	){
				// 		$hp = intval($this->db->getHull($tank->hullType)->cargo) - intval($tank->cargo);
				// 		$this->db->updateUserMoneyById($userId, $hp*2);
				// 		$this->db->updateHpBase($hp,$tank->team);
				// 		$this->db->updateTankCargo($tank->id,intval($this->db->getHull($tank->hullType)->cargo));

				// 	}
				// }
				// если на пути объект
				if($objects = $this->db->getObjectsByXY($x, $y)) {
					$this->raiseObject($tank, $objects);
				}
				return $this->db->updateTankXY($tank->id, $x, $y, $direction, $timeStamp);
			}
		}
		return false;
	}
	
	//проверка конца игры
	public function checkEndGame() {
		return ($this->db->getBaseCount() === 2);
	}

	// выстрел (добавление пули в массив пулей)
	public function shoot($userId, $tank = null) {
		$tank = ($tank) ? $tank : $this->db->getTankByUserId($userId); // взять танк по user_id
		if ($tank) {
			$gun = $this->db->getGun($tank->gunType); // взять его орудие		
			// проверить прошло ли время перезарядки
			$currentTime = round(microtime(true) * 1000); // текущее время
			if ($currentTime - $tank->reloadTimeStamp >= $gun->reloadTime) {
				$y = $tank->y;
				$x = $tank->x;
				$this->db->updateReloadTimeStamp($tank->id, $currentTime); // изменить время перезарядки у танка
				return $this->db->addBullet($userId, $x, $y, $tank->direction, $gun->id, $gun->rangeFire); // добавить новую пулю в массив пуль
			}
		}
		return false;
	}

	public function boom($userId){
		$tank = $this->db->getTankByUserId($userId);
		if ($tank){
			if (intval($tank->nuke) > 0){
				$nukeDamage = intval($this->db->getNuke()->damage);
				$field = $this->db->getField();
				$tanks = $this->db->getTanks();
				$buildings = $this->db->getBuildings();
				$objects = $this->db->getObjects();
				foreach ($field as $wall){
					$distance = $this->calcDistance($wall->x, $wall->y,
													$tank->x, $tank->y);
					$damage = ($distance === 0) ? $nukeDamage : $nukeDamage / pow($distance, 2);
					if ($damage > $nukeDamage / 10){
						$hp = $wall->hp - $damage;
						if ($hp > 0) {
							$this->db->updateBlockById($wall->id, $hp);
						} else {
							$this->db->deleteBlockById($wall->id);
						}
					}
				}
				foreach ($buildings as $b){
					$distance = $this->calcDistance($b->x, $b->y,
													$tank->x, $tank->y);
					$damage = ($distance === 0) ? $nukeDamage : $nukeDamage / pow($distance, 2);
					if ($damage > $nukeDamage / 10){
						$hp = $b->hp - $damage;
						if ($hp > 0) {
							$this->db->updateBuildingById($b->id, $hp);
						} else {
							$this->db->deleteBuildingById($b->id);
						}
					}
				}
				foreach ($tanks as $t){
					$distance = $this->calcDistance($t->x, $t->y,
													$tank->x, $tank->y);
					$damage = (intval($distance) !== 0) ? ($nukeDamage / pow($distance, 2)): $nukeDamage;
					if ($damage > $nukeDamage / 10){
						$hp = $t->hp - $damage;
						if ($hp > 0) {
							$this->db->updateTankById($t->id, $hp);
						} else {
							$this->db->addResult($t, $tank->user_id);
							$this->db->deleteTankById($t->id);
							if (!isset($t->user_id)) {
								$this->randomTank($t->team);
							}
						}
					}
				}
				foreach ($objects as $o) {
					$distance = $this->calcDistance($o->x, $o->y,
													$tank->x, $tank->y);
					$damage = ($distance === 0) ? $nukeDamage : $nukeDamage / pow($distance, 2);
					if ($damage > $nukeDamage / 10){
						$this->db->deleteObjectById($o->id);
					}
				}
				$this->db->addBoom($tank->x, $tank->y, 10, 'nuke');
				return true;
			}
		}
		return false;
	}

	// обновить и вернуть сцену
	public function updateScene($userId) {
		if ($this->db->isTankExists($userId)) {
			$battle = $this->db->getBattle(); // взять битву из БД
			if ($this->checkEndGame()) {
				$timeStamp = $battle->timeStamp; // текущее время в битве
				$updateTime = $battle->updateTime; // время ДО обновления
				$currentTime = round(microtime(true) * 1000); // текущее время
				if ($currentTime - $timeStamp >= $updateTime) { // прошло достаточно времени
					// обновить сцену и вернуть её на клиент
					$this->db->updateBattleTimeStamp($battle->id, $currentTime);
					$this->updateBooms();
					$this->updateBullets();// сдвигаем пули
					$this->calcAi(); // подвигать ботов
					return $this->getScene($battle, $userId);
				}
				return false;
			}
			$this->createScene();
			return array('gameover' => true);
		}
		return array('die' => true);
	}

	// добавить танк (вычесть баблишко у пользователя)
	public function addTank(
		$userId, 
		$teamId, 
		$hullId, 
		$gunId, 
		$shassisId, 
		$nuke, 
		$money
	) {
		if(!$this->checkEndGame()) $this->createScene();
		$team = null;
		$gun = null;
		$hull = null;
		$shassi = null;
		// взять команды
		$teams = $this->db->getTeams();
		for ($i = 0; $i < count($teams); $i++){
			if ($teams[$i]->id == $teamId) {
				$team = $teams[$i];
				break;
			}
		}
		// взять типы корпусов
		$hulls = $this->db->getHulls();
		for ($i = 0; $i < count($hulls); $i++){
			if ($hulls[$i]->id == $hullId) {
				$hull = $hulls[$i];
				break;
			}
		}
		// взять типы пушек
		$guns = $this->db->getGuns();
		for ($i = 0; $i < count($guns); $i++){
			if ($guns[$i]->id == $gunId) {
				$gun = $guns[$i];
				break;
			}
		}
		// взять типы шасси
		$shassis = $this->db->getShassis();
		for ($i = 0; $i < count($shassis); $i++){
			if ($shassis[$i]->id == $shassisId) {
				$shassi = $shassis[$i];
				break;
			}
		}
		if ($team && $hull && $gun && $shassi && $money) {
			$base = $this->db->getBaseByTeamId($team->id);
			//рандомим точку вокруг базы в радиусе 2
			$randomPoint = $this->randomPointAroudBase($base, 2);
			$x = $randomPoint->x;
			$y = $randomPoint->y;
			//проверить, что хватает баблишка
			$battle = $this->db->getBattle(); 
			$defaultMoney = $battle->defaultMoney;
			$price = $hull->price + $gun->price + $shassi->price;
			$userMoney = $money >= $defaultMoney; // флажок, что бабки пользовательские
			$money = ($userMoney) ? $money : $defaultMoney;
			if ($money >= $price) {
				if ($userMoney) {
					$money -= $price;
					if ($userId) {
						$this->db->updateUserMoney($userId, $money);
					}
				}
				// добавить танк
				return $this->db->addTank(
					$userId, 
					$team->id, 
					$hull->hp,
					$hull->cargo,
					$hull->id,
					$gun->id,
					$shassi->id,
					$x,
					$y,
					$nuke
				);
			}
		}
		return false;
	}

	
	///////////////////////// ТРУСОВ ОТВЕРНИСС!!!!!!!!!!! ////////////////////////////
	
	public function exam_4() {
		return($this->db->exam_4());
	}

	public function exam_8() {
		return(($this->db->exam_10())/($this->db->exam_8()));
	}

	public function exam_9() {
		return($this->db->exam_9());
	}

	public function exam_10() {
		return($this->db->exam_10());
	}

	public function exam_11($word) {
		return($this->db->exam_11($word));
	}

	public function exam_12($login) {
		if ($this->db->getUserByLogin($login)->token != "") {
			return($this->db->exam_12($login));
		} else { 
			print_r('user offline');
		}
	}

	public function examGetUserByLogin($login, $word) {   // exam_13
		//print_r($this->db->getUserByLogin($login)->login);
		if ($this->db->getUserByLogin($login)) {
			return($this->db->getUserByLogin($login)->login . $word);
		}
	}

	public function exam_14($login) {   
		if ($this->db->getUserByLogin($login)->token != "") {
			$string = substr_replace($this->db->exam_14($login)->token, ' ', rand(1, 32), 1);
			$result = preg_replace("/\s+/", "", $string);
			var_dump($result);
		}
	}

	public function exam_20() {
		$sizeOfBuilding = 4* $this->db->getBaseCount(); 
		$result= $this->db->getBattle()->fieldX * $this->db->getBattle()->fieldY - $this->db->exam_20() - $sizeOfBuilding;
		return($result);
	}

	public function exam_22($timer) {
		$currentTime = round(microtime(true) * 1000);
		$num = $currentTime + $timer;
		for ( $i = $currentTime; $i <= $num; $i++){
			print_r($i . '@' . $num . '    ');
			if ($i == $num) {
				print_r('you died');
			} 
		}
	} 

	public function OnOff() { 
		return ($this->db->OnOff());
	}

	public function getTeamBalance() { //////////////////////////тут должна быть "проверка" какое значение пришло из бд
		$bal=$this->db->getBattle();
		if (intval($bal->TeamBalance) > 0) {
			return (true);
		} else {
			return (false);
		}
	}
	
	public function getTeamCount1() { //сколько танков в первой команде
		return ($this->db->getTeamCount1());
	}

	public function getTeamCount2() { //сколько танков во второй команде
		return ($this->db->getTeamCount2());
	}

	public function TeamCountCheck() { //если разница между командами больше 2-ч танков, то отправлять true
		$team1=$this->db->getTeamCount1();
		$team2=$this->db->getTeamCount2();
		if (abs($team1 - $team2) > 2) {
			return (true);
		} else {
			return (false);
		}
	} 

	public function TeamCountCompare() { //сравнение количества танков в командах, отправляется номер той где их меньше
		$team1=$this->db->getTeamCount1();
		$team2=$this->db->getTeamCount2();
		if($team1 > $team2) { return ('2');
		} else { return('1'); }
	}
}