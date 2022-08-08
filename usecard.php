<?php
date_default_timezone_set('America/Sao_Paulo');
//Checando se o metodo é POST
/* $method = $_SERVER['REQUEST_METHOD'];
if ($method != 'POST') {
    http_response_code(404);
    return;
} //method not is POST  */

header('Content-Type: application/json; charset=utf-8');

//Credenciais do bd
$host = @$_ENV['db_host'] ?? 'localhost';
$name = @$_ENV['db_name'] ?? 'db_waifu';
$user = @$_ENV['db_user'] ?? 'app';
$pass = @$_ENV['db_pass'] ?? 'Gui84887573';

//Pegando dados
$data = json_decode(file_get_contents("php://input"), true);
$cardId = @$data['cardid'];
$address = @$data['address'];

if (is_null($cardId) || is_null($address)) {
    echo json_encode(['status' => 'failed1']);
    return;
} //No have data

//Verifica se a carta pode ser usada(já se passaram 24 horas desde o ultimo uso)
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "SELECT * FROM tb_assets WHERE asset_id = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('i', $cardId);
$stmt->execute();
$asset = @$stmt->get_result()->fetch_all(MYSQLI_ASSOC)[0];
if (!$asset) {
    echo json_encode(['status' => 'failed2']);
    return;
}

if ($asset['user_address'] != $address) {
    echo json_encode(['status' => 'failed3']);
    return;
}

$dataAtual = date('Y/m/d H:i:s');
$strDataAtual = strtotime($dataAtual);

$dataDesbloqueio = $asset['asset_unlock'];
$strDataDesbloqueio = strtotime($dataDesbloqueio);


if ($strDataAtual < $strDataDesbloqueio) { //Carta bloqueada
    echo json_encode(['status' => 'failed4']);
    return;
}

//Sorteia um valor de acordo com a classe da carta
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "SELECT * FROM tb_cards WHERE card_id = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('i', $asset['card_id']);
$stmt->execute();
$card = @$stmt->get_result()->fetch_all(MYSQLI_ASSOC)[0];

if (!$card) {
    echo json_encode(['status' => 'failed5']);
    return;
}

$value = 0;
if ($card['card_type'] == 'common') {
    $value = rand(1, 10);
} else if ($card['card_type'] == 'rare') {
    $value = rand(1, 15);
} else if ($card['card_type'] == 'legendary') {
    $value = rand(1, 20);
} else if ($card['card_type'] == 'epic') {
    $value = rand(1, 25);
} else {
    echo json_encode(['status' => 'failed6']);
    return;
}

//Insere saldo no proximo desbloqueio
$conexaoDb = new mysqli($host,  $user, $pass, $name);

$sql = "UPDATE tb_users SET balance_unlock_value = balance_unlock_value + ? WHERE user_address = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('ds', $value, $address);
$stmt->execute();

//testando se tudo ocorreu bem
if (!$stmt->affected_rows) {
    echo json_encode(['status' => 'failed7']);
    return;
}

//Bloqueia carta por 24 horas
$dataDesbloqueioConta = date('Y/m/d H:i:s', strtotime("1 days", $strDataAtual));

$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "UPDATE tb_assets SET asset_unlock = ? WHERE asset_id = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('si', $dataDesbloqueioConta, $cardId);
$stmt->execute();

//testando se tudo ocorreu bem
if (!$stmt->affected_rows) {
    echo json_encode(['status' => 'failed8']);
    return;
}

//Retorna valor gerado
echo json_encode(['valor' => $value]);