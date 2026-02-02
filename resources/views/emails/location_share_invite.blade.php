<!DOCTYPE html>
<html>
<body>
    <p>
        {{ $owner->name ?? 'Someone' }} invited you to a location share
        @if($share->name)
            named <strong>{{ $share->name }}</strong>.
        @else
            .
        @endif
    </p>
    <p>
        You’ll be able to see their shared location. To respond, open the app and go to “Location Invites”.
    </p>
    <hr>
    <p style="font-size:12px;color:#666;">
        Share ID: {{ $share->id }}
        @if($share->expires_at)
            • Expires: {{ $share->expires_at }}
        @endif
    </p>
    <p style="font-size:12px;color:#666;">
        If you weren’t expecting this, you can ignore this email.
    </p>
</body>
<!-- Plain Blade template for basic email content -->
</html>

