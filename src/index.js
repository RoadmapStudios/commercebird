// Import SCSS entry file so that webpack picks up changes
 // import 'index.scss';

import React from 'react';
import ReactDom from 'react-dom';
import App from './App';


document.addEventListener('DOMContentLoaded', function(){
    var elm = document.getElementById('react-admin-app');
    console.log("asdasdas");
    if( typeof elm  !== 'undefined' && elm == null){
        ReactDom.render( <App/> , document.getElementById('react-admin-app'));
    }
});