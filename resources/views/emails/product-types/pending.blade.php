
<body>
    <h2>New Product Type Request</h2>
    <p>Dear {{ $recipientName }},</p>
    
    <p>A new Product Type request has been submitted by {{ $pending->requester->name ?? 'Inputter' }} and is awaiting your approval.</p>
    
    <p><strong>Request Details:</strong></p>
    <ul>

        <li><strong>Name:</strong> {{ $pending->name }}</li>
      
    </ul>

    <p>Please log in to the system to review and approve/reject this request.</p>
    
  
</body>
</html>
