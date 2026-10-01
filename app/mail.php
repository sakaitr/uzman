<?php
declare(strict_types=1);

/** Minimal SMTP client (SSL/TLS/STARTTLS + AUTH LOGIN). Falls back to PHP mail() when no SMTP host is configured. */
function mail_send(string $to, string $subject, string $text, string $replyTo = ''): array
{
    $from = trim((string)setting('mail_from')) ?: (trim((string)setting('smtp_user')) ?: 'noreply@' . preg_replace('/^www\./', '', explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]));
    $fromName = '=?UTF-8?B?' . base64_encode((string)setting('site_name', 'Uzman Cosmetic')) . '?=';
    $headers = ['From: ' . $fromName . ' <' . $from . '>', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: 8bit', 'X-Mailer: UzmanCMS'];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $host = trim((string)setting('smtp_host'));
    if ($host === '') {
        $ok = @mail($to, $subj, $text, implode("\r\n", $headers), '-f' . $from);
        return [$ok, $ok ? '' : 'mail() başarısız'];
    }
    try {
        $port = (int)setting('smtp_port', '587');
        $secure = (string)setting('smtp_secure', 'tls');
        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $en, $es, 12, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new RuntimeException("bağlanılamadı: $es");
        }
        stream_set_timeout($fp, 12);
        $read = function () use ($fp): string {
            $out = '';
            while (($l = fgets($fp, 515)) !== false) {
                $out .= $l;
                if (strlen($l) < 4 || $l[3] === ' ') {
                    break;
                }
            }
            return $out;
        };
        $cmd = function (string $c, array $expect) use ($fp, $read): string {
            fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!in_array((int)substr($r, 0, 3), $expect, true)) {
                throw new RuntimeException('SMTP: ' . trim($r));
            }
            return $r;
        };
        $read();
        $ehlo = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0];
        $cmd('EHLO ' . $ehlo, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS başlatılamadı');
            }
            $cmd('EHLO ' . $ehlo, [250]);
        }
        $user = (string)setting('smtp_user');
        if ($user !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode($user), [334]);
            $cmd(base64_encode((string)setting('smtp_pass')), [235]);
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);
        $msg = implode("\r\n", array_merge($headers, ['To: <' . $to . '>', 'Subject: ' . $subj, 'Date: ' . date('r'), '', preg_replace('/^\./m', '..', str_replace("\n", "\r\n", str_replace("\r\n", "\n", $text)))]));
        fwrite($fp, $msg . "\r\n.\r\n");
        $r = $read();
        if ((int)substr($r, 0, 3) !== 250) {
            throw new RuntimeException('SMTP DATA: ' . trim($r));
        }
        $cmd('QUIT', [221]);
        fclose($fp);
        return [true, ''];
    } catch (Throwable $e) {
        return [false, $e->getMessage()];
    }
}
