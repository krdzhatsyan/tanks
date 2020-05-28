<?php
error_reporting(-1);
header('Access-Control-Allow-Origin: *');
require_once('application/Application.php');

function router($params) {
    $method = $params['method'];
    if ($method) {
        $app = new Application();
        switch ($method) {
            // about user
            case 'login': return $app->login($params);
            case 'logout': return $app->logout($params);
            case 'registration': return $app->registration($params);
            // about game
            case 'addTank': return $app->addTank($params);
            case 'move': return $app->move($params);
            case 'shoot': return $app->shoot($params);
            case 'update': return $app->updateScene($params);
            case 'getConstructor': return $app->getConstructor();
            case 'joinGame': return $app->joinGame($params);
            case 'boom': return $app->boom($params);
            case 'getRating': return $app->getRating($params);

             ///////////////////////// ТРУСОВ ОТВЕРНИСС!!!!!!!!!!! ////////////////////////////

             case 'exam_4' : return $app->exam_4();
            case 'exam_8' : return $app->exam_8();
            case 'exam_9' : return $app->exam_9();
            case 'exam_10' : return $app->exam_10();
            case 'exam_11' : return $app->exam_11($params);
            case 'exam_12' : return $app->exam_12($params);
            case 'examGetUserByLogin' : return $app->examGetUserByLogin($params); //13.дописывает переданное слово к имени пользователя по указанному логину
            case 'exam_14' : return $app->exam_14($params);
            case 'exam_20' : return $app->exam_20();
            case 'exam_22' : return $app->exam_22($params);


            case 'OnOff' : return $app->OnOff();
			case 'getTeamBalance' : return $app->getTeamBalance();
			case 'getTeamCount1' : return $app->getTeamCount1();
			case 'getTeamCount2' : return $app->getTeamCount2();
            default: return false;
        }
    }
    return false;
}

function answer($data) {
    if ($data) {
        return array(
            'result' => 'ok',
            'data' => $data
        );
    }
    return array(
        'result' => 'error',
        'error' => array(
            'code' => 9000,
            'text' => 'unknown error'
        )
    );
}

echo json_encode(answer(router($_GET)));