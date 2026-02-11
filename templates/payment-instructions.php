<?php

if (!defined('ABSPATH')) {
    exit();
} ?>
<script>
  window.__initialProps__ = <?php echo wp_json_encode([
      'correlationID' => $correlationID,
      'environment' => $environment,
      'appID' => $appID,
      'pluginUrl' => $pluginUrl,
  ]); ?>
</script>

<script src="<?php echo esc_url($src); ?>" async></script>
<div id='openpix-order' style='margin-bottom: 40px'></div>