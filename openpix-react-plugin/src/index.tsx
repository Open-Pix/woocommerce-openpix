import { StrictMode } from 'react';
import { render } from 'react-dom';

// import * as $ from "jquery";
import $ from './jquery';

import App, { AppProps } from './App';
import { getHostNode } from './getHostNode';
// import {hijackClickReact} from "./hijackClickReact";
import { hijackClickJQuery } from './hijackClickJQuery';
import * as formSubmit from './formSubmit';

const onSuccess = (correlationID: string) => {
  formSubmit.setFormSubmit(true);

  const form = $('form.checkout, form#order_review');

  // add a hiden input with correlation id used
  $( 'input[name=openpix_correlation_id]', form ).remove();
  form.append( $( '<input name="openpix_correlation_id" type="hidden" />' ).val( correlationID ) );

  form.trigger('submit');
};

const onCheckout = () => {
  if (formSubmit.getFormSubmit()) {
    // let woocommerce process the payment
    return true;
  }

  // show openpix payment modal
  const hostNode = getHostNode('openpix-checkout');

  // get data from woocommerce
  // value
  // description

  const props: AppProps = {
    onSuccess,
    value: 1,
    description: 'Woocommerce',
    customer: null,
  }

  render(
    <StrictMode>
      <App
        {...props}
      />
    </StrictMode>,
    hostNode,
  );

  return false;
};

const init = () => {
  // only the exactly jQuery properly hijack place order button click event
  hijackClickJQuery(onCheckout);
  // hijackClickReact(onCheckout);
};

// render Pix to manage isOpen and websockets
init();
