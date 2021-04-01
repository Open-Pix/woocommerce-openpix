import { render } from 'react-dom';

import { StrictMode } from 'react';

import * as formSubmit from './formSubmit';
import $ from './jquery';
import { getHostNode } from './getHostNode';
import App, { AppProps } from './App';

type WCOpenPixParams = {
  appID: string;
  storeName: string;
};

declare global {
  interface Window {
    wcOpenpixParams: WCOpenPixParams;
  }
}

export const onSuccess = (correlationID: string) => {
  formSubmit.setFormSubmit(true);

  const form = $('form.checkout, form#order_review');

  // add a hiden input with correlation id used
  $('input[name=openpix_correlation_id]', form).remove();
  form.append(
    $('<input name="openpix_correlation_id" type="hidden" />').val(
      correlationID,
    ),
  );

  form.trigger('submit');
};

export const onCheckout = () => {
  if (formSubmit.getFormSubmit()) {
    // let woocommerce process the payment
    return true;
  }

  // show openpix payment modal
  const hostNode = getHostNode('openpix-checkout');

  // get data from woocommerce
  // value
  // description

  const form = $('form.checkout, form#order_review');
  const inlineData = $('#openpix-checkout-params', form);

  const { wcOpenpixParams } = window;

  // eslint-disable-next-line
  console.log({
    wcOpenpixParams,
    total: inlineData.data('total'),
  });

  const props: AppProps = {
    onSuccess,
    value: inlineData.data('total'),
    description: wcOpenpixParams.storeName,
    customer: null,
    appID: wcOpenpixParams.appID,
  };

  render(
    <StrictMode>
      <App {...props} />
    </StrictMode>,
    hostNode,
  );

  return false;
};
