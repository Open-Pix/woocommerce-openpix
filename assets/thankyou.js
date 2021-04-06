(function ($) {
  $(function () {
    function copyEmv() {
      var brCode = $('#brCode');
      brCode.trigger('select');
      document.execCommand('copy');
      document.getSelection().collapseToEnd();
      let button = document.querySelector('#copyBrCode');
      let original = button.innerHTML;
      button.innerHTHML = 'Copiado!';

      setTimeout(function () {
        button.innerHTML = original;
      }, 2 * 1000);
    }

    $('#copyBrCode').on('click', function () {
      copyEmv();
    });
  });
  // eslint-disable-next-line
})(jQuery);
