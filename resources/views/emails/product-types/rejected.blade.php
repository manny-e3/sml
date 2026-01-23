<!DOCTYPE html>
<html>
<head>
    <title>Product Type Request Rejected</title>
</head>
<body>
    <h2>Product Type Request Rejected</h2>
    <p>Hello {{ $pending->requester->name ?? 'Inputter' }},</p>
    
    <p>Your request to <strong>{{ $pending->request_type }}</strong> the Product Type <strong>{{ $pending->name }} ({{ $pending->code }})</strong> has been REJECTED.</p>
    
    <p><strong>Rejected By:</strong> {{ $pending->selectedAuthoriser->name ?? 'Authoriser' }}</p>
    <p><strong>Reason:</strong> {{ $reason }}</p>
    <p><strong>Date:</strong> {{ now()->format('Y-m-d H:i') }}</p>

    <p>Please review the reason and submit a new request if necessary.</p>

    <p>Thank you,<br>
    {{ config('app.name') }}</p>
</body>
</html>
