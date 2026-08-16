<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code</title>
</head>
<body style="margin:0; padding:0; background:#f0f4f2; font-family: Poppins, 'Segoe UI', system-ui, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f2; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid rgba(13,74,60,0.08);">
                    <tr>
                        <td style="background:#0d4a3c; padding:24px 28px;">
                            <p style="margin:0; color:#ffffff; font-size:15px; font-weight:600; letter-spacing:0.2px;">
                                Barangay Health Center Information System
                            </p>
                            <p style="margin:3px 0 0; color:rgba(255,255,255,0.75); font-size:12px;">Sta. Ana Health Center</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px 28px;">
                            <h1 style="margin:0 0 10px; color:#1a1f1c; font-size:18px; font-weight:600;">Password reset request</h1>
                            <p style="margin:0 0 20px; color:#5c6560; font-size:13px; line-height:1.7;">
                                Hi,<br><br>
                                We received a request to reset your password. Use the 6-digit code below to verify your identity and set a new password.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="padding:18px 16px; background:#eef6f3; border:1px dashed #0d4a3c; border-radius:10px; text-align:center;">
                                        <p style="margin:0 0 6px; color:#0d4a3c; font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase;">Your verification code</p>
                                        <p style="margin:0; color:#0d4a3c; font-size:30px; font-weight:700; letter-spacing:10px; line-height:1.2;">
                                            {{ $otp }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 8px; color:#5c6560; font-size:13px; line-height:1.7;">
                                This code expires in <strong style="color:#1a1f1c;">{{ $expiresInMinutes }} minutes</strong>.
                            </p>
                            <p style="margin:0; color:#8a938d; font-size:12px; line-height:1.7;">
                                If you did not request a password reset, you can safely ignore this email. Do not share this code with anyone.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 28px; background:#f7f9f8; border-top:1px solid rgba(26,31,28,0.08);">
                            <p style="margin:0; color:#8a938d; font-size:11px; line-height:1.6;">
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
