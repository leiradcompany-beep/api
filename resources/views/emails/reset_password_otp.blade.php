<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .header {
            background-color: #3b82f6; /* Blue 500 */
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            color: #334155;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1f2937;
        }
        .message {
            margin-bottom: 30px;
            font-size: 16px;
        }
        .otp-box {
            background-color: #eff6ff; /* Blue 50 */
            border: 2px dashed #3b82f6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 800;
            color: #1d4ed8; /* Blue 700 */
            letter-spacing: 5px;
            margin: 0;
        }
        .warning {
            background-color: #fff7ed; /* Orange 50 */
            border-left: 4px solid #f97316; /* Orange 500 */
            padding: 15px;
            font-size: 14px;
            color: #9a3412;
            margin-top: 20px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello,</div>
            <div class="message">
                We received a request to reset the password for your Leirad account. Use the verification code below to proceed with setting a new password.
            </div>
            
            <div class="otp-box">
                <div style="font-size: 12px; text-transform: uppercase; color: #1e40af; margin-bottom: 5px; font-weight: 600;">Secure Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="message">
                This code is valid for 10 minutes. Do not share this code with anyone.
            </div>

            <div class="warning">
                <strong>Did not request this?</strong><br>
                If you did not request a password reset, please ignore this email. Your account remains secure.
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Leirad Services. All rights reserved.</p>
            <p>Secure Account Notification</p>
        </div>
    </div>
</body>
</html>
