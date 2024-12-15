<?php


use Pathologic\Redirects\Controllers\Export;
use Pathologic\Redirects\Controllers\Import;
use Pathologic\Redirects\Controllers\Redirects;

define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

include_once(__DIR__ . "/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
if (!isset($_SESSION['mgrValidated'])) {
    $modx->sendErrorPage();
}
include_once 'autoload.php';
$modx->invokeEvent('OnManagerPageInit');

$mode = (isset($_REQUEST['mode']) && is_scalar($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';

switch ($mode) {
    case 'export/start':
        $controller = new Export($modx);
        $mode = 'start';
        break;
    case 'export/process':
        $controller = new Export($modx);
        $mode = 'process';
        break;
    case 'import/process':
        $controller = new Import($modx);
        $mode = 'process';
        break;
    default:
        $controller = new Redirects($modx);
}

if (!empty($mode) && method_exists($controller, $mode)) {
    $out = call_user_func_array([$controller, $mode], []);
} else {
    $out = call_user_func_array([$controller, 'list'], []);
}

echo is_array($out) ? json_encode($out) : $out;
exit;
