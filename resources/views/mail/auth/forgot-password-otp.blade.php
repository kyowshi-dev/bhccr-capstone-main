<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code</title>
</head>
<body style="margin:0; padding:0; background:#f0f4f8; font-family: Poppins, system-ui, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(26,31,28,0.08);">
                    <tr>
                        <td style="background:#0d4a3c; padding:20px 28px;">
                            <p style="margin:0; color:#ffffff; font-size:15px; font-weight:600;">
                                Barangay Health Center Consultation and Referral System
                            </p>
                            <p style="margin:2px 0 0; color:rgba(255,255,255,0.75); font-size:12px;">Sta. Ana Health Center</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 12px; color:#1a1f1c; font-size:18px;">Password reset request</h1>
                            <p style="margin:0 0 16px; color:#5c6560; font-size:13px; line-height:1.6;">
                                Use the code below to verify your identity and set a new password.
                                The code expires in <strong>{{ $expiresInMinutes }} minutes</strong>.
                            </p>
                            <p style="margin:0 0 16px; padding:16px; background:rgba(13,74,60,0.08); border-radius:8px; text-align:center; font-size:28px; font-weight:700; letter-spacing:8px; color:#0d4a3c;">
                                {{ $otp }}
                            </p>
                            <p style="margin:0; color:#5c6560; font-size:12px; line-height:1.6;">
                                If you did not request a password reset, you can ignore this email. Do not share this code with anyone.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 28px; background:#f7f9f8; border-top:1px solid rgba(26,31,28,0.08);">
                            <p style="margin:0; color:#5c6560; font-size:11px;">
                                &copy; {{ date('Y') }} Barangay Sta. Ana Health Center. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
