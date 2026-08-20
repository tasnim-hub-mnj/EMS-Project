<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Invitation</title>
</head>
<body style="margin:0; background:#f4f6f8; color:#20252b; font-family:Arial, sans-serif; line-height:1.6;">
    <div style="max-width:620px; margin:32px auto; padding:32px; background:#ffffff; border:1px solid #e3e7eb; border-radius:10px;">
        <h2 style="margin-top:0; color:#243b53;">You are invited</h2>

        <p>Hello {{ $invitation->holder_name }},</p>
        <p>You have received an invitation to attend:</p>

        <div style="padding:16px; background:#f7f9fb; border-left:4px solid #2f80ed;">
            <strong style="font-size:20px;">{{ $event->name }}</strong>
            @if($event->start_time)
                <br><span>Starts: {{ $event->start_time }}</span>
            @endif
            @if($event->place)
                <br><span>Location: {{ $event->place }}</span>
            @endif
        </div>

        <p style="margin-bottom:6px;"><strong>Invitation code</strong></p>
        <p style="margin-top:0; font-size:18px; letter-spacing:1px;">{{ $invitation->qr_code }}</p>

        <p>Please keep this email and present the invitation code at check-in.</p>

        <hr style="margin:28px 0; border:0; border-top:1px solid #e3e7eb;">
        <p style="margin-bottom:0; color:#68737d; font-size:13px;">Best regards,<br>{{ config('app.name') }} Team</p>
    </div>
</body>
</html>
