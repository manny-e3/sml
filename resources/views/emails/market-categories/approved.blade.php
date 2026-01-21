<!DOCTYPE html>
<html>
<head>
    <title>Request Approved</title>
</head>
<body>
    <p>Dear {{ $pending->requester->first_name }},</p>
    
    <p>Your request to {{ $pending->request_type }} the market category <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been approved.</p>
    
    <p>Thank you,</p>
    <p>{{ config('app.name') }} Team</p>
</body>
</html>
