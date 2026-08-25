<?php
declare(strict_types=1);

namespace App\Services;

use Core\LocalMailer;

class Mailer
{
    /**
     * Send via LocalMailer (stores HTML copy) and PHP mail() simultaneously.
     *
     * @param string $to
     * @param string $subject
     * @param string $body      Plain-text or HTML body
     * @param string $headers   Additional headers, e.g. "From: ..."
     */
    public static function send(string $to, string $subject, string $body, string $headers = ''): void
    {
        // LocalMailer — stores a copy in mails/
        ob_start();
        try {
            (new LocalMailer())->sendEmail($to, $subject, $body, $headers);
        } catch (\Throwable) {
            // silent — don't let storage failure block delivery
        }
        ob_end_clean();

        // PHP mail() — actual delivery via server MTA
        $defaultHeaders = "From: WhatyPie Support <noreply@whatypie.in>\r\n" .
                          "MIME-Version: 1.0\r\n" .
                          "Content-Type: text/html; charset=UTF-8";
        mail($to, $subject, $body, $headers ?: $defaultHeaders);
    }
}
