import App from './App';
import { render } from '@wordpress/element';

/**
 * Import the stylesheet for the plugin.
 */
import './style/main.scss';

render(<App />, document.getElementById('wp-admin-app'));