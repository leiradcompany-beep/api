<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account</title>
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
            background-color: #10b981; /* Emerald 500 */
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
            background-color: #f0fdf4; /* Emerald 50 */
            border: 2px dashed #10b981;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 800;
            color: #047857; /* Emerald 700 */
            letter-spacing: 5px;
            margin: 0;
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
            <h1>LEIRAD Services</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $name }},</div>
            <div class="message">
                Welcome to Leirad! We're excited to have you on board. To ensure the security of your account and complete your registration, please verify your email address.
            </div>
            
            <div class="otp-box">
                <div style="font-size: 12px; text-transform: uppercase; color: #059669; margin-bottom: 5px; font-weight: 600;">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="message">
                This code will expire in 10 minutes. If you didn't create an account with Leirad, you can safely ignore this email.
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Leirad Services. All rights reserved.</p>
            <p>This is an automated message, please do not reply.</p>
        </div>
    </div>
</body>
</html>
