<!DOCTYPE html>
<html>
<head>
    <title>Request Approved</title>
</head>
<body>
    <p>Dear {{ $recipientName }},</p>
    
    <p>Your request to {{ $pending->request_type }} the market category <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been approved.</p>
    
 
</body>
</html>
