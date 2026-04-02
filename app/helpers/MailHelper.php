<?php

class MailHelper
{
    private string $driver;
    private string $fromAddress;
    private string $fromName;
    private string $logPath;
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $smtpEncryption;
    private int $smtpTimeout;

    public function __construct()
    {
        $credentialsFile = BASE_PATH . '/config/mail.credentials.php';
        if (is_file($credentialsFile)) {
            require_once $credentialsFile;
        }

        $this->driver = strtolower(app_normalize_single_line((string)(defined('MAIL_DRIVER') ? MAIL_DRIVER : (getenv('MAIL_DRIVER') ?: 'log')), 20));
        $this->fromAddress = app_normalize_single_line((string)(defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : (getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@example.com')), 160);
        $this->fromName = app_normalize_single_line((string)(defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (getenv('MAIL_FROM_NAME') ?: 'Texsico')), 120);
        $this->logPath = (string)(defined('MAIL_LOG_PATH') ? MAIL_LOG_PATH : (getenv('MAIL_LOG_PATH') ?: (BASE_PATH . '/storage/mail.log')));

        $this->smtpHost = app_normalize_single_line((string)(defined('MAIL_SMTP_HOST') ? MAIL_SMTP_HOST : (getenv('MAIL_SMTP_HOST') ?: '')), 255);
        $this->smtpPort = (int)(defined('MAIL_SMTP_PORT') ? MAIL_SMTP_PORT : (getenv('MAIL_SMTP_PORT') ?: 587));
        $this->smtpUsername = app_normalize_single_line((string)(defined('MAIL_SMTP_USERNAME') ? MAIL_SMTP_USERNAME : (getenv('MAIL_SMTP_USERNAME') ?: '')), 255);
        $this->smtpPassword = (string)(defined('MAIL_SMTP_PASSWORD') ? MAIL_SMTP_PASSWORD : (getenv('MAIL_SMTP_PASSWORD') ?: ''));
        $this->smtpEncryption = strtolower(app_normalize_single_line((string)(defined('MAIL_SMTP_ENCRYPTION') ? MAIL_SMTP_ENCRYPTION : (getenv('MAIL_SMTP_ENCRYPTION') ?: 'tls')), 10));
        $this->smtpTimeout = max(3, min(60, (int)(defined('MAIL_SMTP_TIMEOUT') ? MAIL_SMTP_TIMEOUT : (getenv('MAIL_SMTP_TIMEOUT') ?: 15))));
    }

    public function sendPasswordReset(string $to, string $fullName, string $resetLink, int $expiresMinutes = 20): bool
    {
        $safeName = trim($fullName) !== '' ? $fullName : 'there';
        $subject = 'Reset your Texsico password';
        $preview = 'Use your secure one-time link to reset your Texsico password.';
        $logoUrl = BASE_URL . 'apple-touch-icon.png';
        $escapedName = htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8');
        $escapedLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $escapedLogo = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        $html = '<!DOCTYPE html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<meta name="color-scheme" content="light only">'
            . '<meta name="supported-color-schemes" content="light only">'
            . '<title>Reset your Texsico password</title>'
            . '</head>'
            . '<body style="margin:0;padding:0;background:#eef3fb;font-family:Inter,Arial,Helvetica,sans-serif;color:#162033;">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">'
            . htmlspecialchars($preview, ENT_QUOTES, 'UTF-8')
            . str_repeat('&nbsp;', 12)
            . '</div>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;margin:0;padding:0;background:#eef3fb;">'
            . '<tr><td align="center" style="padding:18px 10px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;width:100%;">'
            . '<tr><td style="background:linear-gradient(135deg,#0f1f3d 0%,#15294b 48%,#1b3258 100%);border-radius:22px;padding:20px 16px 14px;box-shadow:0 18px 50px rgba(12,24,48,0.18);overflow:hidden;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td style="padding-bottom:14px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
            . '<td style="vertical-align:middle;"><img src="' . $escapedLogo . '" alt="Texsico logo" width="40" height="40" style="display:block;width:40px;height:40px;border-radius:12px;border:0;outline:none;text-decoration:none;"></td>'
            . '<td style="padding-left:10px;vertical-align:middle;font-size:20px;line-height:1.1;font-weight:800;color:#ffffff;letter-spacing:-0.02em;font-family:Plus Jakarta Sans,Inter,Arial,sans-serif;">Tex<span style="color:#9ab4ff;">s</span>ico</td>'
            . '</tr></table>'
            . '</td></tr></table>'
            . '<div style="display:inline-block;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.10);border-radius:999px;padding:7px 12px;color:#d9e7ff;font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;">Password recovery</div>'
            . '<h1 style="margin:16px 0 10px;font-size:28px;line-height:1.15;font-weight:800;letter-spacing:-0.03em;color:#ffffff;font-family:Plus Jakarta Sans,Inter,Arial,sans-serif;">Reset your password</h1>'
            . '<p style="margin:0;font-size:15px;line-height:1.65;color:#b8c8e6;">Securely recover your account with a one-time link.</p>'
            . '<div style="height:16px;line-height:16px;font-size:16px;">&nbsp;</div>'
            . '<div style="background:rgba(255,255,255,0.97);border:1px solid rgba(20,34,60,0.08);border-radius:18px;padding:20px 16px 18px;">'
            . '<p style="margin:0 0 12px;font-size:16px;line-height:1.65;color:#22304a;">Hi <strong>' . $escapedName . '</strong>,</p>'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.75;color:#4b5a76;">We received a request to reset the password for your Texsico account. Use the button below to choose a new password.</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0 18px;"><tr><td align="center">'
            . '<a href="' . $escapedLink . '" style="display:block;width:100%;max-width:100%;box-sizing:border-box;background:linear-gradient(135deg,#4fc3ff 0%,#7b78ff 100%);color:#ffffff;text-decoration:none;font-size:16px;font-weight:800;letter-spacing:0.01em;border-radius:999px;padding:15px 20px;box-shadow:0 10px 24px rgba(91,117,255,0.28);font-family:Inter,Arial,Helvetica,sans-serif;text-align:center;">Reset Password</a>'
            . '</td></tr></table>'
            . '<div style="background:#f5f8ff;border:1px solid #dfe8fb;border-radius:14px;padding:14px 14px;margin:0 0 16px;">'
            . '<p style="margin:0 0 6px;font-size:14px;line-height:1.5;color:#22304a;font-weight:700;">Security note</p>'
            . '<p style="margin:0;font-size:13px;line-height:1.7;color:#5f6f8d;">This link expires in <strong>' . (int)$expiresMinutes . ' minutes</strong> and can only be used once.</p>'
            . '</div>'
            . '<p style="margin:0 0 8px;font-size:13px;line-height:1.7;color:#5f6f8d;">If the button does not work, copy and paste this link into your browser:</p>'
            . '<p style="margin:0 0 18px;word-break:break-word;"><a href="' . $escapedLink . '" style="color:#355cff;text-decoration:none;font-size:12px;line-height:1.7;">' . $escapedLink . '</a></p>'
            . '<p style="margin:0;font-size:13px;line-height:1.75;color:#5f6f8d;">If you did not request this, you can safely ignore this email.</p>'
            . '</div>'
            . '<div style="height:14px;line-height:14px;font-size:14px;">&nbsp;</div>'
            . '<div style="padding:0 4px 2px;">'
            . '<p style="margin:0;font-size:11px;line-height:1.7;color:#a9b9d6;text-align:center;">This email was sent by Texsico • noreply@texsico.xyz • &copy; ' . $year . ' Texsico</p>'
            . '</div>'
            . '</td></tr></table>'
            . '</td></tr></table>'
            . '</body>'
            . '</html>';

        $text = "Hi {$safeName},

We received a request to reset your Texsico password.

"
            . "Reset link: {$resetLink}

"
            . "This link expires in {$expiresMinutes} minutes and can only be used once.

"
            . "If you did not request this, you can ignore this email.
";

        return $this->send($to, $subject, $html, $text);
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $to = app_normalize_single_line($to, 160);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return match ($this->driver) {
            'smtp' => $this->sendViaSmtp($to, $subject, $htmlBody, $textBody),
            'mail' => $this->sendViaMail($to, $subject, $htmlBody, $textBody),
            'log' => $this->sendViaLog($to, $subject, $htmlBody, $textBody),
            default => $this->sendViaLog($to, $subject, $htmlBody, $textBody, 'unknown driver, logged email instead'),
        };
    }

    private function sendViaMail(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        $boundary = 'b_' . bin2hex(random_bytes(12));
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $this->formatFromHeader(),
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
            . ($textBody !== '' ? $textBody : strip_tags($htmlBody)) . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
            . $htmlBody . "\r\n"
            . '--' . $boundary . '--';

        $sent = @mail($to, $this->encodeHeader($subject), $body, implode("\r\n", $headers));
        if (!$sent) {
            $this->sendViaLog($to, $subject, $htmlBody, $textBody, 'mail() failed, wrote fallback log');
        }
        return $sent;
    }

    private function sendViaSmtp(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        if ($this->smtpHost === '') {
            $this->sendViaLog($to, $subject, $htmlBody, $textBody, 'smtp host missing, wrote fallback log');
            return false;
        }

        $transport = $this->smtpEncryption === 'ssl' ? 'ssl://' : 'tcp://';
        $socket = null;

        try {
            $errno = 0;
            $errstr = '';
            $socket = @stream_socket_client(
                $transport . $this->smtpHost . ':' . $this->smtpPort,
                $errno,
                $errstr,
                $this->smtpTimeout,
                STREAM_CLIENT_CONNECT
            );

            if (!is_resource($socket)) {
                throw new RuntimeException('SMTP connect failed: ' . ($errstr !== '' ? $errstr : ('error ' . $errno)));
            }

            stream_set_timeout($socket, $this->smtpTimeout);

            $this->smtpExpect($socket, [220]);
            $ehloResponse = $this->smtpCommand($socket, 'EHLO ' . $this->smtpClientName(), [250]);

            if ($this->smtpEncryption === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', [220]);
                $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoEnabled !== true) {
                    throw new RuntimeException('SMTP STARTTLS failed');
                }
                $ehloResponse = $this->smtpCommand($socket, 'EHLO ' . $this->smtpClientName(), [250]);
            }

            if ($this->smtpUsername !== '' || $this->smtpPassword !== '') {
                $this->smtpAuthenticate($socket, $ehloResponse);
            }

            $this->smtpCommand($socket, 'MAIL FROM:<' . $this->fromAddress . '>', [250]);
            $this->smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);

            $message = $this->buildSmtpMessage($to, $subject, $htmlBody, $textBody);
            $this->smtpWrite($socket, $this->dotStuff($message) . "\r\n.\r\n");
            $this->smtpExpect($socket, [250]);

            $this->smtpCommand($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            if (is_resource($socket)) {
                @fwrite($socket, "QUIT\r\n");
                @fclose($socket);
            }
            $this->sendViaLog($to, $subject, $htmlBody, $textBody, 'smtp failed: ' . $e->getMessage());
            return false;
        }
    }

    private function smtpAuthenticate($socket, string $ehloResponse = ''): void
    {
        if (stripos($ehloResponse, 'AUTH PLAIN') !== false) {
            $token = base64_encode("\0" . $this->smtpUsername . "\0" . $this->smtpPassword);
            $this->smtpCommand($socket, 'AUTH PLAIN ' . $token, [235]);
            return;
        }

        $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
        $this->smtpCommand($socket, base64_encode($this->smtpUsername), [334]);
        $this->smtpCommand($socket, base64_encode($this->smtpPassword), [235]);
    }

    private function buildSmtpMessage(string $to, string $subject, string $htmlBody, string $textBody): string
    {
        $boundary = 'b_' . bin2hex(random_bytes(12));
        $plainBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
        $plainBody = str_replace(["\r\n", "\r"], "\n", $plainBody);
        $htmlBody = str_replace(["\r\n", "\r"], "\n", $htmlBody);

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->formatFromHeader(),
            'To: <' . $to . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $parts = [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($plainBody), 76, "\r\n")),
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($htmlBody), 76, "\r\n")),
            '--' . $boundary . '--',
            '',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts);
    }

    private function smtpCommand($socket, string $command, array $expectedCodes): string
    {
        $this->smtpWrite($socket, $command . "\r\n");
        return $this->smtpExpect($socket, $expectedCodes);
    }

    private function smtpExpect($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('SMTP server sent an empty response');
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP unexpected response: ' . trim($response));
        }

        return $response;
    }

    private function smtpWrite($socket, string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = @fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('SMTP write failed');
            }
            $offset += $written;
        }
    }

    private function dotStuff(string $message): string
    {
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $lines = explode("\n", $message);
        foreach ($lines as &$line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
        }
        unset($line);
        return implode("\r\n", $lines);
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function smtpClientName(): string
    {
        $host = (string)($_SERVER['SERVER_NAME'] ?? gethostname() ?: 'localhost.localdomain');
        $host = strtolower(app_normalize_single_line($host, 120));
        $host = preg_replace('/[^a-z0-9.-]/', '', $host) ?? 'localhost.localdomain';
        return $host !== '' ? $host : 'localhost.localdomain';
    }

    private function sendViaLog(string $to, string $subject, string $htmlBody, string $textBody, string $note = 'logged email'): bool
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $entry = "==== " . date('c') . " ====\n"
            . "Driver: {$this->driver}\n"
            . "Note: {$note}\n"
            . "To: {$to}\n"
            . "From: {$this->formatFromHeader()}\n"
            . "Subject: {$subject}\n\n"
            . ($textBody !== '' ? $textBody : strip_tags($htmlBody))
            . "\n\n";

        return file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX) !== false;
    }

    private function formatFromHeader(): string
    {
        $safeName = addcslashes($this->fromName, '"\\');
        return '"' . $safeName . '" <' . $this->fromAddress . '>';
    }
}
