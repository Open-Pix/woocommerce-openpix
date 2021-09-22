(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const btnCopyEmv = document.querySelector('#btnCopyEmv');

    if (btnCopyEmv) {
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
    }

    function getApiUrl() {
      if (window.__initialProps__.environment == 'development') {
        return 'http://localhost:5001';
      }

      if (window.__initialProps__.environment == 'staging') {
        return 'https://api.openpix.dev';
      }

      // production
      return 'https://api.openpix.com.br';
    }

    function getCorrelationID() {
      return window.__initialProps__.correlationID;
    }

    function getAppID() {
      return window.__initialProps__.appID;
    }

    let shouldPolling = true;

    async function checkChargeStatus(payload) {
      let { correlationID, url, appID } = payload;

      let result = await fetch(`${url}/${correlationID}`, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          Authorization: appID,
        },
      });

      let data = await result.json();

      if (!data.charge) {
        return null;
      }

      const { charge } = data;

      if (charge.status === 'ACTIVE') {
        return null;
      }

      if (charge.status === 'EXPIRED') {
        let checkoutSuccessClass = document.getElementById('success-content');

        checkoutSuccessClass.innerHTML = `
          <div class="openpix-content">
            <div class="openpix-pix-completed">
              <span class="label">Pagamento expirado</span>
            </div>
          </div>
        `;

        shouldPolling = false;
        return null;
      }

      let checkoutSuccessClass = document.getElementById('success-content');

      checkoutSuccessClass.innerHTML = `
          <div class="openpix-content">
            <div class="openpix-pix-completed">
              <span class="label-completed">Pagamento realizado</span>
              <span class="label">Pix foi realizado com sucesso!</span>
              <img src="https://i.imgur.com/trayovl.png" alt="check image">
            </div>
          </div>
        `;

      shouldPolling = false;
    }

    async function polling() {
      let url = `${getApiUrl()}/api/openpix/v1/charge`;

      const payload = {
        correlationID: getCorrelationID(),
        api: getApiUrl(),
        appID: getAppID(),
        url,
      };

      await checkChargeStatus(payload);
      if (shouldPolling) {
        setTimeout(polling, 2000);
      }
    }

    setTimeout(polling, 2000);
  });
})();
