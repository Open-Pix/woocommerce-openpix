(function () {
  var settings = window.wc.wcSettings.getSetting(
    'woocommerce_openpix_boleto_data',
    {},
  );
  var label =
    window.wp.htmlEntities.decodeEntities(settings.title) ||
    window.wp.i18n.__('Boleto Bancário', 'woocommerce-openpix');

  var Content = function (props) {
    var eventRegistration = props.eventRegistration;
    var emitResponse = props.emitResponse;
    var onPaymentSetup = eventRegistration.onPaymentSetup;

    var useState = window.wp.element.useState;
    var useEffect = window.wp.element.useEffect;
    var createElement = window.wp.element.createElement;
    var __ = window.wp.i18n.__;

    var state = useState('');
    var taxID = state[0];
    var setTaxID = state[1];

    useEffect(
      function () {
        var unsubscribe = onPaymentSetup(function () {
          if (!taxID) {
            return {
              type: emitResponse.responseTypes.ERROR,
              message: __('CPF/CNPJ is required.', 'woocommerce-openpix'),
            };
          }
          return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
              paymentMethodData: {
                taxID: taxID,
              },
            },
          };
        });
        return function () {
          unsubscribe();
        };
      },
      [
        onPaymentSetup,
        taxID,
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
      ],
    );

    return createElement(
      'div',
      { className: 'wc-block-components-text-input' },

      createElement(
        'p',
        {
          'aria-label': __('CPF/CNPJ', 'woocommerce-openpix'),
          htmlFor: 'openpix-boleto-tax-id',
          style: {
            display: 'block',
            marginBottom: '0.5rem',
            fontWeight: '600',
          },
        },
        __('CPF/CNPJ', 'woocommerce-openpix'),
      ),

      createElement(
        'div',
        { className: 'wc-block-components-text-input__field-wrapper' },

        createElement('input', {
          type: 'text',
          id: 'openpix-boleto-tax-id',
          className: 'wc-block-components-text-input__input',
          value: taxID,
          onChange: (e) => setTaxID(formatTaxId(e.target.value)),
          required: true,
          placeholder: '000.000.000-00 | 00.000.000/0000-00',
          inputMode: 'numeric',
          'data-input-aria-label': '',
        }),
      ),
    );
  };

  window.wc.wcBlocksRegistry.registerPaymentMethod({
    name: 'woocommerce_openpix_boleto',
    label: label,
    content: Object(window.wp.element.createElement)(Content, null),
    edit: Object(window.wp.element.createElement)(Content, null),
    canMakePayment: function () {
      return true;
    },
    placeOrderButtonLabel: window.wp.i18n.__('Continue', 'woocommerce-openpix'),
    ariaLabel: label,
    supports: {
      features: settings.supports,
    },
  });
})();

const formatTaxId = (value) => {
  value = value.replace(/\D/g, '').slice(0, 14);

  if (value.length > 14) {
    return;
  }

  if (value.length <= 11) {
    return value
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  }

  return value
    .replace(/^(\d{2})(\d)/, '$1.$2')
    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1/$2')
    .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
};
