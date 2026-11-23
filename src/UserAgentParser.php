<?php

declare(strict_types=1);

namespace Azanumer\UserAgent;

/**
 * Dependency-free user-agent parser: browser, version, operating system,
 * device type and bot detection from a single UA string.
 *
 *     $ua = UserAgentParser::parse($_SERVER['HTTP_USER_AGENT'] ?? '');
 *     $ua->browser;      // 'Chrome'
 *     $ua->version;      // '131.0.6778.87'
 *     $ua->os;           // 'Windows'
 *     $ua->osVersion;    // '10'
 *     $ua->device;       // 'desktop' | 'mobile' | 'tablet'
 *     $ua->isBot();      // false
 *
 * Parsing is rule-based (ordered regexes) with no external database, so it
 * runs anywhere PHP runs. Unknown browsers/OSes degrade to 'Unknown'
 * instead of throwing.
 *
 * PHP 7.4+.
 */
class UserAgentParser
{
    /** @var string Browser name, e.g. Chrome, Firefox, Safari, Edge. */
    public $browser = 'Unknown';

    /** @var string Browser version, e.g. 131.0.6778.87 ('' when unknown). */
    public $version = '';

    /** @var string Operating system, e.g. Windows, macOS, Android, iOS, Linux. */
    public $os = 'Unknown';

    /** @var string OS version, e.g. 10, 14.6, 11 ('' when unknown). */
    public $osVersion = '';

    /** @var string Device class: desktop, mobile or tablet. */
    public $device = 'desktop';

    /** @var string Bot name when isBot() is true, e.g. Googlebot ('' otherwise). */
    public $bot = '';

    /** @var string The raw user-agent string that was parsed. */
    public $raw = '';

    /**
     * Parse a user-agent string into a UserAgentParser result object.
     */
    public static function parse(string $userAgent): self
    {
        $result      = new self();
        $result->raw = $userAgent;
        $ua          = trim($userAgent);

        if ('' === $ua) {
            return $result;
        }

        $result->detectBot($ua);
        $result->detectOs($ua);
        $result->detectBrowser($ua);
        $result->detectDevice($ua);

        return $result;
    }

    /** True when the UA belongs to a known crawler/bot. */
    public function isBot(): bool
    {
        return '' !== $this->bot;
    }

    /** True when the device is a phone or a tablet. */
    public function isMobile(): bool
    {
        return 'desktop' !== $this->device;
    }

    /** One-line summary, e.g. "Chrome 131 on Windows 10 (desktop)". */
    public function __toString(): string
    {
        if ($this->isBot()) {
            return $this->bot . ' (bot)';
        }

        $summary = $this->browser;
        if ('' !== $this->version) {
            $summary .= ' ' . $this->version;
        }
        $summary .= ' on ' . $this->os;
        if ('' !== $this->osVersion) {
            $summary .= ' ' . $this->osVersion;
        }
        return $summary . ' (' . $this->device . ')';
    }

    /**
     * Bot detection runs first: bots often fake a browser token
     * (Googlebot ships "Chrome/..." in its UA) and must win.
     */
    private function detectBot(string $ua): void
    {
        $bots = array(
            'Googlebot'      => 'Googlebot',
            'Bingbot'        => 'bingbot',
            'Slurp'          => 'Slurp',
            'DuckDuckBot'    => 'DuckDuckBot',
            'Baiduspider'    => 'Baiduspider',
            'YandexBot'      => 'YandexBot',
            'Sogou'          => 'Sogou',
            'Exabot'         => 'Exabot',
            'facebot'        => 'facebot',
            'Twitterbot'     => 'Twitterbot',
            'LinkedInBot'    => 'LinkedInBot',
            'AhrefsBot'      => 'AhrefsBot',
            'SemrushBot'     => 'SemrushBot',
            'MJ12bot'        => 'MJ12bot',
            'DotBot'         => 'DotBot',
            'Applebot'       => 'Applebot',
            'GPTBot'         => 'GPTBot',
            'ChatGPT-User'   => 'ChatGPT-User',
            'ClaudeBot'      => 'ClaudeBot',
            'PerplexityBot'  => 'PerplexityBot',
            'CCBot'          => 'CCBot',
            'Bytespider'     => 'Bytespider',
            'facebookexternalhit' => 'facebookexternalhit',
            'WhatsApp'       => 'WhatsApp',
            'TelegramBot'    => 'TelegramBot',
        );

        foreach ($bots as $name => $token) {
            if (false !== stripos($ua, $token)) {
                $this->bot    = $name;
                $this->device = 'desktop';
                return;
            }
        }

        // Generic fallback: any UA advertising itself as a bot/crawler/spider.
        if (preg_match('/([A-Za-z0-9_.-]+)[\/ ][\d.]*[^;]*\b(bot|crawler|spider)\b/i', $ua, $m)) {
            $this->bot    = $m[1];
            $this->device = 'desktop';
        }
    }

