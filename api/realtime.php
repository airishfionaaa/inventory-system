<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

function pusherTrigger(string $channel, string $event, array $data): void {
    $pusher = new Pusher\Pusher(
        $_ENV['PUSHER_KEY'],
        $_ENV['PUSHER_SECRET'],
        $_ENV['PUSHER_APP_ID'],
        ['cluster' => $_ENV['PUSHER_CLUSTER'], 'useTLS' => true]
    );
    $pusher->trigger($channel, $event, $data);
}