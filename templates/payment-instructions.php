<?php

if (!defined('ABSPATH')) {
    exit();
} ?>
<script src="<?= $src ?>" async ></script>
<script>
  window.__initialProps__ = <?php echo json_encode([
      'correlationID' => $correlationID,
      'environment' => $environment,
      'appID' => $appID,
      'pluginUrl' => $pluginUrl,
      'realtime' => $realtime,
  ]); ?>

</script>
<div id='success-content'></div>
<div id='openpix-order' style='margin-bottom:10px'></div>