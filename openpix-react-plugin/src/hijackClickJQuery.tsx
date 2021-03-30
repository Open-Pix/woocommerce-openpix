import * as $ from "jquery";

// validate if openpix method is selected
const isOpenPixMethod = (): boolean => {
  return $('#payment_method_woo_openpix_plugin').is(':checked');
}

export const hijackClickJQuery = (onClick) => {
  $(() => {
    $('form.checkout').on('click', '#place_order', function () {
      if (!isOpenPixMethod()) {
        // eslint-disable-next-line
        console.log('not openpix method');
        // return true;
        return false;
      }
      onClick();
      return false;
    });
  });
}