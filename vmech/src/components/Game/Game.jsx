import React from 'react'
import Canvas from '../modules/Canvas'
import TankConstructor from '../tankConstructor/tankConstructor'
import './Game.css'

export default class Game extends React.Component {
    constructor(props) {
        super();
        // коллбеки
        this.setAuth = props.setAuth;
        this.updateRequest = props.updateRequest;
        this.stopRequest = props.stopRequest;
        this.getRating = props.getRating;
        this.addTankRequest = props.addTankRequest;
        this.appState = props.appState();
        this.getConstructor = props.getConstructor;
        this.setErrors = props.setErrors;
        this.move = props.move;
        this.shoot = props.shoot;
        this.boomNuke = props.boomNuke;
        this.OnOff = props.OnOff; //Кнопачка
        this.state = {
            isConstructed: false,
            money: this.appState.money
        }
        // спрайты (картинки)
        this.SPRITE = {
            SPRITE_MAP: new window.Image(),
            NUKE_1: new window.Image(),
            NUKE_2: new window.Image(),
            NUKE_3: new window.Image(),
            NUKE_4: new window.Image(),
            NUKE_5: new window.Image(),
            NUKE_6: new window.Image(),
            NUKE_7: new window.Image(),
            NUKE_8: new window.Image(),
            NUKE_9: new window.Image(),
            NUKE_10: new window.Image()
        };

        this.SPRITE.SPRITE_MAP.src = require('../../assets/img/Map/map_sprite.png');
        this.SPRITE.NUKE_1.src = require('../../assets/img/Nuke/NUKE_1.png');
        this.SPRITE.NUKE_2.src = require('../../assets/img/Nuke/NUKE_2.png');
        this.SPRITE.NUKE_3.src = require('../../assets/img/Nuke/NUKE_3.png');
        this.SPRITE.NUKE_4.src = require('../../assets/img/Nuke/NUKE_4.png');
        this.SPRITE.NUKE_5.src = require('../../assets/img/Nuke/NUKE_5.png');
        this.SPRITE.NUKE_6.src = require('../../assets/img/Nuke/NUKE_6.png');
        this.SPRITE.NUKE_7.src = require('../../assets/img/Nuke/NUKE_7.png');
        this.SPRITE.NUKE_8.src = require('../../assets/img/Nuke/NUKE_8.png');
        this.SPRITE.NUKE_9.src = require('../../assets/img/Nuke/NUKE_9.png');
        this.SPRITE.NUKE_10.src = require('../../assets/img/Nuke/NUKE_10.png');

        this.updateRequest(scene => {
            if (scene && (scene.die || scene.gameover)) {
                this.stopRequest(); // stop update scene
                alert("Ты подох"); // write: 'Ты подох!!!'
                this.setConstructed(false); // go to constructor
            } else {
                this.renderScene(scene);
            }
        });

        window.document.onkeydown = event => {
            switch (event.keyCode) {
                case 65: //a
                case 37: //стрелка влево
                    this.move('left'); 
                break;
                case 68: //d
                case 39: //стрелка вправо
                    this.move('right'); 
                break;
                case 87: //w
                case 38: //стрелка вверх
                    this.move('up'); 
                break;
                case 83: //s
                case 40: //стрелка вниз
                    this.move('down'); 
                break;
                case 32: this.shoot(); break;
                case 78: this.boomNuke(); break; //бомбануть
                default: 
                    break;
            }
        }
    }

    setConstructed(val) {
        this.setState({isConstructed: val});
    }

    componentDidUpdate() {
        this.canvas = new Canvas({ id: 'canvas' });
    }

    constructorCB(constructor){
        this.constructor = constructor;
    }

