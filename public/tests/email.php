<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.4;
            color: #2d3748;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 28px;
            margin-bottom: 8px;
        }
        h2 {
            font-size: 22px;
            font-weight: 600;
            margin: 0 0 8px;
            color: #1a202c;
        }
        .code-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-family: 'Courier New', monospace;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 4px;
            color: #3182ce;
        }
        .note {
            font-size: 13px;
            color: #718096;
            text-align: center;
            margin: 16px 0;
            padding: 12px;
            background: #ebf8ff;
            border-radius: 6px;
        }
        .footer {
            font-size: 12px;
            color: #a0aec0;
            text-align: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <div class='logo'>✉️</div>
            <h2>Hi {$toName},</h2>
            <p style='margin:0; color:#4a5568;'>Verify your email to get started</p>
        </div>
        
        <div class='code-box'>
            <div style='font-size:14px; color:#718096; margin-bottom:8px;'>Your verification code</div>
            <div class='code'>{$verificationCode}</div>
            <div style='font-size:12px; color:#a0aec0; margin-top:12px;'>Valid for 24 hours</div>
        </div>
        
        <div class='note'>
            <strong>🔒 Security tip:</strong> Never share this code with anyone
        </div>
        
        <p style='font-size:14px; color:#718096; text-align:center; margin:16px 0 0;'>
            Didn't request this? You can safely ignore this email.
        </p>
        
        <div class='footer'>
            © 2024 Your App Name. All rights reserved.
        </div>
    </div>
</body>
</html>