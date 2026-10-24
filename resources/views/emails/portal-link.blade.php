<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Patient portal</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hello {{ $name }},</p>
    <p>
        Use the button below to choose a password for your {{ $hospital }} patient portal account.
        This link works once and expires in {{ $hours }} hour(s).
    </p>
    <p><a href="{{ $url }}" style="background:#0f5c6e;color:#fff;padding:10px 18px;text-decoration:none;border-radius:4px;">Set my password</a></p>
    <p>If the button does not work, copy this address into your browser:<br>{{ $url }}</p>
    <p>If you did not expect this email, you can ignore it. Nobody can access your account without this link.
       Hospital staff will never ask you for your password.</p>
</body>
</html>
