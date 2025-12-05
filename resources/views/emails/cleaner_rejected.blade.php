<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application Status Update</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; margin-top: 30px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #E53E3E; padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 40px 30px; }
        .message { font-size: 16px; margin-bottom: 20px; color: #4A5568; }
        .reason-box { background-color: #FFF5F5; border-left: 4px solid #E53E3E; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .footer { background-color: #F7FAFC; padding: 20px; text-align: center; font-size: 12px; color: #718096; border-top: 1px solid #EDF2F7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Application Update</h1>
        </div>
        <div class="content">
            <p class="message">Dear {{ $cleanerName }},</p>
            
            <p class="message">Thank you for your interest in joining <strong>LEIRAD</strong>. We appreciate the time you took to apply.</p>
            
            <p class="message">After careful review of your application and submitted documents, we regret to inform you that we are unable to proceed with your application at this time.</p>
            
            <div class="reason-box">
                <p style="margin: 0; color: #742A2A;"><strong>Reason:</strong><br>{{ $reason }}</p>
            </div>
            
            <p class="message">Please do not be discouraged. You are welcome to re-apply in the future if your circumstances change or if you can address the reason mentioned above.</p>
            
            <p class="message">We wish you the best in your future endeavors.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} LEIRAD Home Services. All rights reserved.
        </div>
    </div>
</body>
</html>
