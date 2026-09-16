import bundledJQuery from 'jquery';

// Preserve the instance that Domi plugins have already registered against.
const jQuery = window.jQuery || bundledJQuery;
window.$ = window.jQuery = jQuery;

export default jQuery;
