import $ from 'jquery';
// eslint-disable-next-line
import { within, screen, fireEvent } from '@testing-library/dom';

window.HTMLFormElement.prototype.submit = jest.fn((e) => e.preventDefault());
jest.mock(window.$openpix, () => {
  const { createMockEnvironment } = require('relay-test-utils');
  const environment = createMockEnvironment();
  const getEnv = () => environment;

  return {
    getEnv,
    environment,
  };
});

beforeEach(() => {
  fetchMock.resetMocks();
  jest.resetModules(); // reset require('Widget') between tests
});

// simulate global jQuery
window.jQuery = $;

export const createElementFromHTML = (htmlString: string): ChildNode | null => {
  const div = document.createElement('div');
  div.innerHTML = htmlString.trim();

  // Change this to div.childNodes to support multiple top-level nodes
  return div.firstChild;
};

it('should inject openpix plugin script and also consume WooCommerce data properly', async () => {
  const body = document.querySelector('body');

  // eslint-disable-next-line
  const { getByText, findByText } = within(body);

  const woocommrece = `
    <form name="checkout">
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

  // add woocommerce nodes
  body.append(createElementFromHTML(woocommrece));

  // start checkout logic
  require('../index');

  // screen.debug();

  expect(getByText('Pay')).toBeTruthy();
});

it.skip('should inject openpix plugin and call OnCheckout flow', async () => {
  window.$openpix = {
    push: jest.fn(),
    addEventListener: jest.fn(),
  };

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
  // form.submit = jest.fn();
  // add woocommerce nodes
  body.append(form);

  // start checkout logic
  require('../index');

  // screen.debug();

  const payButton = getByText('Pay');
  fireEvent.click(payButton);
  // getByText('hehe');
  // expect(getByText('hehe')).toBeTruthy();
  expect(window.$openpix.push.mock.calls).toHaveLength(1);
});
