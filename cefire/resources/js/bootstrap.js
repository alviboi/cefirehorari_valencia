import _ from 'lodash';
import UIkit from 'uikit';
import Icons from 'uikit/dist/js/uikit-icons';
import axios from 'axios';
import Pusher from 'pusher-js';
import $ from 'jquery';

window._ = _;
window.UIkit = UIkit;
UIkit.use(Icons);

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

try {
    window.$ = window.jQuery = $;
} catch (e) {
    console.error('Error loading jQuery:', e);
}

Pusher.logToConsole = true;
window.Pusher = Pusher;
