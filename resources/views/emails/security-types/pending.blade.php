<!DOCTYPE html>
<html>
<head>
    <title>New Security Type Request</title>
</head>
<body>
    <h2>New Security Type Request: {{ ucfirst($pending->request_type) }}</h2>
    <p>Hello {{ $pending->selectedAuthoriser->name ?? 'Authoriser' }},</p>
    
    <p>A new Security Type request has been submitted by {{ $pending->requester->name ?? 'Inputter' }} and is awaiting your approval.</p>
    
    <p><strong>Request Details:</strong></p>
    <ul>
        <li><strong>Type:</strong> {{ ucfirst($pending->request_type) }}</li>
        <li><strong>Name:</strong> {{ $pending->name }}</li>
        <li><strong>Code:</strong> {{ $pending->code }}</li>
        <li><strong>Date:</strong> {{ $pending->created_at->format('Y-m-d H:i') }}</li>
    </ul>

    <p>Please log in to the system to review and approve/reject this request.</p>
    
    <a href="{{ $dashboardUrl }}">Go to Dashboard</a>

    <p>Thank you,<br>
    {{ config('app.name') }}</p>
</body>
</html>
