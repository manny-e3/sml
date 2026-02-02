<!DOCTYPE html>
<html>
<head>
    <title>Security Type Request Approved</title>
</head>
<body>
    <h2>Security Type Request Approved</h2>
    <p>Hello {{ $pending->requester->name ?? 'Inputter' }},</p>
    
    <p>Your request to <strong>{{ $pending->request_type }}</strong> the Security Type <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been APPROVED.</p>
    
    <p><strong>Approved By:</strong> {{ $pending->selectedAuthoriser->name ?? 'Authoriser' }}</p>
    <p><strong>Date:</strong> {{ now()->format('Y-m-d H:i') }}</p>

    <p>Thank you,<br>
    {{ config('app.name') }}</p>
</body>
</html>
