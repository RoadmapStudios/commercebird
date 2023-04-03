import React, { useState, useEffect } from 'react';
import Toggle from 'react-toggle';
import { Tooltip } from 'react-tooltip'
import axios from 'axios';
import { BsExclamationTriangle } from "react-icons/bs";

const Settings = () => {

    const [changeLogData, setUpdatesChanges] = useState([]);
    const [subscriptionData, setSubscriptionData] = useState({});
    const [cors_status, setCors] = useState(false);
    const [sub_id, setSubid] = useState('');
    const [loader, setLoader] = useState('Save Settings');

    const renderHTML = (rawHTML) => React.createElement("div", { dangerouslySetInnerHTML: { __html: rawHTML } });

    const url = `${appLocalizer.apiUrl}/wooventory/v1/settings`;
    const changeLogUrl = 'https://wooventory.com/wp-json/wp/v2/changelog';

    let getChangeLog = (changeLogUrl) => {
        let chg_el = document.getElementById("changelog-data");
        axios.get(changeLogUrl)
            .then((res) => {
                chg_el.innerText = "";
                if (res.status === 200) {
                    const middleIndex = Math.ceil(res.data.length / 2);
                    const cld = res.data.splice(0, middleIndex);
                    setUpdatesChanges(cld);
                }
            }).catch((error) => {
                console.log(error);
                chg_el.innerText = "No latest annoucements at the moment.";
            });
    }

    const handleCors = (event) => {
        setCors(event.target.checked);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoader('Saving...');
        axios.post(url, {
            cors_status: cors_status,
            sub_id: sub_id
        }, {
            headers: {
                'content-type': 'application/json',
                'X-WP-NONCE': appLocalizer.nonce
            }
        })
            .then((res) => {
                getSubscription(sub_id);
                setLoader('Save Settings');
            })
    }

    const getSubscription = (sub_id) => {
        if (sub_id != null && sub_id != "") {
            let wid_el = document.getElementById("loading-widget");
            var subscriptionUrl = `${appLocalizer.apiUrl}/wooventory/v1/subscription/` + sub_id;
             axios.get(subscriptionUrl)
                .then((res) => {
                    if (res.status === 200) {
                        setSubscriptionData(res.data);
                    } else {
                        wid_el.innerText = "Please enter your subscription ID to receive support";
                    }
                }).catch((error) => {
                    console.log(error);
                    wid_el.innerText = "Please enter your subscription ID to receive support";
                });
        }
    }

    useEffect(() => {
        let wid_el = document.getElementById("loading-widget");
        let chg_el = document.getElementById("changelog-data");
        wid_el.innerText = "Loading...";
        chg_el.innerText = "Loading...";
        axios.get(url)
            .then((res) => {
                setCors(res.data.cors_status);
                setSubid(res.data.sub_id);
                getSubscription(res.data.sub_id);
            }).catch((error) => {
                console.log(error);
            });
        getChangeLog(changeLogUrl);
    }, []);

    const showMessage = () => {
        return <div className='inactive-widget' id="loading-widget">  </div>
    }

    const showList = () => {
        let appsArray = subscriptionData ? subscriptionData.fee_lines : null;
        let list = "";
        if (appsArray != null) {
            list = appsArray.map((s) => {
                return <li> - {s.name} </li>
            });
            return list;
        }
    }

    const renderWidget = () => {
        let lineItems = subscriptionData.line_items ? subscriptionData.line_items[0] : null;
        let paymentCurrency = subscriptionData.currency;
        return <div className='main'>
            <div className='content'>
                <div>
                    <h1> {lineItems != null ? lineItems.name : "Loading..."}  </h1>
                    <p>
                        Live Notifications, Fastest IOS/Android App, Staff Members, Integrations and more
                    </p>

                    <h3> Activated Integrations </h3>
                    <ul>
                        {showList()}
                    </ul>

                    <a href='https://wooventory.com/pricing'>
                        View Plans
                    </a>
                    {subscriptionData.needs_payment == true ?
                        <a href={subscriptionData.payment_url} target="_blank" className="right rplan-btn"> Reactivate Plan </a>
                        : null}
                </div>
            </div>
            <div className="footer">
                Payment
                <p> Your next bill is for {subscriptionData.total} {paymentCurrency} on {subscriptionData.next_payment_date_gmt} </p>
            </div>
        </div>
    }


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
                                                <label>Enable CORS Support</label>
                                            </th>
                                            <td>
                                                <Toggle id="cors_status" name="cors_status" value={cors_status} checked={cors_status} onChange={handleCors} />
                                                <span class="toggle-text"><BsExclamationTriangle /> Only enable if CORS Error</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                <label htmlFor="cors_status" data-tooltip-id="sub_id_tooltip" data-tooltip-content="Check your Order Email"> Enter subscription id: </label>
                                                <Tooltip id="sub_id_tooltip" />
                                            </th>
                                            <td>
                                                <div>
                                                    <input type="number" id="sub_id" name="sub_id" value={sub_id} onChange={(e) => { setSubid(e.target.value) }} className="regular-text" />
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
                        <div className="head"> Wooventory App </div>
                        <div className='content img-bg'>
                            <img className="hub-logo" src={"../wp-content/plugins/wooventory/media/wooventory-login-banner.png"} alt={"HUb-Logo"} />
                        </div>
                        <div className="footbg">
                            <p> Manage Orders, Products, Customers, Coupons and more. </p>
                            <a href="https://app.wooventory.com" target="_blank">
                                <button class="button-17">
                                    Launch App
                                </button>
                            </a>
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
                                        <a href="https://roadmapstudios.zohobookings.eu/" target="_blank">
                                            <button class="button-17">Book Now</button>
                                        </a>
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
                                        <a href="https://docs.wooventory.com/portal/en/kb/setup" target="_blank">
                                            <button class="button-17">
                                                Open Knowledge Base
                                            </button>
                                        </a>
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
                                        <a href="https://wooventory.com/contact/" target="_blank">
                                            <button class="button-17">
                                                Open Website
                                            </button>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                        </div>


                    </div>

                </div>

                <div className="rightside">

                    <div className="setting-card">
                        <div className="head">
                            <span className="left"> Your Plan </span>
                            {(subscriptionData.status != null && subscriptionData.status != undefined) ? <span className={subscriptionData.status == "active" ? "right active" : "right not-active"}> {subscriptionData.status} </span> : null}
                        </div>
                        {(subscriptionData.status != null && subscriptionData.status != undefined) ? renderWidget() : showMessage()}
                    </div>

                    <div className="setting-card">
                        <div className="widget-head head"> Announcement </div>

                        <div className='content' id="changelog-data">
                            {changeLogData.map((item, index) => (
                                <div class={"border-" + index + " footer"}>
                                    <h3> {renderHTML(item.title.rendered)} </h3>
                                    <span> {(new Date(item.date)).toLocaleDateString('default', {year: 'numeric', month: 'long', day: 'numeric'})} </span>
                                    <p>
                                        {renderHTML(item.content.rendered)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>


                </div>

            </div>
        </React.Fragment>
    )
}

export default Settings;