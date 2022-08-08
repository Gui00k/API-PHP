<?php
date_default_timezone_set('America/Sao_Paulo');
//Checando se o metodo é POST
/* $method = $_SERVER['REQUEST_METHOD'];
if ($method != 'POST') {
    http_response_code(404);
    return;
} //method not is POST  */

header('Content-Type: application/json; charset=utf-8');

//Pegando dados
$data = json_decode(file_get_contents("php://input"), true);
$address = @$data['address'];

if (!$address) {
    echo json_encode(['status' => 'failed']);
    return;
} //No have data

//Iniciando conexão com morales
$url  = "https://deep-index.moralis.io/api/v2/$address/erc20/transfers?chain=bsc%20testnet&limit=1";
$headers = ['X-API-Key: HG2arGB4kv14ybDyhRtV29xFtS8iZ3E7cHsFweQnGUBHZI1St2hklDpO6WognuyA'];
$curl   = curl_init();

//Configurando request
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_RETURNTRANSFER => true
]);

//Buscando resultado
$result = curl_exec($curl);

//Finalizando conexão
curl_close($curl);

//verificando se houve uma resposta
if (!$result) {
    echo json_encode(['status' => 'failed']);
    return;
}

//Tratando dados recebidos do moralis
$result = @json_decode($result, true)['result'][0];
if (!$result) {
    echo json_encode(['status' => 'failed']);
    return;
}

if (strtolower($address) == strtolower($result['to_address'])) {
    echo json_encode(['status' => 'failed']);
    return;
}

$value = ($result['value'] / 1000000000000000000) * 0.95;
$hash = $result['transaction_hash'];
//Formatando data
$depositDate = date('Y/m/d H:i:s', strtotime($result['block_timestamp']));

//Conectando banco de dados
$host = @$_ENV['db_host'] ?? 'localhost';
$name = @$_ENV['db_name'] ?? 'db_waifu';
$user = @$_ENV['db_user'] ?? 'app';
$pass = @$_ENV['db_pass'] ?? 'Gui84887573';

//Verificando se transação já existe
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "SELECT * FROM tb_deposit WHERE deposit_hash = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('s', $hash);
$stmt->execute();
$result = $stmt->fetch();

if ($result != false) {
    echo json_encode(['status' => 'failed']);
    return;
}

//Inserindo deposito
$conexaoDb = new mysqli($host,  $user, $pass, $name);
$sql = "INSERT INTO tb_deposit(user_address, deposit_hash, deposit_value, deposit_date) VALUES (?, ?, ?, ?)";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('ssds', $address, $hash, $value, $depositDate);

//testando se tudo ocorreu bem
if (!$stmt->execute()) {
    echo json_encode(['status' => 'failed']);
    return;
}


//Somando ao balance do usuario
$conexaoDb = new mysqli($host,  $user, $pass, $name);

$sql = "UPDATE tb_users SET user_balance = user_balance + ? WHERE user_address = ?";
$stmt = $conexaoDb->prepare($sql);
$stmt->bind_param('ds', $value, $address);
$stmt->execute();

//testando se tudo ocorreu bem
if (!$stmt->affected_rows) {
    echo json_encode(['status' => 'failed']);
    return;
}

//enviando status de sucesso
echo json_encode(['status' => 'succesfully']);