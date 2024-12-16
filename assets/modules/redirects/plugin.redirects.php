<?php
include_once MODX_BASE_PATH . 'assets/modules/redirects/autoload.php';
$plugin = new Pathologic\Redirects\Plugin($modx, $params);
$event = $modx->event->name;
if (method_exists($plugin, $event)) {
    $plugin->$event();
}
