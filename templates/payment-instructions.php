<?php

if (!defined('ABSPATH')) {
    exit();
} ?>
<script>
  window.__initialProps__ = <?php echo json_encode([
      'correlationID' => $correlationID,
      'environment' => $environment,
      'appID' => $appID,
      'pluginUrl' => $pluginUrl,
      'realtime' => $realtime,
      'beta' => $beta,
  ]); ?>
</script>

<script src="<?= $src ?>" async></script>
<div id='openpix-order' style='margin-bottom: 40px'></div>