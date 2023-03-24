import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Settings = () => {

    const [ cors_status, setCors ] = useState( '' );
    const [ loader, setLoader ] = useState( 'Save Settings' );

    const url = `${appLocalizer.apiUrl}/react/v1/settings`;

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoader( 'Saving...' );
        axios.post( url, {
            cors: cors_status
        }, {
            headers: {
                'content-type': 'application/json',
                'X-WP-NONCE': appLocalizer.nonce
            }
        } )
        .then( ( res ) => {
            setLoader( 'Save Settings' );
        } )
    }

    useEffect( () => {
        axios.get( url )
        .then( ( res ) => {
            setCors( res.data.cors_status );
        } )
    }, [] )

    return(
        <React.Fragment>
            <h2>React Settings Form</h2>
            <form id="work-settings-form" onSubmit={ (e) => handleSubmit(e) }>
                <table className="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label htmlFor="cors_status">Cors Enable</label>
                            </th>
                            <td> 
                                <div >
                                    <input type="checkbox" id="cors_status" name="cors_status" value={ cors_status } onChange={ (e) => { setCors( e.target.value ) } } className="regular-text" />
                                    {/* <span id="slider"></span> */}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p className="submit">
                    <button type="submit" className="button button-primary">{ loader }</button>
                </p>
            </form>
        </React.Fragment>
    )
}

export default Settings;