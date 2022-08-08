<?php
date_default_timezone_set('America/Sao_Paulo');
//Credenciais do bd
$host = @$_ENV['db_host'] ?? 'localhost';
$name = @$_ENV['db_name'] ?? 'db_waifu';
$user = @$_ENV['db_user'] ?? 'app';
$pass = @$_ENV['db_pass'] ?? 'Gui84887573';

//Validando método de solicitação
/* $method = $_SERVER['REQUEST_METHOD'];

if ($method != 'POST') {
    http_response_code(404);
    return;
} //method not is GET */

//Configurando header com json
header('Content-Type: application/json; charset=utf-8');

//Pegando dados enviados no get
$data = json_decode(file_get_contents("php://input"), true);;
$address = @$data['address'];

if (empty($address)) {
    echo json_encode(['status' => 'failed']);
    return;
} //No have data

//Buscando balance no banco de dados 
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "SELECT balance_unlock, user_address 
        FROM tb_users 
        WHERE user_address = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('s', $address);
$stmt->execute();
$balance = @$stmt->get_result()->fetch_all(MYSQLI_ASSOC)[0];

if (empty($balance)) {
    echo json_encode(['status' => 'failed']);
    return;
}

//verificar se a data de desbloqueio já passou
$dataAtual = date('Y/m/d H:i:s');
$strDataAtual = strtotime($dataAtual);

$dataDesbloqueio = $balance['balance_unlock'];
$strDataDesbloqueio = strtotime($dataDesbloqueio);


if ($strDataAtual < $strDataDesbloqueio) { //Balance bloqueado
    echo json_encode(['status' => 'failed']);
    return;
}

//insere valor no balance do usuario e bloqueia o novo saldo
$dataDesbloqueio = date('Y/m/d H:i:s', strtotime("5 days", $strDataAtual));

$conexaoDb = new mysqli($host,  $user, $pass, $name);

$sql = "UPDATE tb_users 
        SET balance_unlock = ?,
            user_balance = user_balance + balance_unlock_value,
            balance_unlock_value = 0 
        WHERE user_address = ?";
        
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('ss', $dataDesbloqueio, $balance['user_address']);
$stmt->execute();

if (!$stmt->affected_rows) {
    echo json_encode(['status' => 'failed']);
    return;
}

//retorna successfully
echo json_encode(['status' => 'successfully']);