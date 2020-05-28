export default class Server {

    constructor(setError){
        this.setError = setError;
    }

    HOST = 'http://vm21/api/?';
    token = null;
    isUpdateScene = false;

    async send(method, data) {
        let arr = [];
        for (let key in data) {
            arr.push(`${key}=${data[key]}`);
        }
        if (this.token) {
            arr.push(`token=${this.token}`);
        }
        const response = await fetch(`${this.HOST}method=${method}&${arr.join('&')}`);
        const answer = await response.json();
        return (answer && answer.result === 'ok') ? 
                answer.data : 
                (method !== 'update' && 
                 method !== 'move' && 
                 method !== 'shoot' && 
                 method !== 'boom') ? 
                    this.error(answer.error) :
                    false;
    }

    error(err){
        this.setError({ ...err });
        return false;
    }

    /********************/
    /* Про пользователя */
    /********************/

    async auth(data) {
        const result = await this.send('login', data);
        if (result && result.token) {
            this.token = result.token;
        }
        return result;
    }

    logout() {
        this.stopUpdate();
        return this.send('logout');
    }

    async registration(data) {
        const result = await this.send('registration', data);
        if (result && result.token) {
            this.token = result.token;
        }
        return result;
    }

    /************/
    /* Про игру */
    /************/
    async startUpdate() {
        if (this.isUpdateScene) {
            const result = await this.send('update');
            if (result) {
                this.updateSceneCb(result);
            }
            this.startUpdate();
        }
    }
    
    stopUpdate() {
        this.isUpdateScene = false;
    }

    async move(direction) {
        return await this.send('move', { direction });
    }

    checkEndGame() {
        return this.send('checkEndGame');
    }

    shoot() {
        return this.send('shoot');
    }

    updateScene(cb) {
        if (cb instanceof Function) {
            this.updateSceneCb = cb;
        } else {
            this.updateSceneCb = () => false;
        }
    }

    getConstructor() {
        return this.send('getConstructor');
    }

    getRating() {
        return this.send('getRating');
    }


    joinGame(tankParams) {
        return this.send('joinGame', { tankParams });
    }

    async addTank(tankParams) {
        const result = await this.send('addTank', { team: tankParams['TEAM'], 
                                                    hull: tankParams['HULL_TYPE'], 
                                                    gun: tankParams['GUN_TYPE'], 
                                                    shassis: tankParams['SHASSIS_TYPE'], 
                                                    nuke: tankParams['NUKE']});
        if (result) {
            this.isUpdateScene = true;
            this.startUpdate();
        }
        return result;
    }

    boomNuke() {
        return this.send('boom');
    }

    /////////////// ТРУСОВ ОТВЕРНИСС!!!! ///////
    
    async OnOff() {
        return await this.send('OnOff');
    }
}