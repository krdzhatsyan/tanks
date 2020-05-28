<?php

class AI {

    function __construct($battle, $field, $tanks, $buildings, $guns) {
        $this->battle = $battle;
        $this->field = $field;
        $this->tanks = $tanks;
        $this->buildings = $buildings;
        $this->guns = $guns;
    }

    private function findPath($ax, $ay, $bx, $by) {
        // массив точек, входящих в найденный путь
        $path = [];
        // ширина и высота поля
        $width = $this->battle->fieldX;
        $height = $this->battle->fieldY;
        $blank = -1;
        $block = -10;
        // длина пути
        $len = 0;
        // рабочее поле               
        $field = [[]];
        $dx = [1, 0, -1, 0];   // смещения, соответствующие соседям ячейки
        $dy = [0, 1, 0, -1];   // справа, снизу, слева и сверху
        // создаем пустое поле
        for($i = 0; $i < $width; $i ++) {
            for($j = 0; $j < $height; $j ++) {
                $field[$j][$i] = $blank;
            }
        }
        // отмечаем  камни на карте как препятствия
        foreach($this->field as $b) {
            $field[$b->y][$b->x] = $block;
        }
        if ($field[$ay][$ax] == $block || $field[$by][$bx] == $block) return false; // (ax, ay) или (bx, by) - являются блоками
        // отмечаем здания на карте как препятствия
        foreach($this->buildings as $build) {
            for($i = 0; $i < $build->width; $i ++)
                for($j = 0; $j < $build->height; $j ++)
                    $field[$build->y + $j][$build->x + $i] = $block;
        }
        // распространение волны
        $d = 0;
        $field[$ay][$ax] = 0; // стартовая ячейка помечена 0
        $stop = null;
        do {  
            $stop = true; // предполагаем, что все свободные клетки уже помечены
            for ($y = 0; $y < $height; $y ++ )
              for ($x = 0; $x < $width; $x ++ )
                if ($field[$y][$x] == $d ) { // ячейка (x, y) помечена числом d
                    for ( $k = 0; $k < 4; ++$k ) { // проходим по всем непомеченным соседям
                        $iy=$y + $dy[$k];
                        $ix = $x + $dx[$k];
                        if ( $iy >= 0 && $iy < $height && $ix >= 0 && $ix < $width &&
                            $field[$iy][$ix] == $blank )
                        {
                           $stop = false; // найдены непомеченные клетки
                           $field[$iy][$ix] = $d + 1; // распространяем волну
                        }
                    }
                }
            $d++;
        } while (!$stop && $field[$by][$bx] == $blank);
        if ($field[$by][$bx] == $blank) return false;  // путь не найден (конечная точка оказалась не помеченой)
        // восстановление пути
        $len = $field[$by][$bx]; // длина кратчайшего пути из (ax, ay) в (bx, by) (метка, которая оказалась в конечной точке)
        // идем из (bx, by) в (ax, ay)
        $x = $bx;
        $y = $by;
        $d = $len;
        while ( $d > 0 ) {
            $point = new stdClass();
            $point->x = $x;
            $point->y = $y;
            // записываем ячейку (x, y) в путь
            $path[$d] = $point;
            $d--;
            for ($k = 0; $k < 4; $k ++) {
                $iy = $y + $dy[$k];
                $ix = $x + $dx[$k];
                if ( $iy >= 0 && $iy < $height && $ix >= 0 && $ix < $width &&
                     $field[$iy][$ix] == $d)
                {
                   // переходим в ячейку, которая на 1 ближе к старту
                   $x = $x + $dx[$k];
                   $y = $y + $dy[$k];
                   break;
                }
            }
        }
        $path = array_reverse($path);
        return $path;
    }

    private function getRangeFire($gunType) {
        foreach($this->guns as $gun) {
            if($gun->id == $gunType) return $gun->rangeFire;
        }
        return false;
    }

    private function findShootableTank($tank) {
        $result = null;
        $rangeFire = $this->getRangeFire($tank->gunType);
        foreach ($this->tanks as $t) {
            if ($t->team != $tank->team) {
                if ($tank->x == $t->x && $tank->y >= $t->y && ($tank->y - $t->y) < $rangeFire) {
                    $result = 'up';
                }
                if ($tank->x == $t->x && $tank->y <= $t->y && ($t->y - $tank->y) < $rangeFire) {
                    $result = 'down';
                }
                if ($tank->y == $t->y && $tank->x >= $t->x && ($tank->x - $t->x) < $rangeFire) {
                    $result = 'left';
                }
                if ($tank->y == $t->y && $tank->x <= $t->x && ($t->x - $tank->x) < $rangeFire) {
                    $result = 'right';
                }
                // проверить, что на пути нету блоков
            }
        }
        return $result;
    }

    private function findNearTank($tank) {
        $tankDistance = [];
        // считаем расстояние до всех танков
        for($i = 0; $i < count($this->tanks); $i ++) {
            if($tank->team != $this->tanks[$i]->team){ //не считаем расстояние до своих
                $t = new stdClass();
                $t->tank = $this->tanks[$i];
                $t->distance = sqrt(pow( $tank->x - $this->tanks[$i]->x, 2) + pow( $tank->y - $this->tanks[$i]->y, 2));
                $tankDistance[] = $t;
            }
        }
        //выбираем танк с наименьшим расстоянием
        $nearTank = isset($tankDistance[0]) ? $tankDistance[0] : null;
        for($i = 1; $i < count($tankDistance); $i ++) {
            if($nearTank->distance > $tankDistance[$i]->distance) {
                $nearTank = $tankDistance[$i];
            }
        }
        return $nearTank ? $nearTank->tank : null;
    }

    public function updateTank($tank) {
        // если можно выстрелить по танку противника - выстрелить
        $shootDirection = $this->findShootableTank($tank);
        if ($shootDirection) {
            return array(
                'command' => 'shoot',
                'direction' => $shootDirection
            );
        }
        // найти ближайший танк противника
        $nearTank = $this->findNearTank($tank);
        if($nearTank) {
            // посчитать путь до него
            $path = $this->findPath( $tank->x, $tank->y, $nearTank->x, $nearTank->y);
            if($path) {
                $direction = null;
                if($tank->x > $path[0]->x) $direction = 'left';
                if($tank->x < $path[0]->x) $direction = 'right';
                if($tank->y > $path[0]->y) $direction = 'up';
                if($tank->y < $path[0]->y) $direction = 'down';
                // совершить шаг
                if($direction) 
                    return array(
                        'command' => 'move',
                        'direction' => $direction
                    );
            }
        }
        return null;
    }
}