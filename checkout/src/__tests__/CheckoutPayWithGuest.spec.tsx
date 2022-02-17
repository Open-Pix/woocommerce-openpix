import $ from 'jquery';
// eslint-disable-next-line
import { within, fireEvent } from '@testing-library/dom';
import { woocommerceForm } from '../__fixtures__/woocommerceForm';
import { createElementFromHTML } from '../../test/createElementFromHTML';

// window.HTMLFormElement.prototype.submit = jest.fn((e) => e.preventDefault());

beforeEach(() => {
  fetchMock.resetMocks();
  jest.resetModules(); // reset require('Widget') between tests
});

// simulate global jQuery
window.jQuery = $;

it.skip('should inject openpix plugin and call OnCheckout flow', async () => {
  window.$openpix = [];

  const api = {
    generateStatic: jest.fn(),
    status: jest.fn(),
    addEventListener: jest.fn(),
  };

  Object.keys(api).map((k) => {
    window.$openpix[k] = api[k];
  });

  const body = document.querySelector('body');

  // add woocommerce nodes
  body.append(createElementFromHTML(woocommerceForm));

  // eslint-disable-next-line
  const { getByText, findByText, queryByText, queryAllByText } = within(body);

  $('form[name="checkout"]').submit(function (evt) {
    // eslint-disable-next-line
    console.log('submit: ', evt);
    evt.preventDefault();
  });

  // start checkout logic
  require('../index');

  // force jQuery load event
  fireEvent.load(document);
  fireEvent.load(body);

  const payWithPixs = queryAllByText('Pay with Pix');

  const btnPayWithPix = payWithPixs.find((el) => el.closest('button'));
  fireEvent.click(btnPayWithPix);

  // TODO - understand why hijack is not working on test
  expect(window.$openpix.push.mock.calls).toHaveLength(1);
});
