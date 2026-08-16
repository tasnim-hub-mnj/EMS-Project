<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application Accepted</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">

    <h2 style="color:#333; text-align:center;">Your Job Application Has Been Accepted</h2>

    <p>
        Hello {{ $staff_name }},<br>
        We are pleased to inform you that your job application for the
        <strong>{{ $exhibition_name }}</strong> Exhibition has been accepted.
    </p>

    <p>
        Please click the button below to claim your role and begin your assigned tasks.
    </p>

    <p style="margin: 30px 0; text-align:center;">
        <a href="{{$portal_url}}"
           style="background-color:#007bff; color:white; padding:12px 20px;
                  text-decoration:none; border-radius:6px; font-size:16px;">
            Claim Your Role
        </a>
    </p>

    <p>
        If the button does not work, you can use the link below:
        <br>
        <span style="color:#555;">{{ $portal_url }}</span>
    </p>

    <hr style="margin-top:40px;">

    <p style="font-size:14px; color:#777;">
        Best regards,<br>
        {{ $exhibition_name }} Team
    </p>

</body>
</html>
