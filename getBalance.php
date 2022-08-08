<?php
date_default_timezone_set('America/Sao_Paulo');
$host = @$_ENV['db_host'] ?? 'localhost';
$name = @$_ENV['db_name'] ?? 'db_waifu';
$user = @$_ENV['db_user'] ?? 'app';
$pass = @$_ENV['db_pass'] ?? 'Gui84887573';

/* $method = $_SERVER['REQUEST_METHOD'];

if ($method != 'GET') {
    http_response_code(404);
    return;
} //method not is GET */

header('Content-Type: application/json; charset=utf-8');

$address = @$_GET['address'];
if (!$address) {
    echo json_encode(['status' => 'failed']);
    return;
} //No have address

//
$conexaoDb = new mysqli($host,  $user, $pass, $name);

$sql = "SELECT balance_unlock_value AS value, balance_unlock AS dataDesbloqueio FROM tb_users WHERE user_address = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('s', $address);
$stmt->execute();

$result = @$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!count($result)) {
    echo json_encode(['status' => 'failed']);
    return;
} //No have address
$result = $result[0];
//Send amount
echo json_encode($result);