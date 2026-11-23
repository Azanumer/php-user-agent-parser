# php-user-agent-parser

Dependency-free PHP user-agent parser: browser, version, operating system, device class and bot detection from a single UA string — no external database, no composer dependency.

## Usage

```php
require 'src/UserAgentParser.php';

use Azanumer\UserAgent\UserAgentParser;

$ua = UserAgentParser::parse($_SERVER['HTTP_USER_AGENT'] ?? '');

$ua->browser;    // 'Chrome'
$ua->version;    // '131.0.6778.87'
$ua->os;         // 'Windows'
$ua->osVersion;  // '10'
$ua->device;     // 'desktop' | 'mobile' | 'tablet'
$ua->isBot();    // false
$ua->bot;        // 'Googlebot' when isBot(), '' otherwise

echo $ua;        // "Chrome 131.0.6778.87 on Windows 10 (desktop)"
```

## What it detects

- **Browsers** — Chrome, Edge, Firefox, Safari, Opera, Opera Mini, Brave, Vivaldi, Samsung Internet, Yandex Browser, Internet Explorer
- **Operating systems** — Windows (NT 5.1–10.0 mapped to XP–10), macOS, iOS, Android, Linux, Chrome OS
- **Devices** — desktop, mobile, tablet (iPad, and Android without a `Mobile` token)
- **Bots** — Googlebot, Bingbot, DuckDuckBot, GPTBot, ClaudeBot, AhrefsBot, SemrushBot, Applebot, social crawlers (facebookexternalhit, Twitterbot, LinkedInBot…) plus a generic `*bot`/`*crawler`/`*spider` fallback

## Why rule order matters

Bots and Chromium-based browsers fake other browsers' tokens: Googlebot ships a `Chrome/...` token, Edge/Opera/Brave all ship one too, and Chrome ships a `Safari/...` token. Detection therefore runs in a strict order — bots first, then Edge/Opera/Vivaldi/Brave, then Chrome, then Safari — and Android is checked before Linux, iOS before macOS.

Unknown browsers and operating systems degrade to `'Unknown'` instead of throwing, so the parser is safe to run on any log line.

## Requirements

- PHP 7.4+

## License

MIT — see [LICENSE](LICENSE).
