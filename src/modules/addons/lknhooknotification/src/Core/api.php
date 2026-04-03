<?php

use Lkn\HookNotification\Core\WHMCS\ApiHandler;
use WHMCS\Database\Capsule;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../../init.php';
require_once __DIR__ . '/../Core/Shared/param_funcs.php';

try {
    $endpoint = $_GET['endpoint'] ?? '';

    if (empty($endpoint)) {
        http_response_code(400);
        echo json_encode(['error' => 'endpoint requerido']);
        exit;
    }

    // Validar nonce de sessão com comparação segura (uso único por carregamento de página)
    $receivedNonce = $_GET['nonce'] ?? '';
    $sessionNonce  = $_SESSION['lkn_hn_reset_nonce'] ?? '';

    if (
        empty($sessionNonce)
        || empty($receivedNonce)
        || !hash_equals($sessionNonce, $receivedNonce)
    ) {
        http_response_code(401);
        echo json_encode(['error' => 'Não autorizado']);
        exit;
    }

    // Invalidar nonce após uso (token de uso único)
    unset($_SESSION['lkn_hn_reset_nonce']);

    // Rate limiting por IP: máximo de 10 requisições por minuto
    $clientIp     = $_SERVER['REMOTE_ADDR'] ?? '';
    $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));

    $attemptsCount = Capsule::table('mod_lkn_hook_notification_reports')
        ->where('notification', 'ApiPublicRateLimit')
        ->where('msg', $clientIp)
        ->where('created_at', '>=', $oneMinuteAgo)
        ->count();

    if ($attemptsCount >= 10) {
        http_response_code(429);
        echo json_encode(['error' => 'Muitas requisições. Tente novamente em breve.']);
        exit;
    }

    // Registrar tentativa para controle de rate limiting por IP
    Capsule::table('mod_lkn_hook_notification_reports')->insert([
        'notification' => 'ApiPublicRateLimit',
        'msg'          => $clientIp,
        'status'       => 'rate_limit_check',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    ApiHandler::getInstance()->routeEndpoint($endpoint);
} catch (Throwable $th) {
    lkn_hn_log('API error', ['exception' => $th->__toString()]);
}
