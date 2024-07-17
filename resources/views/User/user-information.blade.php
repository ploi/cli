<div>
    <p>Here is your information:</p>
    <ul class="list-none">
        <li>🖼️ &nbsp;<b>Avatar:</b> {{ $user->avatar }}</li>
        <li>👤 <b>Name:</b> {{ $user->name }}</li>
        <li>📧 <b>Email:</b> {{ $user->email }}</li>
        <li>🏠 <b>Billing Details:</b> {{ $user->billing_details ?? 'Empty' }}</li>
        <li>🌍 <b>Country:</b> {{ $user->country }}</li>
        <li>⏰ <b>Timezone:</b> {{ $user->timezone }}</li>
        <li>🌙 <b>Dark Mode:</b> {{ $user->dark_mode ? 'Yes' : 'No' }}</li>
        <li>📅 <b>Created At:</b> {{ $user->created_at }}</li>
        <li>💳 <b>Plan:</b> {{ $user->plan }}</li>
        <li>⌛ <b>Plan Expires At:</b> {{ $user->plan_expires_at }}</li>
    </ul>
</div>
