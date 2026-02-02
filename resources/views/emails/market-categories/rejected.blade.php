<!DOCTYPE html>
<html>
<head>
    <title>Request Rejected</title>
</head>
<body>
    <p>Dear {{ $requester['firstname'] ?? 'User' }},</p>
    
    <p>Your request to {{ $pending->request_type }} the market category <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been rejected.</p>
    
    <p><strong>Reason:</strong> {{ $reason }}</p>
    
    <p>Thank you,</p>
    <p>{{ config('app.name') }} Team</p>
</body>
</html>
