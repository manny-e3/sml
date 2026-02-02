<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Market Category Request</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .email-header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #ffffff; padding: 30px; text-align: center; }
        .email-header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .email-body { padding: 30px; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #f5576c; }
        .details-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .details-table td:first-child { font-weight: 600; width: 40%; color: #333; }
        .action-button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>📋 Market Category Request</h1>
        </div>
        <div class="email-body">
            <h2>Hello, Authoriser</h2>
            <p>A new request has been submitted by <strong>{{ $requester['firstname'] ?? 'User' }} {{ $requester['last_name'] ?? '' }}</strong> and is awaiting your approval.</p>
            
            <table class="details-table">
                <tr>
                    <td>Request Type:</td>
                    <td><span style="background: #eee; padding: 2px 6px; border-radius: 4px; font-weight: bold;">{{ strtoupper($pending->request_type) }}</span></td>
                </tr>
                <tr>
                    <td>Category Name:</td>
                    <td>{{ $pending->name }}</td>
                </tr>
                <tr>
                    <td>Code:</td>
                    <td>{{ $pending->code }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>Pending Approval</td>
                </tr>
                <tr>
                    <td>Submitted At:</td>
                    <td>{{ $pending->created_at->format('M d, Y h:i A') }}</td>
                </tr>
            </table>

            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="action-button">Review Request</a>
            </div>
            
            <p style="font-size: 13px; color: #777;">You can approve or reject this request from your dashboard.</p>
        </div>
    </div>
</body>
</html>
