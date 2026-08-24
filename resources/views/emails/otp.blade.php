<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Code</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: #1E3A5F;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }
        .body {
            padding: 30px 25px;
        }
        .body p {
            color: #333;
            font-size: 16px;
            line-height: 1.6;
        }
        .otp-box {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border: 1px dashed #1E3A5F;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            color: #1E3A5F;
            letter-spacing: 6px;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #888;
            font-size: 13px;
            border-top: 1px solid #e9edf2;
        }
        .footer a {
            color: #1E3A5F;
            text-decoration: none;
        }
        @media (max-width: 480px) {
            .container { margin: 20px; }
            .otp-code { font-size: 28px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 TaskFlow</h1>
        </div>
        <div class="body">
            <p>Hello <strong>{{ $userName }}</strong>,</p>
            <p>You requested to log in to your TaskFlow account. Please use the following OTP code to complete your login:</p>
            <div class="otp-box">
                <span class="otp-code">{{ $otp }}</span>
            </div>
            <p>This code is valid for <strong>10 minutes</strong>. If you didn't request this, please ignore this email.</p>
            <p>Thanks,<br>The TaskFlow Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} TaskFlow. All rights reserved.<br>
            This email was sent to you because you are registered on our platform.
        </div>
    </div>
</body>
</html>