// import * as $ from "jquery";
// import {hijackClickReact} from "./hijackClickReact";
import { hijackClickJQuery } from './hijackClickJQuery';
import { onCheckout } from './onCheckout';

const init = () => {
  // only the exactly jQuery properly hijack place order button click event
  hijackClickJQuery(onCheckout);
  // hijackClickReact(onCheckout);
};

// render Pix to manage isOpen and websockets
init();
