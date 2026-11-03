<?php
// Помощник: находит chat_id группы MAX.
// 1) Добавьте бота в группу. 2) Запустите: php tools/max-chat-id.php [ТОКЕН_БОТА] (токен можно взять из MAX_BOT_TOKEN в .env)
// 3) Напишите в группе любое сообщение (или добавьте бота заново). Скрипт выведет chat_id.
// Нужен PHP с расширением curl. Если на токене включён webhook (POST /subscriptions), long polling не работает.
declare(strict_types=1);

$token = $argv[1] ?? getenv('MAX_BOT_TOKEN') ?: '';
$envFile = dirname(__DIR__) . '/.env';
if ($token === '' && is_file($envFile) && preg_match('/^MAX_BOT_TOKEN=(.*)$/m', (string)file_get_contents($envFile), $m)) {
    $token = trim($m[1], " \t\"'\r");
}
if ($token === '') {
    fwrite(STDERR, "Использование: php tools/max-chat-id.php [ТОКЕН_БОТА] (или MAX_BOT_TOKEN в .env)\n");
    exit(1);
}
$base = getenv('MAX_API_BASE') ?: 'https://platform-api2.max.ru';

function findChatIds($node, array &$found): void
{
    if (!is_array($node)) {
        return;
    }
    foreach ($node as $key => $value) {
        if ($key === 'chat_id' && (is_int($value) || is_string($value))) {
            $found[(string)$value] = true;
        }
        findChatIds($value, $found);
    }
}

echo "Жду событий... Добавьте бота в группу или напишите там сообщение. Выход: Ctrl+C\n";
$marker = null;
while (true) {
    $url = $base . '/updates?timeout=30&limit=100&types=bot_added,message_created' . ($marker !== null ? '&marker=' . $marker : '');
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ['Authorization: ' . $token], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($body === false || $status !== 200) {
        fwrite(STDERR, "Ошибка запроса (HTTP $status): " . substr((string)$body, 0, 300) . "\n");
        sleep(3);
        continue;
    }
    $data = json_decode($body, true) ?: [];
    $marker = $data['marker'] ?? $marker;
    foreach ($data['updates'] ?? [] as $update) {
        $ids = [];
        findChatIds($update, $ids);
        echo ($update['update_type'] ?? '?') . ': chat_id = ' . ($ids ? implode(', ', array_keys($ids)) : 'не найден') . "\n";
    }
}
