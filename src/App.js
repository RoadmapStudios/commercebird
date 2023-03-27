import React from 'react';
import Settings from './components/Settings';

function App() {
    return(
        <React.Fragment>
            <div className='on-header'>
                <header>
                <img className="profile-logo" src={ "../wp-content/plugins/wooventory/media/Wooventory-Logo.webp"} alt={"Wooventory-Logo"}/>
                </header>
            </div>
            <Settings/>
        </React.Fragment>
    )
}
export default App;