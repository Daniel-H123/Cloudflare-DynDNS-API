<?php include_once 'config/init.php';

$fnc = new Functions;
$API = new API;

// Request url: https://api.cloudflare-dyndns.com/request/?domain=example.domain.com&ip=127.0.0.1&token=CloudflareAPIToken

$fullDomain = urlencode($_GET["domain"]);

// Extract domain if record domain is subdomain (example.domain.com => domail.com)
// NOTE: This does not work on example.co.uk domains!
$secondLevelDomain = $fnc->getSecondLevelDomain($fullDomain);
// TODO: Add IPV6
$ip = $_GET['ipv4'];
$token = $_GET['token'];

if (isset($ip)) {
    $fnc->ip = $ip;
}
if (isset($fullDomain)) {
    $fnc->fullDomain = $fullDomain;
}

// Check if all parameters are complete
if (!isset($_GET["domain"]) || !isset($_GET['ipv4']) || !isset($_GET['token'])) {
    $status = $fnc->errorMessage('Parameters incorrect.');
    die($fnc->response($status, '400'));
}

// Check if ip-adress is valid
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    $status = $fnc->errorMessage('Invalid IP-adress entered.');
    die($fnc->response($status, '400'));
}

// TODO: Add user input to allow domain proxy
$proxied = false;

// Set authentication headers
$status = $API->initAPI($token);
if ($status !== true) {
    $API->close();
    die($fnc->response($status, '400'));
}

// Get domain id from cloudflare api
$domainID = $API->getDomainID($secondLevelDomain);

$userData = $API->getUserData();
$fnc->saveUserData($userData);

// if the domain id is empty, the domain does not exist on the account
if ($domainID === false) {
    $status = $fnc->errorMessage('Domain not found.');
    $API->close();
    die($fnc->response($status, '400'));
}

// Get record information from cloudflare api
$API->getRecordInformation($domainID, $fullDomain);
$recordID = $API->getRecordID();

if ($recordID === false) {
    $status = $fnc->errorMessage('Record not found.');
    $API->close();
    die($fnc->response($status, '400'));
}

$currentIP = $API->getRecordIP();

if ($ip == $currentIP) {
    $status = $fnc->successMessage();
    die($fnc->response($status, '200'));
}

$status = $API->changeRecord($domainID, $recordID, $fullDomain, $ip);

if ($status === true) {
    $status = $fnc->successMessage();
    die($fnc->response($status, '200'));
}
die($fnc->response($status, '400'));