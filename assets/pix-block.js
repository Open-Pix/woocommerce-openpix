(function () {
  var data = window.wc.wcSettings.getSetting(
    'woocommerce_openpix_pix_data',
    {},
  );
  var label =
    window.wp.htmlEntities.decodeEntities(data.title) ||
    window.wp.i18n.__('Checkout Pix', 'woocommerce-openpix');

  var content = function (data) {
    return window.wp.htmlEntities.decodeEntities(data.description || '');
  };
  window.wc.wcBlocksRegistry.registerPaymentMethod({
    name: 'woocommerce_openpix_pix',
    label,
    content: Object(window.wp.element.createElement)(content, null),
    edit: Object(window.wp.element.createElement)(content, null),
    canMakePayment: function () {
      return true;
    },
    placeOrderButtonLabel: window.wp.i18n.__('Continue', 'woocommerce-openpix'),
    ariaLabel: label,
    supports: {
      features: data.supports,
    },
  });
})();
