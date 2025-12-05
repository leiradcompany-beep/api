<!DOCTYPE html>
<html>
<head>
    <title>Welcome to LEIRAD</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #0EA5E9;">Welcome to LEIRAD!</h2>
        
        <p>Dear {{ $user->name }},</p>
        
        <p>A booking has been created for you by our cleaner. An account has been automatically created so you can manage your bookings and view your history.</p>
        
        <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0EA5E9; margin: 20px 0;">
            <h3 style="margin-top: 0;">Your Login Credentials</h3>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Password:</strong> {{ $password }}</p>
        </div>
        
        <p><strong>Booking Details:</strong></p>
        <ul>
            <li><strong>Date:</strong> {{ \Carbon\Carbon::parse($booking->date)->format('M d, Y') }}</li>
            <li><strong>Time:</strong> {{ \Carbon\Carbon::parse($booking->time)->format('h:i A') }}</li>
            <li><strong>Address:</strong> {{ $booking->address }}</li>
        </ul>

        <p>Please log in to change your password and view your full booking details.</p>
        
        <p>Thank you for choosing LEIRAD!</p>
    </div>
</body>
</html>
