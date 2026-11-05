<?php
// Приём заявки с лендинга: запись в файл и отправка в группу MAX.
// Настройки (токен бота и chat_id) лежат в .env в корне проекта, вне веб-корня.
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function clean(string $value, int $max): string
{
    $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $max);
}

// Защита от формул при открытии CSV в Excel
function csvSafe(string $value): string
{
    if (preg_match('/^\+[\d\s()-]+$/', $value)) {
        return $value; // обычный телефон с плюсом
    }
    return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
}

function logError(string $dir, string $message): void
{
    @file_put_contents($dir . '/errors.log', date('c') . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    reply(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

// Настройки читаются из .env в корне проекта (рядом с public_html, вне веб-корня)
function loadEnv(string $file): array
{
    $env = [];
    if (!is_file($file)) {
        return $env;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        $env[trim($key)] = $value;
    }
    return $env;
}

$env = loadEnv(getenv('ENV_FILE') ?: dirname(__DIR__) . '/.env');
$setting = static fn(string $key, string $default = ''): string => (string)($env[$key] ?? (getenv($key) ?: $default));

$dataDir = $setting('LEADS_DIR', dirname(__DIR__) . '/.leads');
if (!is_dir($dataDir) && !@mkdir($dataDir, 0750, true) && !is_dir($dataDir)) {
    reply(500, ['ok' => false, 'error' => 'storage_unavailable']);
}

// Ловушка для ботов: поле скрыто от людей. Делаем вид, что всё хорошо.
if (clean((string)($_POST['website'] ?? ''), 200) !== '') {
    reply(200, ['ok' => true]);
}

$company   = clean((string)($_POST['company'] ?? ''), 150);
$phone     = clean((string)($_POST['phone'] ?? ''), 30);
$employees = clean((string)($_POST['employees'] ?? ''), 10);
$consent   = ($_POST['consent'] ?? '') !== '';

$digits = preg_replace('/\D+/', '', $phone) ?? '';
$errors = [];
if ($company === '') {
    $errors['company'] = 'Укажите компанию';
}
if (strlen($digits) < 10 || strlen($digits) > 15) {
    $errors['phone'] = 'Укажите телефон';
}
if ($employees !== '' && !ctype_digit($employees)) {
    $errors['employees'] = 'Только число';
}
if (!$consent) {
    $errors['consent'] = 'Нужно согласие на обработку данных';
}
if ($errors) {
    reply(422, ['ok' => false, 'errors' => $errors]);
}

// Простое ограничение частоты: не чаще 5 заявок в 10 минут с одного IP
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$rateFile = $dataDir . '/rate.json';
$now = time();
$rate = [];
if (is_file($rateFile)) {
    $rate = json_decode((string)file_get_contents($rateFile), true) ?: [];
}
$hash = hash('sha256', $ip);
$recent = array_values(array_filter($rate[$hash] ?? [], static fn($t) => $t > $now - 600));
if (count($recent) >= 5) {
    reply(429, ['ok' => false, 'error' => 'too_many_requests']);
}
$recent[] = $now;
$rate[$hash] = $recent;
foreach ($rate as $k => $times) {
    if (!array_filter($times, static fn($t) => $t > $now - 600)) {
        unset($rate[$k]);
    }
}
@file_put_contents($rateFile, json_encode($rate), LOCK_EX);

$created = date('Y-m-d H:i:s');
$saved = false;

// 1. Запись в файл leads.csv (UTF-8 с BOM, разделитель «;» — открывается в Excel)
$csv = $dataDir . '/leads.csv';
$isNew = !is_file($csv);
$fh = @fopen($csv, 'ab');
if ($fh && flock($fh, LOCK_EX)) {
    if ($isNew) {
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['Дата', 'Компания', 'Телефон', 'Сотрудников'], ';');
    }
    $saved = fputcsv($fh, [$created, csvSafe($company), csvSafe($phone), $employees], ';') !== false;
    fflush($fh);
    flock($fh, LOCK_UN);
}
if ($fh) {
    fclose($fh);
}
if (!$saved) {
    logError($dataDir, 'cannot write leads.csv');
}

// 2. Отправка в группу MAX (обычным текстом, без разметки)
$sent = false;
$token  = $setting('MAX_BOT_TOKEN');
$chatId = $setting('MAX_CHAT_ID');
if ($token !== '' && $chatId !== '' && function_exists('curl_init')) {
    $base = rtrim($setting('MAX_API_BASE', 'https://platform-api2.max.ru'), '/');
    $text = "Новая заявка с сайта\n"
        . "Компания: {$company}\n"
        . "Телефон: {$phone}\n"
        . 'Сотрудников: ' . ($employees !== '' ? $employees : 'не указано') . "\n"
        . "Время: {$created}";
    $ch = curl_init($base . '/messages?chat_id=' . rawurlencode($chatId));
    $opts = [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['text' => $text], JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
    ];
    if ($setting('MAX_CA_BUNDLE') !== '') {
        $opts[CURLOPT_CAINFO] = $setting('MAX_CA_BUNDLE');
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $sent = $response !== false && $status === 200;
    if (!$sent) {
        logError($dataDir, 'max send failed: http ' . $status . ' ' . curl_error($ch) . ' ' . substr((string)$response, 0, 300));
    }
    curl_close($ch);
} else {
    logError($dataDir, 'max is not configured');
}

if (!$saved && !$sent) {
    reply(500, ['ok' => false, 'error' => 'not_delivered']);
}
reply(200, ['ok' => true]);
