<?php
declare(strict_types=1);

namespace App\Services;

class EmailTemplate
{
    /**
     * Wrap content in the master WhatyPie HTML Email Layout
     */
    public static function layout(string $preheader, string $title, string $contentHtml): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #020617; color: #f8fafc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
  </style>
</head>
<body style="margin: 0; padding: 0; background-color: #020617;">
  <!-- Preheader Text (Invisible) -->
  <div style="display: none; font-size: 1px; color: #020617; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
    {$preheader}
  </div>

  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #020617; table-layout: fixed;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <!-- Container -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
          
          <!-- Header Logo -->
          <tr>
            <td align="center" style="padding-bottom: 24px;">
              <a href="https://whatypie.in" target="_blank">
                <img src="https://whatypie.in/public/assets/whatypie-logo.png" alt="WhatyPie Logo" width="180" style="display: block; width: 180px; max-width: 180px; height: auto;" />
              </a>
            </td>
          </tr>

          <!-- Main Card -->
          <tr>
            <td style="padding: 32px 24px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 16px;">
              {$contentHtml}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding-top: 32px; padding-bottom: 16px; font-size: 12px; color: #64748b; line-height: 1.6;">
              <div style="padding-bottom: 12px;">
                <a href="https://www.instagram.com/whatypie/" target="_blank" style="color: #10b981; text-decoration: none; margin: 0 8px; font-weight: 600;">Instagram</a> •
                <a href="https://www.facebook.com/people/WhatyPie/61586256147959/" target="_blank" style="color: #10b981; text-decoration: none; margin: 0 8px; font-weight: 600;">Facebook</a> •
                <a href="https://whatypie.in" target="_blank" style="color: #10b981; text-decoration: none; margin: 0 8px; font-weight: 600;">Website</a>
              </div>
              <div>© 2026 Dinzin Technology Solutions Private Limited. All rights reserved.</div>
              <div style="margin-top: 4px; color: #475569;">Lohith Nagara, Nelamangala, Bengaluru 562123, KA</div>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    /**
     * Render AI Strategy Call Confirmation Email
     */
    public static function bookingConfirmation(string $fullName, array $details): string
    {
        $preheader = "Your AI Strategy Call request has been received. Schedule your preferred time slot.";
        $title = "AI Strategy Call Requested — WhatyPie";

        $detailRows = "";
        foreach ($details as $label => $value) {
            if ($value) {
                $detailRows .= <<<HTML
<tr>
  <td style="padding: 8px 12px; font-size: 13px; color: #94a3b8; width: 35%; border-bottom: 1px solid #1e293b;">{$label}</td>
  <td style="padding: 8px 12px; font-size: 13px; color: #f8fafc; font-weight: 600; border-bottom: 1px solid #1e293b;">{$value}</td>
</tr>
HTML;
            }
        }

        $content = <<<HTML
<h2 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 800; color: #ffffff;">Strategy Call Requested! 🎉</h2>
<p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
  Hi <strong style="color: #ffffff;">{$fullName}</strong>,
</p>
<p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
  Thank you for requesting an AI Strategy Call with WhatyPie. We have saved your details. Click the button below to pick your preferred 30-minute time slot on our calendar:
</p>

<!-- CTA Button -->
<div style="text-align: center; margin: 28px 0;">
  <a href="https://calendly.com/whatypie/30min?back=1&month=2026-08" target="_blank" style="background: linear-gradient(135deg, #059669 0%, #0d9488 100%); color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 14px; display: inline-block; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);">
    📅 Schedule Your Time Slot →
  </a>
</div>

<!-- Details Table -->
<div style="margin-top: 24px; background: #020617; border: 1px solid #1e293b; border-radius: 12px; padding: 16px;">
  <div style="font-size: 12px; font-weight: 700; color: #34d399; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Submitted Information</div>
  <table border="0" cellpadding="0" cellspacing="0" width="100%">
    {$detailRows}
  </table>
</div>

<p style="margin: 24px 0 0 0; font-size: 13px; line-height: 1.6; color: #94a3b8;">
  If you have any questions before the call, reply directly to this email or reach us on WhatsApp.
</p>
HTML;

        return self::layout($preheader, $title, $content);
    }

    /**
     * Render Contact Form Received Confirmation Email
     */
    public static function contactConfirmation(string $name, string $message): string
    {
        $preheader = "We have received your inquiry and will reply shortly.";
        $title = "We Received Your Message — WhatyPie";

        $escapedMsg = htmlspecialchars($message);

        $content = <<<HTML
<h2 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 800; color: #ffffff;">Thank You for Reaching Out! 👋</h2>
<p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
  Hi <strong style="color: #ffffff;">{$name}</strong>,
</p>
<p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
  We have received your message and our team will get back to you shortly.
</p>

<!-- Quote Box -->
<div style="background: #020617; border-left: 4px solid #10b981; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 13px; color: #94a3b8; font-style: italic; line-height: 1.5;">
  "{$escapedMsg}"
</div>

<p style="margin: 20px 0 0 0; font-size: 13px; line-height: 1.6; color: #94a3b8;">
  Best regards,<br>
  <strong style="color: #34d399;">WhatyPie Team</strong><br>
  <span style="font-size: 11px; color: #64748b;">Dinzin Technology Solutions Private Limited</span>
</p>
HTML;

        return self::layout($preheader, $title, $content);
    }

    /**
     * Render Admin Reply Email
     */
    public static function adminReply(string $replyMessage): string
    {
        $preheader = "Response from WhatyPie Support";
        $title = "Update on Your Inquiry — WhatyPie";

        $replyHtml = nl2br(htmlspecialchars($replyMessage));

        $content = <<<HTML
<h2 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 800; color: #ffffff;">Update from WhatyPie Support</h2>

<div style="font-size: 14px; line-height: 1.7; color: #f8fafc; margin-bottom: 24px;">
  {$replyHtml}
</div>

<p style="margin: 24px 0 0 0; font-size: 13px; line-height: 1.6; color: #94a3b8; border-t: 1px solid #1e293b; padding-top: 16px;">
  Warm regards,<br>
  <strong style="color: #34d399;">WhatyPie Support Team</strong>
</p>
HTML;

        return self::layout($preheader, $title, $content);
    }
}
