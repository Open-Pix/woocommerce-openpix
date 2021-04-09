(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const btnCopyEmv = document.querySelector('#btnCopyEmv');

    btnCopyEmv.addEventListener('click', function () {
      const textAreaEmv = document.querySelector('#emv');

      textAreaEmv.select();
      textAreaEmv.setSelectionRange(0, 99999);
      document.execCommand('copy');
      document.getSelection().collapseToEnd();

      const originalText = btnCopyEmv.innerHTML;
      btnCopyEmv.innerHTML = 'Copiado!';

      setTimeout(function () {
        btnCopyEmv.innerHTML = originalText;
      }, 10 * 1000);
    });
  });
})();
