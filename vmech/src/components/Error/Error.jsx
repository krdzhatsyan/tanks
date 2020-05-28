import React from 'react'
import './Error.css'

export default class Error extends React.Component {
    constructor(props){
        super();
        this.errors = props.errors();
        this.clearErrors = props.clearErrors;
    }

    printErrors() {
        let arr = [];
        for(let i = 0; i < this.errors.length; i++){
            arr.push(<div key={'err' + i} id="error">
                        <span key={'err' + this.errors[i].code}>>>{this.errors[i].code}</span><br/>
                        <span key={'err' + this.errors[i].text}>{this.errors[i].text}</span><br/>
                    </div>);
        }
        return <div key='e' className='errors'>{arr}</div>;//React.createElement('div', {className: 'errors'}, arr);
    }

    render(){
        return (
        <div key='Error' className='Error'>
            <button onClick={() => this.clearErrors()}>clear</button>
            {this.printErrors()}
        </div>);
    }
}

