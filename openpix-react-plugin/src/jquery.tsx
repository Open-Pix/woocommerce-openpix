declare global {
  interface Window {
    jQuery: JQueryStatic;
  }
}

// we need to use the same jQuery version of Wordpress and Woocommerce to make it work
let $ = window.jQuery;

export default $;
