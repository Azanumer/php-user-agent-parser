<?php

declare(strict_types=1);

require __DIR__ . '/../src/UserAgentParser.php';

use Azanumer\UserAgent\UserAgentParser;

$samples = array(
    // Desktop Chrome on Windows 10
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    // Safari on macOS
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
    // Chrome on Android phone
    'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36',
    // Safari on iPhone
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    // iPad (tablet)
    'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    // Firefox on Linux
    'Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0',
    // Edge on Windows (ships a Chrome token — must still win as Edge)
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0',
    // Googlebot (fakes Chrome — must still win as a bot)
    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    // Empty UA
    '',
);

foreach ($samples as $ua) {
    $parsed = UserAgentParser::parse($ua);
    echo $parsed . PHP_EOL;
    echo '  browser=' . $parsed->browser . ' version=' . $parsed->version
        . ' os=' . $parsed->os . ' ' . $parsed->osVersion
        . ' device=' . $parsed->device
        . ' bot=' . ($parsed->isBot() ? $parsed->bot : 'no') . PHP_EOL;
}
