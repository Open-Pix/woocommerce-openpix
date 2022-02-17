import $ from 'jquery';
// eslint-disable-next-line
import { within, screen, fireEvent } from '@testing-library/dom';
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

  // eslint-disable-next-line
  const { getByText, findByText, debug } = within(body);

  const woocommrece = `
    <form name="checkout" action="" method="POST">
      <div id="#order_review">
        <ul>
          <li>
            <input id="payment_method_another" />
          </li>
          <li>
            <input id="payment_method_woocommerce_openpix" checked="checked" />
          </li>
        </ul>
        <button type="submit">
          Pay
        </button>
      </div>
    </form>  
  `;

  const form = createElementFromHTML(woocommrece);

  // add woocommerce nodes
  body.append(form);

  // start checkout logic
  require('../index');

  // screen.debug();

  const payButton = getByText('Pay');
  fireEvent.click(payButton);

  expect(window.$openpix.push.mock.calls).toHaveLength(1);
});
