@if($errors->any() && ! $errors->has('fcm_token'))
    <div class="pushbox-result pushbox-result-error" role="alert"><div><strong>Could not send notification</strong><p>{{ $errors->first() }}</p></div><button type="button" class="pushbox-text-button" data-open-device>Edit device settings</button></div>
@endif
@if(is_array(session('pushbox_send_result')))
    @php
        $result = session('pushbox_send_result');
        $accepted = ($result['ok'] ?? false) === true;
        $note = (string) ($result['note'] ?? '');
        $tokenRejected = ! $accepted && (str_contains(strtolower($note), 'registration token') || str_contains(strtolower($note), 'invalid token') || str_contains(strtolower($note), 'not registered'));
        $summary = $accepted ? 'Accepted by FCM' : ($tokenRejected ? 'Device token rejected' : (($result['status'] ?? '') === 'skipped' ? 'Notification was not sent' : 'Could not send notification'));
        $explanation = $accepted ? 'The provider accepted the message. Device delivery is not guaranteed.' : ($tokenRejected ? 'Firebase rejected this device token. Update it in Device settings and try again.' : 'Review the provider details below before trying again.');
    @endphp
    <section class="pushbox-result {{ $accepted ? 'pushbox-result-success' : 'pushbox-result-error' }}" role="{{ $accepted ? 'status' : 'alert' }}" aria-label="Sending report">
        <div class="pushbox-result-summary"><span class="pushbox-result-symbol" aria-hidden="true">{{ $accepted ? '✓' : '!' }}</span><div><span class="pushbox-eyebrow">Sending report · {{ $accepted ? 'Accepted' : ucfirst($result['status'] ?? 'failed') }}</span><h2>{{ $summary }}</h2><p>{{ $explanation }}</p></div>@if(($result['duration_ms'] ?? null) !== null)<span class="pushbox-result-time">{{ $result['duration_ms'] }} ms</span>@endif</div>
        @if($tokenRejected)<button type="button" class="pushbox-text-button" data-open-device>Edit device settings</button>@endif
        <details class="pushbox-result-details"><summary>Technical details</summary><dl><div><dt>Status</dt><dd>{{ $accepted ? 'Accepted by FCM' : ucfirst($result['status'] ?? 'failed') }}</dd></div><div><dt>Target (masked)</dt><dd>{{ $result['target'] ?? 'Unavailable' }}</dd></div><div><dt>Notification</dt><dd>{{ $result['notification_class'] ?? 'Unavailable' }}</dd></div><div><dt>Channel</dt><dd>{{ $result['channel'] ?? 'Unavailable' }}</dd></div><div><dt>Queue</dt><dd>{{ $result['queue_connection'] ?? 'sync' }}</dd></div></dl>@if($note)<p>{{ $note }}</p>@endif @if(filled($result['response_text'] ?? null))<p class="pushbox-code-label">{{ $accepted ? 'FCM response' : 'Error detail' }}</p><pre class="pushbox-code">{{ $result['response_text'] }}</pre>@endif</details>
    </section>
@endif
