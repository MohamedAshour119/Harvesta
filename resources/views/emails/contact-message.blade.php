<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Contact Message</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            background-color: #ffffff;
            margin: auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #FDAC29;
            color: white;
            padding: 20px;
            font-size: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            color: #333333;
            line-height: 1.6;
        }
        .content p {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #555555;
        }
        .footer {
            background-color: #f4f4f4;
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #888888;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            📩 New Contact Message
        </div>
        <div class="content">
            <p><span class="label">Full Name:</span> {{ $data['full_name'] }}</p>
            <p><span class="label">Email:</span> {{ $data['email'] }}</p>
            <p><span class="label">Phone:</span> {{ $data['phone'] }}</p>
            <hr>
            <p><span class="label">Message:</span></p>
            <p>{{ $data['message'] }}</p>
        </div>
        <div class="footer">
            This message was sent from your website's contact form.
        </div>
    </div>
</body>
</html>
