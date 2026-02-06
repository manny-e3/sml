<!DOCTYPE html>
<html>
<head>
    <title>Security Master Request Approved</title>
</head>
<body>
    <h2>Request Approved</h2>
    <p>Your request to {{ $pending->request_type }} security "<strong>{{ $pending->security_name }}</strong>" has been approved.</p>
    
    <a href="{{ $dashboardUrl }}">Go to Dashboard</a>
</body>
</html>
