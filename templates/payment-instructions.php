<?php

if (!defined('ABSPATH')) {
    exit();
} ?>

<div style="margin-bottom: 40px">
    <p class="openpix-text-align-center">
        Efetue o pagamento Pix usando o <string>QRCode</string> ou usando
        <strong>Pix copia e cola</strong>, se preferir:
    </p>

    <div class="openpix-container">
        <div class="openpix-qrcode-container">
            <img
                    class="openpix-qrcode-image"
                    title="QRCode Pix deste pedido."
                        src="<?php echo $qrCodeImage; ?>"
            />
        </div>
        <div class="openpix-instructions">
            <ul>
                <li>
              <span>
                Abra o app do seu banco ou instituição financeira e
                <strong>entre no ambiente Pix</strong>.
              </span>
                </li>
                <li>
              <span>
                Escolha a opção <strong>Pagar com QR Code</strong> e escaneie o
                código ao lado.
              </span>
                </li>
                <li>
                    <span>Confirme as informações e finalize o pagamento.</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="openpix-copy-paste-container">
        <p class="openpix-text-align-center">
            Pagar com Pix copia e cola
            <button id="btnCopyEmv" class="openpix-copy-button">Copiar</button>
        </p>
        <div class="openpix-textarea-container">
                      <textarea id="emv" readonly="" rows="3" class="openpix-copy-textarea"><?php echo $brCode; ?></textarea>
        </div>
    </div>
    <p class="openpix-text-align-center">
        Após o pagamento, podemos levar alguns segundos para confirmar o seu
        pagamento.<br />Você será avisado assim que isso ocorrer!
    </p>
</div>
</div>