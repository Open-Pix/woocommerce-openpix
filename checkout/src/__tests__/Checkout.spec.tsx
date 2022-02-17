import $ from 'jquery';
// eslint-disable-next-line
import { within } from '@testing-library/dom';
import { woocommerceForm } from '../__fixtures__/woocommerceForm';
import { createElementFromHTML } from '../../test/createElementFromHTML';

// window.HTMLFormElement.prototype.submit = jest.fn((e) => e.preventDefault());

beforeEach(() => {
  fetchMock.resetMocks();
  jest.resetModules(); // reset require('Widget') between tests
});

// simulate global jQuery
window.jQuery = $;

it('should inject openpix plugin script and also consume WooCommerce data properly', async () => {
  const body = document.querySelector('body');

  // add woocommerce nodes
  body.append(createElementFromHTML(woocommerceForm));

  // eslint-disable-next-line
  const { getByText, findByText, queryByText, queryAllByText } = within(body);

  // start checkout logic
  require('../index');

  const payWithPixs = queryAllByText('Pay with Pix');

  const btnPayWithPix = payWithPixs.find((el) => el.closest('button'));

  expect(btnPayWithPix).toBeTruthy();
});
