<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'config_missing']);
    exit;
}

$config = require $configPath;

if (
    empty($config['brevo_api_key']) ||
    $config['brevo_api_key'] === 'YOUR_BREVO_API_KEY' ||
    empty($config['sender_email']) ||
    empty($config['to_email'])
) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'config_incomplete']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

if (!empty($data['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

session_start();
$now = time();
$last = (int) ($_SESSION['support_last_sent'] ?? 0);
if ($now - $last < 20) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'rate_limited']);
    exit;
}

$name = trim((string) ($data['name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$subjectKey = trim((string) ($data['subject'] ?? 'general'));
$message = trim((string) ($data['message'] ?? ''));
$lang = trim((string) ($data['lang'] ?? 'en'));

if ($name !== '' && mb_strlen($name) > 120) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_name']);
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 200) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_email']);
    exit;
}

if ($message === '' || mb_strlen($message) < 5 || mb_strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_message']);
    exit;
}

$subjects = [
    'general' => 'General question',
    'bug' => 'Bug report',
    'feedback' => 'Feedback',
    'privacy' => 'Privacy',
    'other' => 'Other',
];
$subjectLabel = $subjects[$subjectKey] ?? $subjects['general'];
$mailSubject = "[I'm clean] {$subjectLabel}";

$safeName = $name !== '' ? $name : 'Anonymous';
$safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeNameHtml = htmlspecialchars($safeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeSubject = htmlspecialchars($subjectLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeLang = htmlspecialchars($lang, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$html = <<<HTML
<p><strong>New support message from I'm clean</strong></p>
<p><strong>Subject:</strong> {$safeSubject}<br>
<strong>From:</strong> {$safeNameHtml} &lt;{$safeEmail}&gt;<br>
<strong>Language:</strong> {$safeLang}</p>
<hr>
<p style="white-space:pre-wrap">{$safeMessage}</p>
HTML;

$text = "New support message from I'm clean\n"
    . "Subject: {$subjectLabel}\n"
    . "From: {$safeName} <{$email}>\n"
    . "Language: {$lang}\n\n"
    . $message;

$payload = [
    'sender' => [
        'name' => $config['sender_name'] ?: "I'm clean",
        'email' => $config['sender_email'],
    ],
    'to' => [[
        'email' => $config['to_email'],
        'name' => $config['to_name'] ?: "I'm clean Support",
    ]],
    'replyTo' => [
        'email' => $email,
        'name' => $safeName,
    ],
    'subject' => $mailSubject,
    'htmlContent' => $html,
    'textContent' => $text,
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'content-type: application/json',
        'api-key: ' . $config['brevo_api_key'],
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 20,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'upstream_unreachable', 'detail' => $curlError]);
    exit;
}

if ($status < 200 || $status >= 300) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'upstream_failed', 'status' => $status]);
    exit;
}

$_SESSION['support_last_sent'] = $now;
echo json_encode(['ok' => true]);
