<?php
date_default_timezone_set('America/Sao_Paulo');
$valorCarta = 10;
/* $method = $_SERVER['REQUEST_METHOD'];
if ($method != 'GET') {
    http_response_code(404);
    return;
} //method not is POST  */

header('Content-Type: application/json; charset=utf-8');

//Pegando dados
$data = $_GET;
$address = @$data['address'];

if (!$address) {
    echo json_encode(['status' => 'failed']);
    return;
} //No have data

//Conectando banco de dados
$host = @$_ENV['db_host'] ?? 'localhost';
$name = @$_ENV['db_name'] ?? 'dataname';
$user = @$_ENV['db_user'] ?? 'user';
$pass = @$_ENV['db_pass'] ?? 'password';

//Validando compra
if (false) { //aqui deve ter algum tipo de validação para a compra, corrija o mais rapido possivel(falha grave de segurança).
    echo json_encode(['status' => 'failed']);
    return;
}

//Verificando saldo da carteira
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "SELECT user_balance FROM tb_users WHERE user_address = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('s', $address);
$stmt->execute();

$result = @$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!count($result)) {
    echo json_encode(['status' => 'failed']);
    return;
}

//Verificando se saldo atual é o suficiente para a compra.
$balanceAtual = $result[0]['user_balance'];
if ($balanceAtual < $valorCarta) {
    echo json_encode(['status' => 'failed']);
    return;
}

//Descontando 500 da conta
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "UPDATE tb_users SET user_balance = user_balance - ? WHERE user_address = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('ds', $valorCarta, $address);
$stmt->execute();

//testando se tudo ocorreu bem
if (!$stmt->affected_rows) {
    echo json_encode(['status' => 'failed']);
    return;
}

//Sorteando entre as classes Commum, Rare, Epic e Legendary
$numeroSorteado = rand(1, 100);
$cardType = '';
if ($numeroSorteado <= 70) {
    $cardType = 'common';
} else if ($numeroSorteado <= 94) {
    $cardType = 'rare';
} else if ($numeroSorteado <= 99) {
    $cardType = 'legendary';
} else if ($numeroSorteado == 100) {
    $cardType = 'epic';
} else {
    echo json_encode(['status' => 'failed']);
    return;
}

//Sorteando carta da classe no banco de dados
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "SELECT * FROM tb_cards WHERE card_type = ? ORDER BY rand() LIMIT 1;";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('s', $cardType);
$stmt->execute();
$result = @$stmt->get_result()->fetch_all(MYSQLI_ASSOC)[0];
if (!$result) {
    echo json_encode(['status' => 'failed']);
    return;
}

$card = [
    'id' => $result['card_id'],
    'name' => $result['card_name'],
    'type' => $result['card_type'],
    'src' => $result['card_img_src'],
];

//Inserindo carta sorteada na carteira
$dataAtual = date('Y/m/d H:i:s');
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "INSERT INTO tb_assets(user_address, card_id, asset_unlock) VALUES (?, ?, ?)";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('sis', $address, $card['id'], $dataAtual);

//testando se tudo ocorreu bem
if (!$stmt->execute()) {
    echo json_encode(['status' => 'failed']);
    return;
}
//enviando carta ao usuario
$card['id'] = $conexaoDb->insert_id;
echo json_encode($card);