    /** Operating system + version. Order matters: Android before Linux, iOS before macOS. */
    private function detectOs(string $ua): void
    {
        $rules = array(
            // [regex, os name, version group]
            array('/Windows NT 10\.0/', 'Windows', '10'),
            array('/Windows NT 6\.3/', 'Windows', '8.1'),
            array('/Windows NT 6\.2/', 'Windows', '8'),
            array('/Windows NT 6\.1/', 'Windows', '7'),
            array('/Windows NT 6\.0/', 'Windows', 'Vista'),
            array('/Windows NT 5\.1/', 'Windows', 'XP'),
            array('/Android ([\d.]+)/', 'Android', null),
            array('/iPhone OS ([\d_]+)/', 'iOS', null),
            array('/iPad; CPU OS ([\d_]+)/', 'iOS', null),
            array('/CPU OS ([\d_]+) like Mac OS X/', 'iOS', null),
            array('/Mac OS X ([\d_]+)/', 'macOS', null),
            array('/CrOS [\w]+ ([\d.]+)/', 'Chrome OS', null),
            array('/Ubuntu/', 'Linux', 'Ubuntu'),
            array('/Linux/', 'Linux', ''),
        );

        foreach ($rules as $rule) {
            list($pattern, $name, $version) = $rule;
            if (preg_match($pattern, $ua, $m)) {
                $this->os = $name;
                if (null === $version) {
                    $this->osVersion = str_replace('_', '.', $m[1] ?? '');
                } else {
                    $this->osVersion = $version;
                }
                return;
            }
        }
    }

    /**
     * Browser + version. Order matters: Edge/Opera/Brave/Vivaldi all ship a
     * Chrome token, so they must be checked before plain Chrome; Chrome
     * before Safari (Chrome ships "Safari/..." too).
     */
    private function detectBrowser(string $ua): void
    {
        $rules = array(
            array('/Edg\/([\d.]+)/', 'Edge'),
            array('/EdgA\/([\d.]+)/', 'Edge'),
            array('/EdgiOS\/([\d.]+)/', 'Edge'),
            array('/OPR\/([\d.]+)/', 'Opera'),
            array('/Opera Mini\/([\d.]+)/', 'Opera Mini'),
            array('/Vivaldi\/([\d.]+)/', 'Vivaldi'),
            array('/Brave\/([\d.]+)/', 'Brave'),
            array('/SamsungBrowser\/([\d.]+)/', 'Samsung Internet'),
            array('/YaBrowser\/([\d.]+)/', 'Yandex Browser'),
            array('/Firefox\/([\d.]+)/', 'Firefox'),
            array('/FxiOS\/([\d.]+)/', 'Firefox'),
            array('/CriOS\/([\d.]+)/', 'Chrome'),
            array('/Chrome\/([\d.]+)/', 'Chrome'),
            array('/Version\/([\d.]+).*Safari\//', 'Safari'),
            array('/Safari\/([\d.]+)/', 'Safari'),
            array('/MSIE ([\d.]+)/', 'Internet Explorer'),
            array('/Trident\/.*rv:([\d.]+)/', 'Internet Explorer'),
        );

        foreach ($rules as $rule) {
            list($pattern, $name) = $rule;
            if (preg_match($pattern, $ua, $m)) {
                $this->browser = $name;
                $this->version = $m[1] ?? '';
                return;
            }
        }
    }

    /** Device class: tablet (iPad / Android tablet), mobile, or desktop. */
    private function detectDevice(string $ua): void
    {
        if ($this->isBot()) {
            return;
        }

        if (false !== stripos($ua, 'iPad')) {
            $this->device = 'tablet';
            return;
        }

        // Android without "Mobile" in the UA is a tablet.
        if (false !== stripos($ua, 'Android') && false === stripos($ua, 'Mobile')) {
            $this->device = 'tablet';
            return;
        }

        if (preg_match('/Mobile|iPhone|iPod|Android|Windows Phone|BlackBerry|webOS/', $ua)) {
            $this->device = 'mobile';
            return;
        }

        $this->device = 'desktop';
    }
}
