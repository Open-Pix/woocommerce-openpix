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
  correlationID: string;
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

type TaxID = {
  taxID: string;
  type: string;
};

type Email = {
  email: string;
  wasVerified: boolean;
};

type Shopper = {
  id: string;
  name: string;
  phones: string;
  taxID: TaxID;
  emails: Email[];
  cashbackUsableBalance: number;
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
): Partial<Customer> => {
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
    return {};
  }

  return {
    name: `${data.billing_first_name} ${data.billing_last_name}`,
    taxID,
    phone:
      normalizePhoneNumber(data.billing_cellphone) ||
      normalizePhoneNumber(data.billing_phone),
    email: data.billing_email,
  };
};

export const getCustommerFromShopper = (
  data: Shopper,
): Partial<Customer> | null => {
  const getTaxID = () => {
    if (!data?.taxID?.taxID) {
      return {};
    }

    return { taxId: data.taxID.taxID };
  };

  const getEmail = () => {
    if (!data?.emails[0].email) {
      return {};
    }

    return { email: data?.emails[0].email };
  };

  const getPhone = () => {
    if (!data?.phones[0]) {
      return {};
    }

    return { phone: data?.phones[0] };
  };

  const getName = () => {
    if (!data?.name) {
      return {};
    }

    return { name: data?.name };
  };

  return {
    ...getName(),
    ...getTaxID(),
    ...getEmail(),
    ...getPhone(),
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
  const wooCustomer = getCustomerFromWoocommerce(wooData);
  const total = inlineData.data('total');
  // eslint-disable-next-line
  console.log({
    wcOpenpixParams,
    inlineData,
    total,
    wooData,
    nonce: wooData['woocommerce-process-checkout-nonce'],
  });

  let shopperCustomer: Partial<Customer> | null = null;
  const onCashbackApplyEvent = (e) => {
    // eslint-disable-next-line
    console.log('apply event logEvents: ', e);

    if (e.type === 'CASHBACK_APPLY') {
      const { shopper, cashbackValue, cashbackHash } = e.data;
      shopperCustomer = getCustommerFromShopper(shopper);
      const cashbackValueInput = $(
        'input[name=openpix_cashback_value]',
        form,
      ).val();
      const cashbackHashInput = $(
        'input[name=openpix_cashback_hash]',
        form,
      ).val();
      const shopperIdInput = $('input[name=openpix_shopper_id]', form).val();

      if (cashbackValueInput && cashbackHashInput && shopperIdInput) {
        return;
      }

      if (cashbackValue && !cashbackValueInput) {
        form.append(
          $<HTMLInputElement>('<input hidden/>')
            .attr('name', 'openpix_cashback_value')
            .val(cashbackValue),
        );
      }

      if (shopper?.id && !shopperIdInput) {
        form.append(
          $<HTMLInputElement>('<input hidden/>')
            .attr('name', 'openpix_shopper_id')
            .val(shopper.id),
        );
      }

      if (cashbackHash && !cashbackHashInput) {
        form.append(
          $<HTMLInputElement>('<input hidden/>')
            .attr('name', 'openpix_cashback_hash')
            .val(cashbackHash),
        );
      }

      window.$openpix.push(['close']);
      formSubmit.setFormSubmit(true);

      // add a hiden input with correlation id used
      $('input[name=openpix_correlation_id]', form).remove();
      form.append(
        $('<input name="openpix_correlation_id" type="hidden" />').val(
          wcOpenpixParams.correlationID,
        ),
      );

      form.trigger('submit');
    }
  };

  const onCashbackInactiveEvent = (e) => {
    console.log('inactive: ', e);

    if (e.type === 'CASHBACK_INACTIVE') {
      // window.$openpix.push(['close']);
      formSubmit.setFormSubmit(true);

      // add a hiden input with correlation id used
      $('input[name=openpix_correlation_id]', form).remove();
      form.append(
        $('<input name="openpix_correlation_id" type="hidden" />').val(
          wcOpenpixParams.correlationID,
        ),
      );

      form.trigger('submit');
    }
  };

  const customer: Customer = {
    name: wooCustomer?.name ?? shopperCustomer?.name,
    phone: wooCustomer?.phone ?? shopperCustomer?.phone,
    email: wooCustomer?.email ?? shopperCustomer?.email,
    taxID: wooCustomer?.taxID ?? shopperCustomer?.taxID,
  };

  const props: AppProps = {
    onSuccess,
    onCashbackApplyEvent,
    onCashbackInactiveEvent,
    value: inlineData.data('total'),
    description: wcOpenpixParams.storeName,
    customer,
    appID: wcOpenpixParams.appID,
    correlationID: wcOpenpixParams.correlationID,
    retry: new Date(),
  };

  render(
    <StrictMode>
      <Checkout {...props} />
    </StrictMode>,
    hostNode,
  );

  return false;
};
