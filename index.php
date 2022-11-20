<?php include_once 'config/init.php';

$API = new API;
$Store = new Store;

// Request url: https://api.cloudflare-dyndns.com/request/?domain=example.domain.com&ip=127.0.0.1&token=CloudflareAPIToken

// Get all information
$fullDomain = urlencode($_GET["domain"]);

// Extract domain if record domain is subdomain (example.domain.com => domail.com)
// NOTE: This does not work on example.co.uk domains!
$secondLevelDomain = $API->getSecondLevelDomain($fullDomain);
$ip = $_GET['ipv4'];
$token = $_GET['token'];

if (!isset($_GET["domain"]) || !isset($_GET['ipv4']) || !isset($_GET['token'])) {
    $status = [
        'success' => false,
        'errors' => [[
            'code' => '',
            'message' => 'Parameters incorrect.'
        ]]
    ];
    $API->response(400, $status);
}

// TODO: Add user input to allow domain proxy
$proxied = false;

$URL = "https://api.cloudflare.com/client/v4/zones?name={$secondLevelDomain}";
$headers = [
    "Authorization: Bearer {$token}",
    'Content-Type: application/json'
];
$response = $API->REQUEST($type = 'GET', $URL, $headers);

$domainID = $response['result'][0]['id'];
if (!isset($domainID)) {
    $status = [
        'success' => false,
        'errors' => [[
            'code' => '',
            'message' => 'Domain not found.'
        ]]
    ];
    $Store->REQUEST($ip, $fullDomain, $owner_email = null, $owner_id = null, $account_name = null, $account_id = null, $status);
    $API->response(400, $status);
}

$owner_email = $response['result'][0]['owner']['email'];
$owner_id = $response['result'][0]['owner']['id'];
$account_name = $response['result'][0]['account']['name'];
$account_id = $response['result'][0]['account']['id'];

$URL = "https://api.cloudflare.com/client/v4/zones/{$domainID}/dns_records?type=A&name={$fullDomain}";
$headers = $headers; // Request headers not changed
$status = [
    'success' => true,
    'errors' => [
        'code' => '',
        'message' => 'Record not found.'
    ]
];
$response = $API->REQUEST($type = 'GET', $URL, $headers);

if (!isset($response['result'][0]['id'])) {
    $Store->REQUEST($ip, $fullDomain, $owner_email, $owner_id, $account_name, $account_id, $status);
    $API->response(400, $status);
}

$recordID = $response['result'][0]['id'];
$oldIp = $response['result'][0]['content'];

if (!filter_var($ip, FILTER_VALIDATE_IP) || $ip == $oldIp) {
    $status = [
        'success' => true,
        'errors' => []
    ];
    $Store->REQUEST($ip, $fullDomain, $owner_email, $owner_id, $account_name, $account_id, $status);
    $API->response(200, $status);
}

$URL = "https://api.cloudflare.com/client/v4/zones/{$domainID}/dns_records/{$recordID}";
$headers = $headers; // Request headers not changed
$body = [
    'type' => 'A',
    'name' => $fullDomain,
    'content' => $ip,
    'proxied' => $proxied
];
$response = $API->REQUEST($type = 'PUT', $URL, $headers, $body);
$status = [
    'success' => $response['success'],
    'errors' => $response['errors']
];
$Store->REQUEST($ip, $fullDomain, $owner_email, $owner_id, $account_name, $account_id, $status);
$API->response(200, $status);
