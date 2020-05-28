import React from 'react'
import md5 from 'md5'
import './Auth.css'

class Auth extends React.Component {

    constructor(props) {
        super();
        this.authRequest = props.authRequest;
        this.setAuth = props.setAuth;
        this.appState = props.appState();
        this.setRegistr = props.setRegistr;
        this.setErrors = props.setErrors;
    }

    async login() {
        const login = document.getElementById('login').value;
        const password = document.getElementById('password').value;
        if (login && password) {
            const rnd = Math.round(Math.random() * 100000);
            const hash = md5(md5(login + password) + rnd);
            const result = await this.authRequest({ login, hash, rnd });
            if(result){
                this.appState.login = result.login;
                this.appState.money = result.money;
                this.appState.token = result.token;
                this.setAuth(true);
            }
        } else {
            this.setErrors({
                code: 100,
                text: 'Не хватает данных для входа'
            });
        }    
    }

    registration() {
        this.setRegistr(true);
    }

    render() {
        return (
            <div className="menu">
                <div className='auth'>
                <h1>Autorization</h1>
                    <div className='menu__input'>
                        <input className='input__login' type='text' id='login' placeholder='Login'></input><br />
                        <input className='input__password' type='password' id='password' placeholder='Password'></input><br />
                        <div className='menu__btn'>
                            <div className='green__btn' onClick={() => this.login()} alt="green">
                            <label id='button'>Sign in</label>
                            </div>
                            <div className='blue__btn' onClick={() => this.registration()}>
                            <label id='button'>Sigh up</label>
                            </div>
                        </div>                   
                    </div>   
                </div>   
                <div className="bg__menu">
                        <div className='back__logo'>
                            <div className="logo"></div>
                        </div>
                </div> 
            </div>
        );
    }
}

export default Auth;