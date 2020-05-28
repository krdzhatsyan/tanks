import React from 'react'

//import './../assets/css/mods.css'

import Server from './modules/Server'
import appState from './modules/State'

import Auth from './Auth'
import Error from './Error'
import Game from './Game'
import Registr from './Registr'

class Container extends React.Component {
  constructor() {
    super();
    this.server = new Server(err => this.setErrors(err));
    this.appState = new appState();
    this.state = {
      isAuth: false,
      isRegistr: false,
      isError: false,
      errors: []    
    }
  }
  setErrors(err) {
    let arr = this.state.errors;
    arr.push(err);
    this.setState({isError: true, errors: arr});
  }

  clearErrors() {
    this.setState({isError: false, errors: []});
  }

  setAuth(val) {
    if (!val) {
      this.server.logout();
    }
    this.setState({isAuth: val});
  }

  setRegistr(val){
    this.setState({isRegistr: val});
  }

  render() {
    return (
      <div className="App">
        {this.state.isError ? <Error errors = {() => this.state.errors} clearErrors = {() => this.clearErrors()}/> : null}
          {this.state.isAuth 
          ?
            <Game
              setAuth = {val => this.setAuth(val)}
              updateRequest = {cb => this.server.updateScene(cb)}
              stopRequest = {() => this.server.stopUpdate()}
              addTankRequest = {val => this.server.addTank(val)}
              appState = {() => this.appState}
              getRating = {() => this.server.getRating()}
              getConstructor = {() => this.server.getConstructor()}
              setErrors = {err => this.setErrors(err)}
              move={(direction) => this.server.move(direction)}
              shoot={() => this.server.shoot()}
              boomNuke={() => this.server.boomNuke()}
              OnOff={() => this.server.OnOff()} // кнопачка
              />
          : this.state.isRegistr
          ?
          <Registr
            setAuth = {val => this.setAuth(val)}
            setRegistr = {val => this.setRegistr(val)}
            appState = {() => this.appState}
            registrRequest={data => this.server.registration(data)}
          />  
          :<Auth 
            setRegistr = {val => this.setRegistr(val)}
            setAuth = {val => this.setAuth(val)} 
            authRequest={data => this.server.auth(data)}
            appState = {() => this.appState}
            setErrors = {err => this.setErrors(err)}
          />
        }
      </div>
    );
  }
}

export default Container;