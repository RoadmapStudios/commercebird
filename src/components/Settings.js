import React, { useState, useEffect } from 'react';


const Settings = () => {
    return  <React.Fragment>
    <h2>React Settings Form</h2>
    <form id="work-settings-form" onSubmit={ (e) => handleSubmit(e) }>
        <table className="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label htmlFor="unchecked" style={{ padding: "10px 24px 10px 0" }}> Add Cors </label>
                    </th>
                    <td>
                        <SwitchComponent id={check}></SwitchComponent>
                        {/* <input id="firstname" name="firstname" value={ firstname } onChange={ (e) => { setFirstName( e.target.value ) } } className="regular-text" /> */}
                    </td>
                </tr>
            </tbody>
        </table>
        <p className="submit">
            <button type="submit" className="button button-primary">{ loader }</button>
        </p>
    </form>
    </React.Fragment>
};