    renderScene(scene) {
        this.canvas.clear();
        if(scene.userMoney !== this.state.money) this.setState({ money:scene.userMoney });
        const field = scene.field;
        const buildings = scene.buildings;
        const bullets = scene.bullets;
        const tanks = scene.tanks;
        const booms = scene.booms;
        const objects = scene.objects;
        const users = scene.users;
        const battle = scene.battle;
        let user = null;
        let tank = null;
        const hullTypes = this.constructor.CONSTRUCTOR.HULL_TYPE;
        const spriteMap = scene.spriteMap;
        let sprite_map = {};
        for(let i = 0; i < spriteMap.length; i++){
            sprite_map[spriteMap[i].name] = spriteMap[i];
        }
        for(let i = 0; i < users.length; i ++) {//взять текущего юзера
            if(users[i].token === this.appState.token) user = users[i];
        }
        for(let i = 0; i < tanks.length; i ++) {//взять танк текущего юзера
            if(tanks[i] && user && tanks[i].user_id === user.id) tank = tanks[i];
        }
        for (let j = 0; j < field.length; j++){
            for (let i = 0; i < field[j].length; i++){
                if(field[j][i] === 0) this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, i*50, j*50, 50, 50, sprite_map, 'GRASS');
                if(field[j][i] > 0 && field[j][i] <= 30) this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, i*50, j*50, 50, 50, sprite_map, 'STONE_3');
                if(field[j][i] > 30 && field[j][i] <= 70) this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, i*50, j*50, 50, 50, sprite_map, 'STONE_2');
                if(field[j][i] > 70) this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, i*50, j*50, 50, 50, sprite_map, 'STONE_1');
            }
        }        
        for (let i = 0; i < buildings.length; i++) {
            if (buildings[i].team === '1')
                this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, buildings[i].x*50, buildings[i].y*50, 100, 100, sprite_map, 'BASE_RED');
            if (buildings[i].team === '2')
                this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, buildings[i].x*50, buildings[i].y*50, 100, 100, sprite_map, 'BASE_BLUE');
            if(tank && buildings[i].team === tank.team){//отобразить здоровье своей базы
                let maxValue = battle.healthBase;
                let currentValue = buildings[i].hp;
                let value = 100 * currentValue / maxValue;
                this.canvas.drawRect(buildings[i].x * 50, buildings[i].y * 50, buildings[i].width * 50, 10, '#ffffff');//maxHealth
                this.canvas.drawRect(buildings[i].x * 50, buildings[i].y * 50, value, 10, '#ff0000');//currentHealth
            }
        }
        for(let i = 0; i < objects.length; i ++) {
            this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, objects[i].x*50, objects[i].y*50, 50, 50, sprite_map, 'LOOT');
            this.canvas.drawText('loot: ' + objects[i].count, objects[i].x * 50, objects[i].y * 50, '#ffffff');//count
        }
        for (let i = 0; i < tanks.length; i++) {
            if(tanks[i]){
                if (tanks[i].team === '1'){
                    // шасси
                    if (tanks[i].shassisType === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'SHASSIS_LIGHT', tanks[i].direction);
                    if (tanks[i].shassisType === '2') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'SHASSIS_HEAVY', tanks[i].direction);
                    //корпус
                    if (tanks[i].hullType === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'HULL_LIGHT_RED', tanks[i].direction);
                    if (tanks[i].hullType === '2') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'HULL_HEAVY_RED', tanks[i].direction);
                    //оружие
                    if (tanks[i].gunType === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'GUN_LIGHT_RED', tanks[i].direction);
                    if (tanks[i].gunType === '2') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'GUN_HEAVY_RED', tanks[i].direction);

                    if (tanks[i].nuke === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'BOMB_RED', tanks[i].direction);

                }
                if (tanks[i].team === '2'){
                    // шасси
                    if (tanks[i].shassisType === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'SHASSIS_LIGHT', tanks[i].direction);
                    if (tanks[i].shassisType === '2') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'SHASSIS_HEAVY', tanks[i].direction);
                    //корпус
                    if (tanks[i].hullType === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'HULL_LIGHT_BLUE', tanks[i].direction);
                    if (tanks[i].hullType === '2') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'HULL_HEAVY_BLUE', tanks[i].direction);
                    //оружие
                    if (tanks[i].gunType === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'GUN_LIGHT_BLUE', tanks[i].direction);
                    if (tanks[i].gunType === '2') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'GUN_HEAVY_BLUE', tanks[i].direction);

                    if (tanks[i].nuke === '1') this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, tanks[i].x*50, tanks[i].y*50, 50, 50, sprite_map, 'BOMB_BLUE', tanks[i].direction);

                }
                for(let j = 0; j < users.length; j++) {
                    if(tanks[i].user_id) {
                        if(tanks[i].user_id === users[j].id) {
                            this.canvas.drawText(users[j].login, tanks[i].x * 50, tanks[i].y * 50, '#ffffff');//login
                        }
                    } else { 
                        this.canvas.drawText('bot', tanks[i].x * 50, tanks[i].y * 50, '#ffffff');//bot 
                    }
                    for(let hull in hullTypes){//отобразить здоровье танков
                        if(hullTypes[hull].id === tanks[i].hullType){
                            let maxValue = hullTypes[hull].hp;
                            let currentValue = tanks[i].hp;
                            let value = 50 * currentValue / maxValue;
                            this.canvas.drawRect(tanks[i].x * 50, tanks[i].y * 50, 50, 10, '#ffffff');//maxHealth
                            this.canvas.drawRect(tanks[i].x * 50, tanks[i].y * 50, value, 10, '#ff0000');//currentHealth
                        }
                    }
                }
            } else if (!tanks[i]) {
                continue;   
            }
            for(let i = 0; i < bullets.length; i ++){
                if(bullets[i].type === '1'){
                    this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, bullets[i].x*50, bullets[i].y*50, 50, 50, sprite_map, 'BULLET_LIGHT', bullets[i].direction);
                } else {
                    this.canvas.drawImageFromSpriteMap(this.SPRITE.SPRITE_MAP, bullets[i].x*50, bullets[i].y*50, 50, 50, sprite_map, 'BULLET_HEAVY', bullets[i].direction);
                }
            }
            for(let i = 0; i < booms.length; i++){
                if(booms[i].type === 'bullet'){
                    if (booms[i].timeLife === "4" || booms[i].timeLife === "3" ||
                        booms[i].timeLife === "2" || booms[i].timeLife === "1"
                    ) {
                        this.canvas.drawImageFromSpriteMap(
                            this.SPRITE.SPRITE_MAP, 
                            booms[i].x*50, 
                            booms[i].y*50, 
                            50, 
                            50, 
                            sprite_map, 
                            `FIRE_${booms[i].timeLife}`);
                    }
                }
                if(booms[i].type === 'nuke'){
                    this.canvas.drawImageScale(this.SPRITE[`NUKE_${booms[i].timeLife}`], booms[i].x*50 - 200, booms[i].y*50 - 200, 400, 400 );
                }
            }
        }
    }

    logout() {
        this.setAuth(false);
    }

    render() {
        return (
            <div className="game">
                <div id='userInfo'>
                    <span>Money: </span>{this.state.money}<span> rub</span><br/>
                    <span>Login: </span>{this.appState.login}<br/>
                </div>
                {this.state.isConstructed
                 ? <canvas id='canvas'></canvas>
                 : <TankConstructor 
                        addTankRequest = { (data) => this.addTankRequest(data)} 
                        setConstructed = { (val) => this.setConstructed(val)}
                        getConstructor = {() => this.getConstructor()}
                        getRating={() => this.getRating()}
                        OnOff={() => this.OnOff()} //кнопачка
                        money = {this.appState.money}
                        setErrors = {this.setErrors}
                        constructorCB = {constructor => this.constructorCB(constructor)}/>
                }
                <div className='menu__btn' onClick={ 
                    () => {
                        this.logout();
                        this.setConstructed(false);
                }}><div className='exit__btn'><label id='button'>Exit</label></div></div><br/>
            </div>
        );
    }
}