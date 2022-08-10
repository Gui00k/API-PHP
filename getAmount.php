<?php
date_default_timezone_set('America/Sao_Paulo');
$host = @$_ENV['db_host'] ?? 'localhost';
$name = @$_ENV['db_name'] ?? 'dataname';
$user = @$_ENV['db_user'] ?? 'user';
$pass = @$_ENV['db_pass'] ?? 'password';

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

$conexaoDb = new mysqli($host,  $user, $pass, $name);

$sql = "SELECT user_balance FROM tb_users WHERE user_address = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('s', $address);
$stmt->execute();

$result = @$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!count($result)) {
    echo json_encode(['status' => 'failed']);
    return;
} //No have address
$result = $result[0]['user_balance'];
$result = ['balance' => $result];
//Send amount
echo json_encode($result);