import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Settings = () => {

    const [cors_status, setCors] = useState('');
    const [loader, setLoader] = useState('Save Settings');

    const url = `${appLocalizer.apiUrl}/react/v1/settings`;

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoader('Saving...');
        axios.post(url, {
            cors: cors_status
        }, {
            headers: {
                'content-type': 'application/json',
                'X-WP-NONCE': appLocalizer.nonce
            }
        })
            .then((res) => {
                setLoader('Save Settings');
            })
    }

    useEffect(() => {
        axios.get(url)
            .then((res) => {
                setCors(res.data.cors_status);
            })
    }, [])

    return (
        <React.Fragment>
            <div className='dashboard-container'>
                <div className="setting-card">
                    <h3>React Settings Form</h3>
                    <form id="work-settings-form" onSubmit={(e) => handleSubmit(e)}>
                        <table className="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label htmlFor="cors_status">Cors Enable</label>
                                    </th>
                                    <td>
                                        <div >
                                            <input type="checkbox" id="cors_status" name="cors_status" value={cors_status} onChange={(e) => { setCors(e.target.value) }} className="regular-text" />
                                            {/* <span id="slider"></span> */}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p className="submit">
                            <button type="submit" className="button button-primary">{loader}</button>
                        </p>
                    </form>
                </div>

                <div className="setting-card">
                    <div className="head">Your Plan</div>

                    <div className='content'>
                        <div>
                            <h1> Plan Name ... </h1>
                            <p>
                                Unlimited Transactions, Unlimited Customers
                            </p>
                            <a href='/'>
                                View Plans
                            </a>
                            <img className="profile-logo aside" src={ "../wp-content/plugins/wooventory/media/Wooventory-Logo.webp"} alt={"Wooventory-Logo"}/>
                        </div>
                    </div>

                    <div className="footer">
                        Payment
                        <p> Your next bill is for 0.00 usd + tax on 2024-02-14 </p>
                    </div>
                </div>
            </div>

            <div className="dashboard-container">
                <div className="setting-card">
                    <div className="head"> Wooventory Hub </div>
                    <div className='content img-bg'>
                        <img className="hub-logo" src={ "../wp-content/plugins/wooventory/media/wooventory-login-banner.png"} alt={"HUb-Logo"}/>
                    </div>
                    <div className="footbg">
                        Start selling with our uncluttered and intuitive WooCommerce POS register.
                        <button class="op-button-transparent">
                            <a href="https://app.wooventory.com" target="_blank" id="op-transparent"> Launch Register </a>
                        </button>
                    </div>
                </div>

                <div className="setting-card">
                    <h3>React Settings Form</h3>
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
                      
                    </form>
                </div>
            </div>



        </React.Fragment>
    )
}

export default Settings;