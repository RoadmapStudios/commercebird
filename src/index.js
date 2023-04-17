import App from './App';
import { createRoot } from '@wordpress/element';
import 'react-tooltip/dist/react-tooltip.css'
import "react-toggle/style.css"

/**
 * Import the stylesheet for the plugin.
 */
import './style/main.scss';

const domNode = document.getElementById('wooventory-app');
const root = createRoot(domNode);
root.render(<App />);