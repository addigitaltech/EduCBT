@if (session('status'))
    <div class="alert alert--ok" role="status">
        <i data-lucide="circle-check" class="icon" aria-hidden="true"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

@if (session('temporary_password'))
    <div class="alert alert--warn" role="alert">
        <i data-lucide="key-round" class="icon" aria-hidden="true"></i>
        <div class="stack">
            <p><strong>Temporary password (shown only once):</strong></p>
            <p><span class="secret">{{ session('temporary_password') }}</span></p>
            <p>Give this to the user in person or by a private message. They must choose their own password the first time they sign in. It cannot be shown again, so reset the password if it is lost.</p>
        </div>
    </div>
@endif
