<?php

if (!defined('ABSPATH')) {
    exit();
} ?>

<div style="margin-bottom: 40px">
    <p style="width: 100%; text-align: center">
        Efetue o pagamento PIX usando o <strong>QRCode</strong> ou
        usando <strong>PIX copia e cola</strong>, se preferir:
    </p>
    <div>
        <div>
            <img
                src="<?php echo $qrCodeImage; ?>"
                title="Código de barras do PIX deste pedido."
            /><br />
        </div>
        <div>
            <ul>
                <li>
              <span
              >Abra o app do seu banco ou instituição financeira e
                <strong>entre no ambiente Pix</strong>.</span
              >
                </li>
                <li>
              <span
              >Escolha a opção <strong>Pagar com QR Code</strong> e escanele o
                código ao lado.</span
              >
                </li>
                <li>
                    <span>Confirme as informações e finalize o pagamento.</span>
                </li>
            </ul>
        </div>
    </div>
    <div>
        <p>Pagar com PIX copia e cola </p>
        <div class="textarea-container">
          <textarea id='brCode' readonly="" rows="3">
              <?php echo $brCode; ?>
</textarea
          >
        </div>
    </div>
    <p style="width: 100%; text-align: center; margin-top: 20px">
        Após o pagamento, podemos levar alguns segundos para confirmar o seu
        pagamento.<br />Você será avisado assim que isso ocorrer!
    </p>
</div>