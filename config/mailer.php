<?php
// Pengirim email sederhana lewat SMTP (misalnya Gmail), tanpa library tambahan.
// Diatur lewat config/config.local.php: smtp_host, smtp_port, smtp_secure, smtp_user, smtp_pass, mail_from.

function smtp_configured(array $config): bool {
    return !empty($config['smtp_host']) && !empty($config['smtp_user']) && !empty($config['smtp_pass']);
}

function send_mail(array $config, string $to, string $toName, string $subject, string $body, ?string &$error = null): bool {
    $host = $config['smtp_host'];
    $port = (int)($config['smtp_port'] ?? 465);
    $secure = $config['smtp_secure'] ?? ($port === 465 ? 'ssl' : 'tls');
    $from = $config['mail_from'] ?? $config['smtp_user'];
    $fromName = $config['mail_from_name'] ?? 'TaskMate';

    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $sock = @stream_socket_client($remote, $errno, $errstr, 15);
    if (!$sock) { $error = "Tidak bisa terhubung ke $host:$port ($errstr)"; return false; }
    stream_set_timeout($sock, 15);

    $read = function () use ($sock): string {
        $data = '';
        while (($line = fgets($sock, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function (string $line, array $ok) use ($sock, $read, &$error): bool {
        if ($line !== '') fwrite($sock, $line . "\r\n");
        $reply = $read();
        if (!in_array((int)substr($reply, 0, 3), $ok, true)) {
            $error = 'SMTP: ' . trim($reply);
            return false;
        }
        return true;
    };

    $helo = 'EHLO ' . (gethostname() ?: 'localhost');
    $ok = $cmd('', [220]) && $cmd($helo, [250]);
    if ($ok && $secure === 'tls') {
        $ok = $cmd('STARTTLS', [220])
            && stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
            && $cmd($helo, [250]);
    }
    $ok = $ok
        && $cmd('AUTH LOGIN', [334])
        && $cmd(base64_encode($config['smtp_user']), [334])
        && $cmd(base64_encode($config['smtp_pass']), [235])
        && $cmd('MAIL FROM:<' . $from . '>', [250])
        && $cmd('RCPT TO:<' . $to . '>', [250, 251])
        && $cmd('DATA', [354]);

    if ($ok) {
        $encode = fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $encode($fromName) . ' <' . $from . '>',
            'To: ' . $encode($toName) . ' <' . $to . '>',
            'Subject: ' . $encode($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
        $ok = $cmd($message . "\r\n.", [250]);
    }
    if ($sock) { @fwrite($sock, "QUIT\r\n"); fclose($sock); }
    return $ok;
}
