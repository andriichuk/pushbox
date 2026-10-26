<dialog id="pushbox-device-dialog" class="pushbox-device-dialog" aria-labelledby="pushbox-device-heading" @if($errors->has('fcm_token')) data-open="true" @endif>
    <div class="pushbox-dialog-heading"><div><h2 id="pushbox-device-heading">Device settings</h2><p>Choose the device that receives test notifications.</p></div><button type="button" class="pushbox-button pushbox-button-quiet" data-close-device aria-label="Close device settings">✕</button></div>
    <form method="post" action="{{ route('pushbox.device-token') }}" class="pushbox-device-form" data-preserve-scroll>
        @csrf
        <input type="hidden" name="class" value="{{ request('class') }}">
        <input type="hidden" name="variant" value="{{ request('variant') }}">
        <input type="hidden" name="locale" value="{{ request('locale') }}">
        <label for="pushbox-fcm-token">FCM test device token</label>
        <textarea id="pushbox-fcm-token" name="fcm_token" rows="4" autofocus autocomplete="off" spellcheck="false" placeholder="Paste your test device token" aria-describedby="pushbox-token-help @error('fcm_token') pushbox-token-error @enderror">{{ $fcmDeviceToken }}</textarea>
        <p id="pushbox-token-help" class="pushbox-muted">Stored for this browser session. A saved token overrides the configured default.</p>
        @error('fcm_token')<p id="pushbox-token-error" class="pushbox-field-error">{{ $message }}</p>@enderror
        <div class="pushbox-device-status">{{ $fcmSendTargetHint }}</div>
        <div class="pushbox-dialog-actions"><button type="button" class="pushbox-button pushbox-button-secondary" data-close-device>Cancel</button><button type="submit" class="pushbox-button pushbox-button-primary">Save token</button></div>
    </form>
    @if($fcmDeviceToken !== '')
        <form method="post" action="{{ route('pushbox.device-token.clear') }}" class="pushbox-clear-token" data-preserve-scroll>
            @csrf
            <input type="hidden" name="class" value="{{ request('class') }}">
            <input type="hidden" name="variant" value="{{ request('variant') }}">
            <input type="hidden" name="locale" value="{{ request('locale') }}">
            <button type="submit" class="pushbox-text-button">Clear saved token</button><span class="pushbox-muted">Saving or clearing never sends a notification.</span>
        </form>
    @endif
</dialog>
