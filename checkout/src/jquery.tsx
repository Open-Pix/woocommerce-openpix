// import * as $ from 'jquery';

declare global {
  interface Window {
    jQuery: JQueryStatic;
  }
}

// we need to use the same jQuery version of Wordpress and Woocommerce to make it work
const $ = window.jQuery;

export default $;
