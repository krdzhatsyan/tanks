<?php
require_once('db/DB.php');
require_once('user/User.php');
require_once('vmech/VMech.php');

class Application {

    function __construct() {
        $db = new DB();
        $this->user = new User($db);
        $this->vMech = new VMech($db);
    }

    /********************/
    /* Про пользователя */
    /********************/
    public function login($params) {
        if ($params['login'] && $params['hash'] && intval($params['rnd'])) {
            return $this->user->login($params['login'], $params['hash'], intval($params['rnd']));
        }
        return false;
    }

    public function logout($params) {
        if ($params['token']) {
            return $this->user->logout($params['token']);
        }
        return false;
    }

    public function registration($params) {
		if ($params['login'] && $params['hash']) {
			return $this->user->registration($params['login'], $params['hash']);
		}
		return false;
    }

    /************/
    /* Про игру */
    /************/

    public function addTank($params) {
        if ($params['token'] && 
            $params['team'] && 
            $params['hull'] && 
            $params['gun'] && 
            $params['shassis']
        ) {

			$flagOnOff=$this->vMech->getTeamBalance(); // флаг проверки на включение балансировки (та самая кнопачка вкл/выкл)
			$flag=$this->vMech->TeamCountCheck(); // флаг проверки на разницу между командами
			$team=$this->vMech->TeamCountCompare(); //здесь лежит какую команду нужно подкинуть (в которой меньше танков)


			
            $user = $this->user->getUserByToken($params['token']);
            if ($user) { 
				if ($flagOnOff) { // тут будет проверка на флаг (переключатель), если true, то включена балансировка команд 
					if ($flag) { //если разница между командами больше 2-х, то изменить команду на противоположную
						return $this->vMech->addTank(
						$user->id, 
						$params['team'] = $team, //подбрасываем нужную команду, вместо той что выбрал пользователь
						$params['hull'],
						$params['gun'],
						$params['shassis'],
						(intval($params['nuke'])) ? $params['nuke'] : 0,
						$user->money);
					} else { // ничего не делать и оставить как есть (без балансировки)
						return $this->vMech->addTank(
						$user->id, 
						$params['team'], 
						$params['hull'],
						$params['gun'],
						$params['shassis'],
						(intval($params['nuke'])) ? $params['nuke'] : 0,
						$user->money);
					}
				} else { //тоже ничего не делать и оставить как есть (без балансировки)
					return $this->vMech->addTank(
					$user->id, 
					$params['team'], 
					$params['hull'],
					$params['gun'],
					$params['shassis'],
					(intval($params['nuke'])) ? $params['nuke'] : 0,
					$user->money);
				}
                
            }
        }
        return false;
    }

    public function move($params) {
        if ($params['token'] && $params['direction']) {
            $user = $this->user->getUserByToken($params['token']);
            if ($user) {
                return $this->vMech->move($user->id, $params['direction']);
            }
        }
        return false;
    }

    public function shoot($params) {
        if ($params['token']) {
            $user = $this->user->getUserByToken($params['token']);
            if ($user) {
                return $this->vMech->shoot($user->id);
            }
        }
        return false;
    }

    public function boom($params) {
        if ($params['token']) {
            $user = $this->user->getUserByToken($params['token']);
            if ($user) {
                return $this->vMech->boom($user->id);
            }
        }
        return false;
    }

    public function updateScene($params) {
        if ($params['token']) { 
            $user = $this->user->getUserByToken($params['token']);
            if ($user) {
                return $this->vMech->updateScene($user->id);
            }
        }
        return false;
    }

    public function joinGame($params) {
        if($params['id'] && $params['x'] && $params['y'] && $params['hull'] && $params['gun'] && $params['shasshi']){
            return $this->vMech->tanks[] = $this->vMech->createTank(intval($params['id']), intval($params['x']), intval($params['y']), $params['hull'], $params['gun'], $params['shasshi']);
        } return false;
    }

    public function getConstructor() {
        return $this->vMech->getConstructor();
    }

    public function getRating() {
        return $this->vMech->getRating();
    }

    ///////////////////////// ТРУСОВ ОТВЕРНИСС!!!!!!!!!!! ////////////////////////////

    public function exam_4() {
        return $this->vMech->exam_4();
    }

    public function exam_8() {
        return $this->vMech->exam_8();
    }

    public function exam_9() {
        return $this->vMech->exam_9();
    }

    public function exam_10() {
        return $this->vMech->exam_10();
    }

    public function exam_11($params) {
        return $this->vMech->exam_11($params['word']);
    }

    public function exam_12($params) {
        if ($params['login']) { 
            return $this->vMech->exam_12($params['login']);
        }
    }

    public function examGetUserByLogin($params) {
        if ($params['login']) { 
            return $this->vMech->examGetUserByLogin($params['login'], $params['word']);
        }
    }

    public function exam_14($params) {
        if ($params['login']) { 
            return $this->vMech->exam_14($params['login']);
        }
    }

    public function exam_20() {
        return $this->vMech->exam_20();
    }

    public function exam_22($params) {
        if ($params['timer']) {
            return $this->vMech->exam_22($params['timer']);
        }
    }

    public function OnOff() {
        return $this->vMech->OnOff();
    }

	public function getTeamBalance() {
        return $this->vMech->getTeamBalance();
    }

	public function getTeamCount1() {
        return $this->vMech->getTeamCount1();
    }

	public function getTeamCount2() {
        return $this->vMech->getTeamCount2();
    }

}