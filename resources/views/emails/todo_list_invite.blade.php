<!DOCTYPE html>
<html>
<body>
    <p>
        {{ $owner->name ?? 'Someone' }} invited you to collaborate on
        <strong>{{ $list->title ?? 'a todo list' }}</strong>.
    </p>
    @if($list?->description)
        <p>{{ $list->description }}</p>
    @endif
    <p>
        To respond, open the app and go to “Todo Invites”.
    </p>
    <hr>
    <p style="font-size:12px;color:#666;">
        Invite ID: {{ $invite->id }}
        @if($invite->expires_at)
            • Expires: {{ $invite->expires_at }}
        @endif
    </p>
    <p style="font-size:12px;color:#666;">
        If you weren’t expecting this, you can ignore this email.
    </p>
</body>
<!-- Plain Blade template for basic email content -->
</html>

