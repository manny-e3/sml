<!DOCTYPE html>
<html>
<head>
    <title>Security Type Request Approved</title>
</head>
<body>
    <h2>Security Type Request Approved</h2>
    <p>Hello {{ $recipientName }},</p>
    
    <p>Your request to <strong>{{ $pending->request_type }}</strong> the Security Type <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been APPROVED.</p>
    
    <p><strong>Approved By:</strong> {{ $pending->selectedAuthoriser->name ?? 'Authoriser' }}</p>


</body>
</html>
