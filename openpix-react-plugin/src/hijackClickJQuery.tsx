// import * as $ from "jquery";
import $ from './jquery';
import * as formSubmit from './formSubmit';

// validate if openpix method is selected
const isOpenPixMethod = (): boolean => {
  return $('#payment_method_woo_openpix_plugin').is(':checked');
};

export const hijackClickJQuery = (onClick: () => boolean) => {
  $(() => {
    function isCheckoutInvalid(evt) {
      const wasSubmit = formSubmit.getFormSubmit();

      // If this submit is a result of the request callback firing,
      // let submit proceed by returning true immediately.
      if (wasSubmit) {
        if ('undefined' !== typeof evt && 'undefined' !== typeof evt.data) {
          if (
            'undefined' !==
              typeof evt.data.preserveOpenPixCheckoutSubmitValue &&
            !evt.data.preserveOpenPixCheckoutSubmitValue
          ) {
            formSubmit.setFormSubmit(false);
          }
        }
        return true;
      }

      if (!isOpenPixMethod()) {
        return true;
      }

      return false;
    }

    const paymentMethodID = 'woo_openpix_plugin';

    $('form.checkout').on('click', '#place_order', function (evt) {
      if (isCheckoutInvalid()) {
        return true;
      }

      return onClick();
    });

    // this is called by woo commerce form submit
    $('form.checkout').on(
      `checkout_place_order_${paymentMethodID}`,
      {
        preserveOpenPixCheckoutSubmitValue: true,
      },
      isCheckoutInvalid,
    );

    $('form#order_review').submit(function () {
      if (isCheckoutInvalid()) {
        return true;
      }

      return onClick();
    });
  });
};
