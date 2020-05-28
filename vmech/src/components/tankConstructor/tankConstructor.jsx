import React from 'react'
import './tankConstructor.css'

class TankConstructor extends React.Component {
    constructor(props) {
        super();
        this.addTankRequest = props.addTankRequest;
        this.setConstructed = props.setConstructed;
        this.getConstructor = props.getConstructor;
        this.getRating = props.getRating;
        this.constructorCB = props.constructorCB;
        this.constructor = null;
        this.loadConstructor();
        this.loadRating();
        this.OnOff = props.OnOff; // кнопачка, на которую жмякаешь и улетает запрос
        this.money = props.money;
        this.setErrors = props.setErrors;
        this.state = {
            constructorIsLoaded: false,
            ratingIsLoaded: false,
            checkedElems: {
                GUN_TYPE: [false, false],
                HULL_TYPE: [false, false],
                NUKE: [false],
                SHASSIS_TYPE: [false, false],
                TEAM: [false, false]
            }
        }
    }

    async loadConstructor(){
        this.constructor = await this.getConstructor();
        if(this.constructor) {
            this.setState({ constructorIsLoaded: true });
            this.constructorCB(this.constructor);
        }
    }

    async loadRating(){
        this.rating = await this.getRating();
        if(this.rating) {
            this.setState({ ratingIsLoaded: true });
        }
    }

    // Берем собранный танк
    buildTank() {
        let params = {};
        let price = 0;
        const CONSTRUCTOR = this.constructor.CONSTRUCTOR;
        for(let elem in CONSTRUCTOR){
            for(let i = 0; i < CONSTRUCTOR[elem].length; i++){
                let item = CONSTRUCTOR[elem][i];
                if(document.getElementById(item.name).checked){
                    params[elem] = item.id;
                    price += item.price ? item.price - 0 : 0;
                }
            }
            if (elem === 'NUKE') 
                continue;
            if(!params[elem]){
                this.setErrors({
                    code: 500,
                    text: 'Танк собран не полностью'
                });
                return false;
            }
        }
        let defaultMoney = this.constructor.DEFAULT_MONEY;
        
        this.money = (this.money >= defaultMoney - 0) ? this.money : defaultMoney;
        if(this.money >= price){
            return params;
        } else {
            this.setErrors({
                code: 500,
                text: 'Недостаточно денег'
            });
        }
        return false;
    }

    clickHandler(e){
        let name = e.target.name;
        let id = parseInt(e.target.value);
        this.setState(state => {
            for(let i = 0; i < state.checkedElems[name].length; i ++){
                if(id === i) state.checkedElems[name][i] = !state.checkedElems[name][i];
                else state.checkedElems[name][i] = false;
            }
            return {checkedElems : state.checkedElems}
        });
    }

    printConstructor(){
        let arr = [];
        const CONSTRUCTOR = this.constructor.CONSTRUCTOR;
        for(let elem in CONSTRUCTOR){
            arr.push(<div key={'name__type' + elem} className='name__type'><p key={elem.toString()}>--{elem}--</p></div>);
            for(let i = 0; i < CONSTRUCTOR[elem].length; i++){
                let item = CONSTRUCTOR[elem][i];
                arr.push(<div key={'type' + item.name} className='type'><label key={item.name.toString()}>
                            <input key={item.name.toString() + i} checked={this.state.checkedElems[elem][i]} onClick={(e) => this.clickHandler(e)} onChange={() => {}} value={i} type='radio' name={elem} id={item.name}></input>
                            <img key={item.name.toString() + "_img" + i} className='constructorImages' src={require(`../../assets/img/${item.image}`)} alt='none' />
                            { item.price ? <div key={item.name.toString() + "_priceDiv"} className='cost'><span key={item.name.toString() + "_price"}>{item.price}</span></div> : null }
                        </label></div>);
            }
            arr.push(<br key={'br' + elem} />);
        }
        return <div key='tankConstructorInner' className="tankConstructor">{arr}</div>;//React.createElement('div', {className: 'tankConstructor'}, arr);
    }

    printRating() {
        let arr = [];
        arr.push(
            <tr key='table_tr'>
                <th key='table_name'>имя</th>
                <th key='table_death'>смерти</th>
                <th key='table_kills'>убийства</th>
                <th key='table_friend_fires'>уб своих</th>
            </tr>);
        arr.push(this.rating.map((val) => {
        return (<tr>
            <td key={'ratingLogin' + val} className='ratingLogin'>{val.login}</td>
            <td key={'ratingDeath' + val} className='ratingDeath'>{val.deaths}</td>
            <td key={'ratingKill' + val} className='ratingKill'>{val.kills}</td>
            <td key={'ratingFriendFire' + val} className='ratingFriendFire'>{val.friendFires}</td>
        </tr>)}));

        return <table key='table' className='rating'>
                    <caption key='table_caption' id='ratingName'>Рейтинг</caption>
                    <tbody key='table_tbody'>
                        {arr}
                    </tbody>
                </table>;
    }

    

    render() {
        return (<div key=''>
        { this.rating ? this.printRating() : <img className='rating' key='loading1' src={require('../../assets/img/Loading/loading.gif')} alt='none'/> }
        <div key='tankConstructor'>
            { this.constructor ? this.printConstructor() : <img className='tankConstructor' key='loading2' src={require('../../assets/img/Loading/loading.gif')} alt='none'/> }
            <div className='menu__btn'><div className='start__btn' onClick={ () => {
                const tankParams = this.buildTank();
                if (tankParams) {
                    this.addTankRequest(tankParams);  // Отправляем запрос на сервер для создания нового танка с параметрами из конструктора
                    this.setConstructed(true);
                }
            }}><label id='button'>Start</label></div></div>
            <br/>
            <div className='menu__btn' onClick={  // кнопка
                    () => {
                        const lala = this.OnOff();
                        lala.then(lala => alert('Произошло изменение режима балансировки команд'));
                }}>Изменение режима балансировки команд</div>
        </div>
        </div>);
    }
}
export default TankConstructor;