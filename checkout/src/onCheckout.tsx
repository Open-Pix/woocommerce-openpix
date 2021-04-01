import { render } from 'react-dom';

import { StrictMode } from 'react';

import parsePhoneNumber from 'libphonenumber-js';

import * as formSubmit from './formSubmit';
import $ from './jquery';
import { getHostNode } from './getHostNode';
import Checkout, { AppProps, Customer } from './Checkout';

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

type WoocomerceFormData = {
  billing_first_name: string;
  billing_last_name: string;
  billing_persontype: string;
  billing_cpf: string;
  billing_company: string;
  billing_cnpj: string;
  billing_country: string;
  billing_postcode: string;
  billing_address_1: string;
  billing_number: string;
  billing_address_2: string;
  billing_neighborhood: string;
  billing_city: string;
  billing_state: string;
  billing_phone: string;
  billing_cellphone: string;
  billing_email: string;
  order_comments: string;
  payment_method: string;
  'woocommerce-process-checkout-nonce': string;
  _wp_http_referer: string;
};

export const formDataToObject = (data: FormData) => {
  const obj = {};
  data.forEach((value, key) => (obj[key] = value));
  return obj;
};

export const getWoocommerceFormData = (): WoocomerceFormData => {
  const form = $('form.checkout, form#order_review')[0];

  const data = new FormData(form);

  return formDataToObject(data) as WoocomerceFormData;
};

const defaultCountry = 'BR';

export const normalizePhoneNumber = (phoneNumber: string): string | null => {
  if (!phoneNumber) {
    return null;
  }

  const parsed = parsePhoneNumber(phoneNumber, defaultCountry);

  if (parsed) {
    return parsed.number;
  }

  return null;
};

export const getCustomerFromWoocommerce = (
  data: WoocomerceFormData,
): Customer | null => {
  const getTaxID = () => {
    if (data.billing_cpf) {
      return data.billing_cpf;
    }

    if (data.billing_cnpj) {
      return data.billing_cnpj;
    }

    return null;
  };

  const taxID = getTaxID();

  if (!taxID) {
    return null;
  }

  return {
    name: `${data.billing_first_name} ${data.billing_last_name}`,
    taxID: data.billing_cpf,
    phone:
      normalizePhoneNumber(data.billing_cellphone) ||
      normalizePhoneNumber(data.billing_phone),
    email: data.billing_email,
  };
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

  const wooData = getWoocommerceFormData();
  const customer = getCustomerFromWoocommerce(wooData);

  // eslint-disable-next-line
  console.log({
    wcOpenpixParams,
    total: inlineData.data('total'),
    customer,
    wooData,
    nonce: wooData['woocommerce-process-checkout-nonce'],
  });

  const props: AppProps = {
    onSuccess,
    value: inlineData.data('total'),
    description: wcOpenpixParams.storeName,
    customer,
    appID: wcOpenpixParams.appID,
  };

  render(
    <StrictMode>
      <Checkout {...props} />
    </StrictMode>,
    hostNode,
  );

  return false;
};
