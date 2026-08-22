<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
</head>

<body style="font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; color: #111827;">
    <h2 style="margin: 0 0 12px;">New Contact Message</h2>
    <p style="margin: 0 0 8px;"><strong>Name:</strong> {{ $data['full_name'] }}</p>
    <p style="margin: 0 0 8px;"><strong>Email:</strong> {{ $data['email'] }}</p>
    <p style="margin: 0 0 8px;"><strong>Phone:</strong> {{ $data['phone'] }}</p>
    <p style="margin: 12px 0 6px;"><strong>Message:</strong></p>
    <div style="white-space: pre-wrap; background: #f3f4f6; padding: 12px; border-radius: 6px;">
        {!! nl2br(e($data['message'])) !!}
    </div>
</body>

</html>