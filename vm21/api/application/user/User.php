<?php

class User {

    function __construct($db) {
        $this->db = $db;
    }

    public function login($login, $hash, $rnd) {
        $user = $this->db->getUserByLogin($login);
        if ($user) {
            $hashS = md5($user->password . $rnd);
            if (strval($hash) === strval($hashS)) {
                $token = md5($hash . strval(rand()));
                $this->db->updateToken($user->id, $token);
                return array(
                    'token' => $token,
                    'login' => $user->login,
                    'money' => $user->money
                );
            }
        }
        return false;
    }
    
    public function logout($token) {
        $user = $this->db->getUserByToken($token);
        if ($user) {
            return $this->db->updateToken($user->id, null);
        }
        return false;
    }

	public function registration($login, $hash){
		if (!($this->db->getUserByLogin($login))) {
			$hashs = md5($login . $hash);
			$token = md5($hashs . strval(rand()));
			$this->db->addUsers($login, $hash, $token);
            $this->db->updateToken($user->id, $token);
			$user = $this->db->getUserByLogin($login);
			return $user;
		}
	}

    public function getUserByToken($token) {
        return $this->db->getUserByToken($token);
    }
}