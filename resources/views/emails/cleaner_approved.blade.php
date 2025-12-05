<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to LEIRAD!</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; margin-top: 30px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #2C7A7B; padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 40px 30px; }
        .message { font-size: 16px; margin-bottom: 20px; color: #4A5568; }
        .highlight-box { background-color: #E6FFFA; border-left: 4px solid #319795; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .btn { display: inline-block; background-color: #2C7A7B; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 20px; }
        .footer { background-color: #F7FAFC; padding: 20px; text-align: center; font-size: 12px; color: #718096; border-top: 1px solid #EDF2F7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to the Team!</h1>
        </div>
        <div class="content">
            <p class="message">Dear {{ $cleanerName }},</p>
            
            <p class="message">We are thrilled to inform you that your application to join <strong>LEIRAD</strong> as a Cleaning Specialist has been <strong>ACCEPTED</strong>!</p>
            
            <div class="highlight-box">
                <p style="margin: 0; color: #234E52;"><strong>Congratulations!</strong> Your profile is now active, and you can start accepting bookings immediately.</p>
            </div>
            
            <p class="message">We were impressed by your qualifications and experience, and we believe you will be a valuable asset to our team.</p>
            
            <p class="message">You can now log in to your dashboard to view your schedule, manage your profile, and start your journey with us.</p>
            
            <div style="text-align: center;">
                <a href="#" class="btn">Go to My Dashboard</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} LEIRAD Home Services. All rights reserved.
        </div>
    </div>
</body>
</html>
