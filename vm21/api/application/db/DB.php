<?php

class DB {

    function __construct() {
        $host = 'localhost';
        $user = 'root';
        $password = '';
        $db = 'vmech';
        $this->conn = new mysqli($host, $user, $password, $db);
        if ($this->conn->connect_errno) {
            printf("Не удалось подключиться: %s\n", $this->conn->connect_error);
            exit();
        }
    }

    function __destruct() {
        $this->conn->close();
    }

    private function oneRecord($result) {
        while ($row = $result->fetch_object()) {
            return $row;
        }
        return null;
    }

    private function allRecords($result) {
        $res = array();
        while ($row = $result->fetch_object()) {
            $res[] = $row;
        }
        return $res;
    }

    private function getAllData($tableName) {
        $query = 'SELECT * FROM ' . $tableName;
        $result = $this->conn->query($query);
        return $this->allRecords($result);
    }

    private function getDataById($tableName, $id) {
        $query = 'SELECT * FROM ' . $tableName . ' WHERE id=' . $id;
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    private function getDataByTeam($tableName, $team) {
        $query = 'SELECT * FROM ' . $tableName . ' WHERE team=' . $team;
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

	private function getDataByUserId($tableName, $userId) {
        $query = 'SELECT * FROM ' . $tableName . ' WHERE user_id=' . $userId;
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function getUserByLogin($login) {
        $query = 'SELECT * FROM users WHERE login="' . $login . '"';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

	public function getUsers(){
		$query = 'SELECT * FROM users';
        $result = $this->conn->query($query);
        return $this->allRecords($result);
	}

    public function getUserByToken($token) {
        $query = 'SELECT * FROM users WHERE token="' . $token . '"';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function getUserById($id) {
        $query = 'SELECT * FROM users WHERE id="' . $id . '"';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function getAllUsers() { return $this->getAllData('users'); }

	public function addUsers($login, $hash , $token){
		$query = 'INSERT INTO users (login, password , token) VALUES ("'.$login . '" , "' . $hash . '" , "' . $token .'")';
		$this->conn->query($query);
        return true;
    }

    public function updateToken($id, $token) {
        $query = 'UPDATE users SET token="' . $token . '" WHERE id=' . $id;
        $this->conn->query($query);
        return true;
    }
    
    public function updateUserMoney($id, $money) {
        $query = 'UPDATE users SET money='.$money.' WHERE id=' . $id;
        $this->conn->query($query);
        return true;
    }

    public function getHulls() { return $this->getAllData('hull'); }
    public function getGuns() { return $this->getAllData('gun'); }
    public function getShassis() { return $this->getAllData('shassis'); }
    public function getTeams() { return $this->getAllData('team'); }
    public function getObjects() { return $this->getAllData('objects'); }

    public function getHull($id) { return $this->getDataById('hull', $id); }
    public function getGun($id) { return $this->getDataById('gun', $id); }
    public function getShassi($id) { return $this->getDataById('shassis', $id); }
    public function getTeam($id) { return $this->getDataById('team', $id); }

    public function getBattle() {
        $query = 'SELECT * FROM battle';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function getField(){ return $this->getAllData('field'); }
	public function getTanks(){ return $this->getAllData('tanks'); }
	public function getBuildings(){ return $this->getAllData('building'); }
    public function getBullets(){ return $this->getAllData('bullets'); }
    public function getBooms(){ return $this->getAllData('booms'); }

    public function getSpriteMap(){ return $this->getAllData('sprite_map'); }

    public function getSpeed($shassisType){ return $this->getDataById('shassis', $shassisType); }
	public function getTankByUserId($userId){
        
        return $this->getDataByUserId('tanks', $userId); 
    }
	public function getBaseById($id){ return $this->getDataById('building', $id); }

    public function getBuilding($team){return $this->getDataByTeam('building',$team);}

    public function addTank(
        $userId, 
        $teamId, 
        $hp, 
        $cargo, 
        $hullId, $gunId, $shassiId, $x, $y, $nuke
    ) {
        if ($userId) {
            $query = 'DELETE FROM tanks WHERE user_id=' . $userId;
            $this->conn->query($query);
        }
        $query = 'INSERT INTO tanks 
                (user_id, team, x, y, hp, cargo, hullType, gunType, shassisType, nuke ) 
                VALUES 
                ('.(($userId) ? $userId : "null").', 
                 '.$teamId.',
				 '.$x.',
				 '.$y.',
                 '.$hp.', 
                 '.$cargo.', '.$hullId.', '.$gunId.', '.$shassiId.', 
                 '.(($nuke) ? $nuke : "null").')';
        $this->conn->query($query);
        return true;
    }
    public function addObject($x, $y, $count, $type){
        $query = 'INSERT INTO objects (x, y, count, type) VALUES ('.$x.','.$y.','.$count.','.$type.')';
        $this->conn->query($query);
        return true;
    }
	public function addBullet($userId, $x, $y, $direction, $type, $rangeBullet){
		$query = 'INSERT INTO bullets 
                (user_id, x, y, direction, type, rangeBullet)
				VALUES 
                ('.($userId ? $userId : 'NULL').',
                '.$x.', 
                 '.$y.',
				 "'.$direction.'",
				 '.$type.',
                 '.$rangeBullet.')';
		$this->conn->query($query);
        return true;
    }

    public function addBuilding($team, $x, $y, $hp, $width, $height, $type) {
        $query = 'INSERT INTO building 
                (team, x, y, hp, width, height, type )
                VALUES 
                ('.$team.', 
                 '.$x.',
                 '.$y.',
                 '.$hp.',
                 '.$width.',
                 '.$height.',
                 "'.$type.'")';
        $this->conn->query($query);
        return true;
    }
    
    public function addBoom($x, $y, $timeLife, $type){
		$query = 'INSERT INTO booms (x, y, timeLife, type) VALUES ('.$x.', '.$y.', '.$timeLife.', "'.$type.'")';
		$this->conn->query($query);
        return true;
    }

    public function addBlock($x, $y, $hp) {
        $query = 'INSERT INTO field (x, y, hp) VALUES ('.$x.', '.$y.', '.$hp.')';
		$this->conn->query($query);
        return true;
    }

    public function updateBattleTimeStamp($id, $timeStamp) {
        $query = 'UPDATE battle SET timeStamp='.$timeStamp.' WHERE id='.$id;
        $this->conn->query($query);
        return true;
    }

    public function updateTankXY($id, $x, $y, $direction, $timeStamp) {
        $query = 'UPDATE  tanks SET  x='.$x.', y= '.$y.', direction = "'.$direction.'", moveTimeStamp = '.$timeStamp.' WHERE id='.$id;
        $this->conn->query($query);
        return true;
    }

    public function updateBulletById($bulletId, $x, $y, $rangeBullet) {
        $query = 'UPDATE bullets SET x='.$x.', y= '.$y.', rangeBullet = '.$rangeBullet.' WHERE id='.$bulletId;
        $this->conn->query($query);
        return true;
    }

    public function updateReloadTimeStamp($id, $timeStamp) {
        $query = 'UPDATE tanks SET reloadTimeStamp='.$timeStamp.' WHERE id='.$id;
        $this->conn->query($query);
        return true;
    }

    public function UpdateHpById($tableName, $hp, $id) {
        $query = 'UPDATE '.$tableName.' SET hp='.$hp.' WHERE id='.$id;
        $this->conn->query($query);
        return true;
    }

    public function updateBoomById($boomId, $timeLife){
        $query = 'UPDATE booms SET timeLife='.$timeLife.' WHERE id='.$boomId;
        $this->conn->query($query);
        return true;
    }

    public function updateBlockById($blockId, $hp) {return $this->UpdateHpById('field', $hp, $blockId); }
    public function updateBuildingById($buildingId, $hp) {return $this->UpdateHpById('building', $hp, $buildingId); }
    public function updateTankById($tankId, $hp) {return $this->UpdateHpById('tanks', $hp, $tankId); }

    public function DeleteById($tableName, $id) {
        $query = 'DELETE FROM '.$tableName.' WHERE id=' . $id;
        $this->conn->query($query);
		return true;
    }
    

	public function deleteBulletById($bulletId){ return $this->DeleteById('bullets', $bulletId); }
    public function deleteBlockById($blockId){ return $this->DeleteById('field', $blockId); }
    public function deleteBuildingById($buildingId){ return $this->DeleteById('building', $buildingId); }
    public function deleteTankById($tankId){ return $this->DeleteById('tanks', $tankId); }
    public function deleteBoomById($boomId){ return $this->DeleteById('booms', $boomId); }
    public function deleteObjectById($objectId){ return $this->DeleteById('objects',$objectId); }
    public function deleteAllData($tableName){
        $query = 'DELETE FROM '.$tableName;
        $this->conn->query($query);
		return true;
    }

    public function deleteAllTank(){return $this->deleteAllData('tanks'); }
    public function deleteAllBuilding(){return $this->deleteAllData('building'); }
    public function deleteAllObject(){return $this->deleteAllData('objects'); }
    public function deleteAllBlock(){return $this->deleteAllData('field'); }
    public function deleteAllBullet(){return $this->deleteAllData('bullets'); }
    public function deleteAllBoom(){return $this->deleteAllData('booms'); }

    public function isTankExists($userId) {
        return $this->getDataByUserId('tanks', $userId);
    }

    public function getBaseCount(){
        $query = 'SELECT COUNT(type) AS count FROM building WHERE type="base"';
        $result = $this->conn->query($query);
        return intval($this->oneRecord($result)->count);
    }

	public function getBaseByTeamId($teamId) {
        $query = 'SELECT * FROM building WHERE team=' . $teamId;
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function getObjectsByXY($x, $y){
        $query = 'SELECT * FROM objects WHERE x = ' . $x . ' AND y = ' . $y;
        $result = $this->conn->query($query);
        return $this->allRecords($result);
    }

    public function updateObjectCount($id,$count){
        $query = 'UPDATE objects SET count='.$count.' WHERE id='.$id;
        $this->conn->query($query);
        return true;
    }

    public function updateTankCargo($id, $cargo){
        $query = 'UPDATE tanks SET cargo='.$cargo.' WHERE id='.$id;
        $this->conn->query($query);
        return true;
    }
    
    public function updateHpBase($hp,$team){
        $query = 'UPDATE building SET hp = '.$hp.' WHERE team='.$team;
        $this->conn->query($query);
        return true;
    }

    public function getNukes(){ return $this->getAllData('nuke'); }

    public function getNuke(){
        $query = 'SELECT * FROM nuke';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function updateUserMoneyById($id, $money) {
        $query = 'UPDATE users SET money = money + '.$money.' WHERE id=' . $id;
        $this->conn->query($query);
        return true;
    }

    public function getKills() {
        $query = 'SELECT user_id AS id, count(user_id) as kills FROM result 
                    INNER JOIN users ON user_id = users.id
                  GROUP BY users.id';
        $result = $this->conn->query($query);
        return $this->allRecords($result);
    }

    public function getDeaths() {
        $query = 'SELECT user_id AS id, count(killed_id) as deaths FROM result 
                    INNER JOIN users ON user_id = users.id
                  GROUP BY users.id';
        $result = $this->conn->query($query);
        return $this->allRecords($result);
    }

    public function getFriendFire() {
        $query = 'SELECT user_id AS id, count(killed_id) as friendFires FROM result
	                INNER JOIN users ON user_id = users.id
                  WHERE enemy = 0
                  GROUP BY users.id';
        $result = $this->conn->query($query);
        return $this->allRecords($result);
    }

    public function getRating() {
        $query = 'SELECT u.login, r.kills, rd.deaths
                  FROM 
                      users AS u, 
                      (SELECT u.id, count(r.killed_id) AS kills 
                      FROM users AS u 
                          INNER JOIN result AS r ON u.id = r.user_id AND r.enemy = 1
                      GROUP BY u.id) AS r,
                      (SELECT u.id, count(rd.user_id) AS deaths
                      FROM users AS u 
                          INNER JOIN result AS rd ON u.id = rd.killed_id
                      GROUP BY u.id) AS rd
                  WHERE u.id = r.id AND u.id = rd.id';
        $result = $this->conn->query($query);
        return $this->allRecords($result);
    }


    public function addResult($tank, $userId) {
        if (isset($tank->user_id) && $userId) {
            $killerTank = $this->getTankByUserId($userId);
            $enemy = ($tank->team === $killerTank->team) ?  0 : 1;
            $query = 'INSERT INTO result (user_id, killed_id, enemy)
                      VALUES ('.$killerTank->user_id.', '.$tank->user_id.', '.$enemy.')';
            $this->conn->query($query);
            return true;
        }
        return false;
    }

    public function randomHull() {
        $query = 'SELECT * FROM hull ORDER BY RAND() LIMIT 1';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function randomShassis() {
        $query = 'SELECT * FROM shassis ORDER BY RAND() LIMIT 1';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function randomGun() {
        $query = 'SELECT * FROM gun ORDER BY RAND() LIMIT 1';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    ///////////////////////// ТРУСОВ ОТВЕРНИСС!!!!!!!!!!! ////////////////////////////

    public function exam_4() {
        $query = 'SELECT COUNT(*) AS cnt FROM users WHERE (token = " ") ';
        $result = $this->conn->query($query);
        return $this->oneRecord($result)->cnt;
    }

    public function exam_8() {
        $query = 'SELECT COUNT(*) AS TABLE_COUNT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = "vmech"';
        $result = $this->conn->query($query);
        return intval($this->oneRecord($result)->TABLE_COUNT);
    }

    public function exam_9() {
        $query = 'SELECT *  FROM users WHERE (token != " ") ORDER BY RAND() LIMIT 1';
        $result = $this->conn->query($query);
        return $this->oneRecord($result)->login;
    }

    public function exam_10() {
        $query = 'SELECT sum(cnt) as cnt 
                FROM
                (SELECT count(*) as cnt FROM battle
                UNION ALL 
                SELECT count(*) as cnt FROM booms
                UNION ALL 
                SELECT count(*) as cnt FROM building
                UNION ALL 
                SELECT count(*) as cnt FROM bullets
                UNION ALL 
                SELECT count(*) as cnt FROM field
                UNION ALL 
                SELECT count(*) as cnt FROM gun
                UNION ALL 
                SELECT count(*) as cnt FROM hull
                UNION ALL 
                SELECT count(*) as cnt FROM nuke
                UNION ALL 
                SELECT count(*) as cnt FROM objects
                UNION ALL 
                SELECT count(*) as cnt FROM result
                UNION ALL 
                SELECT count(*) as cnt FROM shassis
                UNION ALL 
                SELECT count(*) as cnt FROM sprite_map
                UNION ALL 
                SELECT count(*) as cnt FROM tanks
                UNION ALL 
                SELECT count(*) as cnt FROM team
                UNION ALL 
                SELECT count(*) as cnt FROM users
                ) as t';
         $result = $this->conn->query($query);
         return intval($this->oneRecord($result)->cnt);
        

    }

    public function exam_11($word) {
        $query = 'SELECT * FROM users WHERE login LIKE "%'.$word.'%"';
        $result = $this->conn->query($query);
        return $this->oneRecord($result)->login;
    }

    public function exam_12($login) {
        $query = 'SELECT token, REVERSE(token) AS Reverse FROM users WHERE login = "'.$login.'"';
        $result = $this->conn->query($query);
        return $this->oneRecord($result)->Reverse;
    }

    public function exam_14($login) {
        $query = 'SELECT * FROM users WHERE login =  "'.$login.'" ';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }

    public function exam_20() {
        $query = 'SELECT sum(cnt) as cnt 
        FROM
        (SELECT count(*) as cnt FROM field
        UNION ALL 
        SELECT count(*) as cnt FROM objects
        UNION ALL 
        SELECT count(*) as cnt FROM tanks
        ) as t';
        $result = $this->conn->query($query);
        return intval($this->oneRecord($result)->cnt);
    }
    



    public function OnOff() {
        $query = 'UPDATE battle SET TeamBalance=NOT(TeamBalance)';
        $this->conn->query($query);
        return true;
    }

	public function getTeamBalance() {
		$query = 'SELECT TeamBalance FROM battle';
        $result = $this->conn->query($query);
        return $this->oneRecord($result);
    }


	public function getTeamCount1() {
		$query = 'SELECT COUNT(*) as cnt FROM tanks WHERE team=1';
        $result = $this->conn->query($query);
        return $this->oneRecord($result)->cnt;
    }

		public function getTeamCount2() {
		$query = 'SELECT COUNT(*) as cnt FROM tanks WHERE team=2';
        $result = $this->conn->query($query);
        return $this->oneRecord($result)->cnt;
    }

}