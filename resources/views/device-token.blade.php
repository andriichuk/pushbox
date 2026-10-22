<div class="space-y-3 rounded-lg border border-border bg-card p-4">
    <form method="post" action="{{ route('pushbox.device-token') }}" class="flex flex-wrap items-end gap-3">
        @csrf
        <input type="hidden" name="class" value="{{ request('class') }}">
        <input type="hidden" name="variant" value="{{ request('variant') }}">
        <input type="hidden" name="locale" value="{{ request('locale') }}">
        <div class="flex min-w-0 flex-1 flex-col sm:min-w-[18rem]">
            <label for="pushbox-fcm-token" class="mb-1.5 block text-xs font-medium text-muted-foreground">FCM test device token</label>
            <textarea id="pushbox-fcm-token" name="fcm_token" rows="2" autocomplete="off" spellcheck="false"
                      placeholder="Paste your test device token"
                      class="w-full resize-y rounded-md border border-border bg-input px-3 py-2 font-mono text-xs text-foreground">{{ $fcmDeviceToken }}</textarea>
        </div>
        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-foreground px-4 text-sm font-medium text-background">Save token</button>
    </form>
    <form method="post" action="{{ route('pushbox.device-token.clear') }}">
        @csrf
        <input type="hidden" name="class" value="{{ request('class') }}">
        <input type="hidden" name="variant" value="{{ request('variant') }}">
        <input type="hidden" name="locale" value="{{ request('locale') }}">
        <button type="submit" class="text-xs text-muted-foreground underline">Clear saved token</button>
    </form>
    <p class="text-xs text-muted-foreground">{{ $fcmSendTargetHint }}. Saving or clearing never sends. A saved token overrides PUSHBOX_FCM_TOKEN.</p>
</div>
