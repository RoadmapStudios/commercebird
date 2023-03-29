import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Settings = () => {

    const [cors_status, setCors] = useState('');
    const [loader, setLoader] = useState('Save Settings');

    const url = `${appLocalizer.apiUrl}/react/v1/settings`;


    const changeLogUrl = 'https://wooventory.com/wp-json/wp/v2/changelog';
    let changeLogData = {};
    axios.get(changeLogUrl)
        .then((res) => {
            changeLogData = { update1: res.data[0], update2: res.data[1] };//, update3: res.data[2], update4: res.data[3]
            console.log(changeLogData);
        });

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

                <div className="leftside">
                    <div className="setting-card">
                        <div className="head"> Settings </div>

                        <form id="work-settings-form" onSubmit={(e) => handleSubmit(e)}>
                            <div className='content'>
                                <table className="form-table" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label htmlFor="cors_status"> Enable CORS Support </label>
                                                <p>Only enable this in case of CORS error</p>
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
                            </div>
                            <div className="footer">
                                <p className="submit">
                                    <button type="submit" className="button button-primary">{loader}</button>
                                </p>
                            </div>
                        </form>

                    </div>

                    <div className="setting-card">
                        <div className="head"> Wooventory Hub </div>
                        <div className='content img-bg'>
                            <img className="hub-logo" src={"../wp-content/plugins/wooventory/media/wooventory-login-banner.png"} alt={"HUb-Logo"} />
                        </div>
                        <div className="footbg">
                            <p> Manage staff, receipts, reports, account settings and more. </p>
                            <button class="op-button-transparent">
                                <a href="https://app.wooventory.com" target="_blank" id="op-transparent"> Launch Hub </a>
                            </button>
                        </div>
                    </div>
                    <div className="setting-card">
                        <div className="head"> Need a Hand? </div>
                        <div className='content'>
                            <div class="op-portlet-need">
                                <ul>
                                    <li>
                                        <i class="calendar alternate outline"></i>
                                    </li>
                                    <li>
                                        <h6>Schedule a Meeting</h6>
                                        <p>Book a Demo with our Sales Team</p>
                                    </li>
                                    <li>
                                        <button class="op-btn-transparent">
                                            <a href="https://app.wooventory.com/pricing" target="_blank">Book Now</a>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="op-portlet-need">
                                <ul>
                                    <li>
                                        <i class="calendar alternate outline"></i>
                                    </li>
                                    <li>
                                        <h6>Call our Support Line</h6>
                                        <p>Give our office a call </p>
                                    </li>
                                    <li>
                                        <button class="op-btn-transparent">
                                            <a href="tel:8336620633">Call +1-833-662-0633</a>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="op-portlet-need">
                                <ul>
                                    <li>
                                        <i class="calendar alternate outline"></i>
                                    </li>
                                    <li>
                                        <h6>Ask Our Knowledge Base</h6>
                                        <p>Get help right away</p>
                                    </li>
                                    <li>
                                        <button class="op-btn-transparent">
                                            <a href="https://docs.wooventory.com/portal/en/kb/setup" target="_blank">Open Knowledge Base</a>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="op-portlet-need">
                                <ul>
                                    <li>
                                        <i class="calendar alternate outline"></i>
                                    </li>
                                    <li>
                                        <h6>Live Chat</h6>
                                        <p>Talk to a support expert </p>
                                    </li>
                                    <li>
                                        <button class="op-btn-transparent">
                                            <a href="https://wooventory.com/contact/#chat-open" target="_blank">Open Website</a>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                        </div>


                    </div>

                </div>

                <div className="rightside">

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
                                <img className="profile-logo aside" src={"../wp-content/plugins/wooventory/media/Wooventory-Logo.webp"} alt={"Wooventory-Logo"} />
                            </div>
                        </div>

                        <div className="footer">
                            Payment
                            <p> Your next bill is for 0.00 usd + tax on 2024-02-14 </p>
                        </div>
                    </div>



                    <div className="setting-card">
                        <div className="head"> Announcement </div>
                        <div className='content'>
                            <div>
                                <h1> Plan Name ... </h1>
                                <p>
                                    Unlimited Transactions, Unlimited Customers
                                </p>
                                <a href='/'>
                                    View Plans
                                </a>
                                <img className="profile-logo aside" src={"../wp-content/plugins/wooventory/media/Wooventory-Logo.webp"} alt={"Wooventory-Logo"} />
                            </div>
                        </div>

                        <div className="footer">
                            Payment
                            <p> Your next bill is for 0.00 usd + tax on 2024-02-14 </p>
                        </div>
                    </div>
                </div>

            </div>
        </React.Fragment>
    )
}

export default Settings;