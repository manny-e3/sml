Hello Authoriser,

A new Market Category Request ({{ strtoupper($pending->request_type) }}) has been submitted by {{ $pending->requester->firstname ?? 'User' }}.

Details:
Type: {{ strtoupper($pending->request_type) }}
Name: {{ $pending->name }}
Code: {{ $pending->code }}
Date: {{ $pending->created_at->format('M d, Y h:i A') }}

Please review the request in your dashboard: {{ $dashboardUrl }}

Thanks,
{{ config('app.name') }}
