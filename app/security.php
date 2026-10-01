<?php
declare(strict_types=1);

function secret(): string
{
    return (string)config('secret', 'uzman-fallback');
}

/** Signed, time-stamped form token (stateless, works with page caching). */
function form_token(): string
{
    $ts = (string)time();
    $n = bin2hex(random_bytes(6));
    return $ts . '.' . $n . '.' . hash_hmac('sha256', $ts . '.' . $n, secret());
}

function form_token_age(string $tok): int
{
    $p = explode('.', $tok);
    if (count($p) !== 3 || !hash_equals(hash_hmac('sha256', $p[0] . '.' . $p[1], secret()), $p[2])) {
        return -1;
    }
    return time() - (int)$p[0];
}

function throttle_count(string $kind, int $window): int
{
    return (int)val('SELECT COUNT(*) FROM throttle WHERE kind = ? AND ip = ? AND ts > ?', [$kind, client_ip(), time() - $window]);
}

function throttle_hit(string $kind): void
{
    q('INSERT INTO throttle(kind, ip, ts) VALUES(?,?,?)', [$kind, client_ip(), time()]);
    if (random_int(1, 40) === 1) {
        q('DELETE FROM throttle WHERE ts < ?', [time() - 86400]);
    }
}

function json_out(array $d, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
