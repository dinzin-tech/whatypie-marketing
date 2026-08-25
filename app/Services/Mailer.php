<?php
declare(strict_types=1);

namespace App\Services;

use Core\LocalMailer;

class Mailer
{
    /**
     * Send via LocalMailer (stores HTML copy), SMTP (if configured), and PHP mail().
     */
    public static function send(string $to, string $subject, string $body, string $headers = ''): void
    {
        // 1. LocalMailer — stores a copy in mails/ for inspection in Admin Panel
        ob_start();
        try {
            (new LocalMailer())->sendEmail($to, $subject, $body, $headers);
        } catch (\Throwable) {
            // silent — don't let storage failure block delivery
        }
        ob_end_clean();

        // 2. SMTP Delivery if configured in environment variables
        $smtpHost = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST');
        if ($smtpHost) {
            try {
                self::sendViaSmtp($to, $subject, $body);
                return;
            } catch (\Throwable $e) {
                error_log("SMTP Mail Error: " . $e->getMessage());
            }
        }

        // 3. Native PHP mail() fallback
        $defaultHeaders = "From: WhatyPie Support <noreply@whatypie.in>\r\n" .
                          "MIME-Version: 1.0\r\n" .
                          "Content-Type: text/html; charset=UTF-8";
        @mail($to, $subject, $body, $headers ?: $defaultHeaders);
    }

    private static function sendViaSmtp(string $to, string $subject, string $body): void
    {
        $host     = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST');
        $port     = (int) ($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587);
        $user     = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?: '';
        $pass     = $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: '';
        $from     = $_ENV['SMTP_FROM'] ?? getenv('SMTP_FROM') ?: 'noreply@whatypie.in';
        $fromName = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'WhatyPie Support';

        $socket = fsockopen($host, $port, $errno, $errstr, 15);
        if (!$socket) {
            throw new \RuntimeException("Could not connect to SMTP host {$host}:{$port}");
        }

        fgets($socket, 512);
        fputs($socket, "EHLO {$host}\r\n");
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) == ' ') break;
        }

        if ($port == 587) {
            fputs($socket, "STARTTLS\r\n");
            fgets($socket, 512);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO {$host}\r\n");
            while ($line = fgets($socket, 512)) {
                if (substr($line, 3, 1) == ' ') break;
            }
        }

        if ($user && $pass) {
            fputs($socket, "AUTH LOGIN\r\n");
            fgets($socket, 512);
            fputs($socket, base64_encode($user) . "\r\n");
            fgets($socket, 512);
            fputs($socket, base64_encode($pass) . "\r\n");
            fgets($socket, 512);
        }

        fputs($socket, "MAIL FROM: <{$from}>\r\n");
        fgets($socket, 512);
        fputs($socket, "RCPT TO: <{$to}>\r\n");
        fgets($socket, 512);
        fputs($socket, "DATA\r\n");
        fgets($socket, 512);

        $headers  = "From: {$fromName} <{$from}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        fgets($socket, 512);
        fputs($socket, "QUIT\r\n");
        fclose($socket);
    }
}
