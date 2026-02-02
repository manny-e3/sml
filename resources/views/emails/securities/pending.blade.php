<!DOCTYPE html>
<html>
<head>
    <title>New Security Request</title>
</head>
<body>
    <h2>New Security Request: {{ ucfirst($pending->request_type) }}</h2>
    <p>Hello {{ $pending->selectedAuthoriser->firstname ?? 'Authoriser' }},</p>
    
    <p>A new Security request has been submitted by {{ $pending->requester->firstname ?? 'Inputter' }} {{ $pending->requester->last_name ?? '' }} and is awaiting your approval.</p>
    
    <p><strong>Request Details:</strong></p>
    <ul>
        <li><strong>Type:</strong> {{ ucfirst($pending->request_type) }}</li>
        <li><strong>ISIN:</strong> {{ $pending->isin ?? 'N/A' }}</li>
        <li><strong>Issuer:</strong> {{ $pending->issuer ?? 'N/A' }}</li>
        <li><strong>Description:</strong> {{ $pending->description ?? 'N/A' }}</li>
        <li><strong>Security Type:</strong> {{ $pending->security_type_name ?? 'N/A' }}</li>
        <li><strong>Product Type:</strong> {{ $pending->product_type_name ?? 'N/A' }}</li>
        <li><strong>Issue Date:</strong> {{ $pending->issue_date ? $pending->issue_date->format('Y-m-d') : 'N/A' }}</li>
        <li><strong>Maturity Date:</strong> {{ $pending->maturity_date ? $pending->maturity_date->format('Y-m-d') : 'N/A' }}</li>
        <li><strong>Coupon:</strong> {{ $pending->coupon ?? 'N/A' }}%</li>
        <li><strong>Date:</strong> {{ $pending->created_at->format('Y-m-d H:i') }}</li>
    </ul>

    <p>Please log in to the system to review and approve/reject this request.</p>
    
    <a href="{{ config('app.frontend_url', config('app.url')) }}/admin/pending-securities">Go to Pending Securities</a>

    <p>Thank you,<br>
    {{ config('app.name') }}</p>
</body>
</html>
