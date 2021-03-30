import { StrictMode } from 'react';
import { render } from 'react-dom';
import * as $ from "jquery";

import App from './App';
import { getHostNode } from './getHostNode';
// import {hijackClickReact} from "./hijackClickReact";
import {hijackClickJQuery} from "./hijackClickJQuery";

const onSuccess = () => {
  console.log('payment success');

  const form        = $( 'form.checkout, form#order_review' );


  console.log({
    form,
  });

  form.submit();
}

const onCheckout = () => {
  const hostNode = getHostNode('openpix-checkout');

  render(
    <StrictMode>
      <App onSuccess={onSuccess}/>
    </StrictMode>,
    hostNode,
  );
};

const init = () => {
  // only jQuery properly hijack place order button click event
  hijackClickJQuery(onCheckout);
  // hijackClickReact(onCheckout);
};

// render Pix to manage isOpen and websockets
init();
