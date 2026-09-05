<p>Hello,</p>
<p>Your delivery <strong>{{ $delivery->reference }}</strong> has a new status: <strong>{{ \App\Models\Delivery::statuses()[$delivery->status] }}</strong>.</p>
<p><a href="{{ $trackingUrl }}">View delivery tracking</a></p>
<p>This is a transactional delivery update. You can reply directly to this email to contact the delivery company.</p>
