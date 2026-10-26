<section class="pushbox-panel pushbox-preview-panel" aria-labelledby="pushbox-preview-heading">
    <div class="pushbox-preview-heading"><h2 id="pushbox-preview-heading">Android preview</h2><span class="pushbox-badge pushbox-badge-neutral">Approximation</span></div>
    <p class="pushbox-muted">Appearance varies by device and app.</p>
    <div class="pushbox-preview-controls" role="group" aria-label="Notification appearance"><button type="button" data-preview-state="false" aria-pressed="true">Collapsed</button><button type="button" data-preview-state="true" aria-pressed="false">Expanded</button></div>
    <figure class="pushbox-device-stage"><figcaption class="pushbox-sr-only">Android Lock Screen</figcaption>
        <div class="pushbox-phone" data-expanded="false">
            <div class="bg-gradient-to-b from-[#0d1117] via-[#161b22] to-[#0d1117] px-4 pb-10 pt-6">
                <div class="mb-4 flex items-center justify-between px-1">
                    <span class="text-[12px] font-medium text-white/70">9:41</span>
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3 w-3 text-white/60" viewBox="0 0 24 24" fill="currentColor"><path d="M2 22h20V2L2 22zm18-2H6.83L20 6.83V20z"/></svg>
                        <svg class="h-3 w-3 text-white/60" viewBox="0 0 24 24" fill="currentColor"><path d="M12 18c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6-6c-.2 0-.4.1-.5.1l1.4 1.4c.1-.1.1-.2.1-.4 0-1.1-.9-2-2-2-.2 0-.3 0-.5.1L18 9.8c.3-.1.6-.2 1-.2 2.2 0 4 1.8 4 4 0 .4-.1.7-.2 1l1.5 1.5c.4-.8.7-1.6.7-2.5 0-3.3-2.7-6-6-6zm-6-6C6.5 6 1.8 9.6.2 14.5l1.5 1.5C3.1 11.8 7.2 8.5 12 8.5s8.9 3.3 10.3 7.5l1.5-1.5C22.2 9.6 17.5 6 12 6z"/></svg>
                        <div class="flex h-3 w-5 items-center rounded-sm border border-white/40 px-0.5">
                            <div class="h-1.5 w-3 rounded-[1px] bg-white/60"></div>
                        </div>
                    </div>
                </div>
                <div class="mb-10 text-center">
                    <p class="text-[64px] font-extralight leading-none tracking-tight text-white">9:41</p>
                    <p class="mt-2 text-[14px] font-medium text-white/60">Fri, Jan 17</p>
                </div>
                <div class="rounded-[26px] bg-[#1f2937]/95 px-5 py-5 shadow-xl">
                    <div class="flex min-w-0 items-center gap-2">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/80 text-[11px] font-medium text-primary-foreground">
                            {{ \Illuminate\Support\Str::substr($appLabel, 0, 1) }}
                        </div>
                        <span class="min-w-0 truncate text-[12px] font-medium text-white/80">{{ $appLabel }}</span>
                        <span class="shrink-0 whitespace-nowrap text-[11px] text-white/40">• now</span>
                        <button type="button" class="pushbox-preview-chevron" data-toggle-preview aria-expanded="false" aria-label="Expand notification"><svg class="h-4 w-4 shrink-0 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
                    </div>
                    <div class="pushbox-notification-content mt-3">
                        <div class="min-w-0 flex-1 space-y-2">
                            <p data-preview-title class="break-words text-[15px] font-medium leading-5 text-white {{ $dTitle ? '' : 'italic text-white/40' }}">{{ $dTitle ?: 'No title in payload' }}</p>
                            <p data-preview-body class="break-words text-[14px] leading-5 text-white/70 {{ $dBody ? '' : 'italic text-white/40' }}">{{ $dBody ?: 'No body in payload' }}</p>
                        </div>
                        @if($dImage)
                            @include('pushbox::notification-image', ['imageUrl' => $dImage, 'imageAlt' => 'Notification image thumbnail', 'imageClass' => 'pushbox-image-thumbnail'])
                        @endif
                    </div>
                    @if($dImage)
                        <div data-preview-expanded class="mt-4 overflow-hidden rounded-[18px]">
                            @include('pushbox::notification-image', ['imageUrl' => $dImage, 'imageAlt' => 'Notification image preview', 'imageClass' => 'pushbox-image-expanded'])
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </figure>
    <p id="pushbox-preview-note" class="pushbox-preview-note" aria-live="polite">Collapsed notification preview.</p>
</section>
