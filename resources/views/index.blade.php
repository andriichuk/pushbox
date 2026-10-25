<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-hidden bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $selected ? $selected->label().' · Pushbox' : 'Pushbox' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ $pushboxFavicon }}">
    <style>{!! $pushboxCss !!}</style>
</head>
<body class="h-full overflow-hidden font-sans antialiased">
<a class="pushbox-skip-link" href="#pushbox-content">Skip to notification</a>
<div id="pushbox-app" class="pushbox-shell">
    <header class="pushbox-mobile-header">
        <a class="pushbox-brand" href="{{ route('pushbox.index') }}"><span class="pushbox-brand-icon">@include('pushbox::logo')</span>Pushbox</a>
        <button type="button" id="pushbox-menu-toggle" class="pushbox-button pushbox-button-quiet" aria-label="Open menu" aria-expanded="false" aria-controls="pushbox-sidebar-wrap">
            <svg id="pushbox-icon-menu" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg id="pushbox-icon-close" class="hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg>
        </button>
    </header>
    <div id="pushbox-overlay" class="pushbox-overlay hidden" aria-hidden="true"></div>
    <aside id="pushbox-sidebar-wrap" class="pushbox-sidebar -translate-x-full" aria-label="Notification library">
        <a class="pushbox-brand pushbox-desktop-brand" href="{{ route('pushbox.index') }}"><span class="pushbox-brand-icon">@include('pushbox::logo')</span>Pushbox<span class="pushbox-brand-caption">Notification studio</span></a>
        <label class="pushbox-search" for="pushbox-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/></svg>
            <input id="pushbox-search" type="search" aria-label="Search notifications" placeholder="Find a notification" autocomplete="off" aria-keyshortcuts="/">
            <kbd aria-hidden="true">/</kbd>
        </label>
        <div class="pushbox-library-label">Notifications</div>
        <nav class="pushbox-library" aria-label="Registered notifications">
            @foreach($grouped as $entry)
                @if($entry instanceof \Andriichuk\Pushbox\Registry\NotificationGroup)
                    <details class="pushbox-nav-group" data-search-group open>
                        <summary><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg><span>{{ $entry->label }}</span><span class="pushbox-count">{{ count($entry) }}</span></summary>
                        @foreach($entry as $n)
                            <a class="pushbox-nav-link" data-search-item data-search-text="{{ $n->label().' '.$n->className().' '.$entry->label }}" href="{{ route('pushbox.index', array_filter(['class' => $n->className(), 'locale' => request('locale')])) }}" @if($selected?->className() === $n->className()) aria-current="page" @endif>{{ $n->label() }}</a>
                        @endforeach
                    </details>
                @else
                    <a class="pushbox-nav-link" data-search-item data-search-text="{{ $entry->label().' '.$entry->className() }}" href="{{ route('pushbox.index', array_filter(['class' => $entry->className(), 'locale' => request('locale')])) }}" @if($selected?->className() === $entry->className()) aria-current="page" @endif>{{ $entry->label() }}</a>
                @endif
            @endforeach
            <p id="pushbox-search-empty" class="pushbox-muted pushbox-search-empty" role="status" hidden>No matching notifications.</p>
        </nav>
        <div class="pushbox-sidebar-footer"><span class="pushbox-dot"></span>{{ ucfirst(app()->environment()) }} workspace</div>
    </aside>
    <main id="pushbox-main" class="pushbox-main">
        <div id="pushbox-content" class="pushbox-workspace" tabindex="-1">
            @if($selected)
                @php
                    $fcm = $payload['fcm'] ?? null;
                    $display = $fcm['display'] ?? [];
                    $dTitle = $display['title'] ?? null;
                    $dBody = $display['body'] ?? null;
                    $dImage = $display['image'] ?? null;
                    $appLabel = config('pushbox.preview_app_name') ?: config('app.name', 'App');
                    $structured = $fcm['structured'] ?? [];
                    $notification = $structured['notification'] ?? [];
                    $alertTitle = $notification['title'] ?? $dTitle;
                    $alertBody = $notification['body'] ?? $dBody;
                    $alertImage = $notification['image'] ?? $dImage;
                    $dataRows = is_array($structured['data'] ?? null) ? $structured['data'] : [];
                    $androidTtl = \Illuminate\Support\Arr::get($structured, 'android.ttl');
                    $apnsExpiration = \Illuminate\Support\Arr::get($structured, 'apns.headers.apns-expiration');
                    $pushboxCanSend = $fcm && ($send['enabled'] ?? false) && (app()->isLocal() || $sendAllowNonLocal) && $fcmDriverAvailable && ! $previewError;
                    $pushboxReadyToSend = $pushboxCanSend && $hasSendTarget;
                @endphp
                <header class="pushbox-workspace-header">
                    <div class="pushbox-heading-row">
                        <div class="pushbox-title-group">
                            <div class="pushbox-eyebrow">{{ $selected->getCategory() ?: 'Notifications' }}</div>
                            <h1>{{ $selected->label() }}</h1>
                            <div class="pushbox-class-row"><code id="pushbox-class-name">{{ $selected->className() }}</code><button type="button" class="pushbox-copy" data-copy-source="pushbox-class-name" data-copy-label="class name" aria-label="Copy class name">Copy</button></div>
                        </div>
                        <button type="button" class="pushbox-button pushbox-button-secondary" data-open-device aria-haspopup="dialog"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg>Device settings</button>
                    </div>
                    <div class="pushbox-toolbar">
                        <form id="pushbox-selection" class="pushbox-selectors" method="get" action="{{ route('pushbox.index') }}" data-preserve-scroll>
                            <input type="hidden" name="class" value="{{ $selected->className() }}">
                            @if(count($selected->variants()) > 0)
                                <div class="pushbox-selector"><label for="pushbox-variant">Variant</label><select id="pushbox-variant" name="variant" data-auto-submit><option value="">Standard</option>@foreach(array_keys($selected->variants()) as $variant)<option value="{{ $variant }}" @selected(request('variant') === $variant)>{{ $variant }}</option>@endforeach</select></div>
                            @endif
                            @if(count($locales) > 1)
                                <div class="pushbox-selector"><label for="pushbox-locale">Language</label><select id="pushbox-locale" name="locale" data-auto-submit>@foreach($locales as $code => $label)<option value="{{ $code }}" @selected($selectedLocale === $code)>{{ $label }}</option>@endforeach</select></div>
                            @else
                                <input type="hidden" name="locale" value="{{ $selectedLocale }}">
                                <span class="pushbox-language">{{ $locales[$selectedLocale] ?? $selectedLocale }}</span>
                            @endif
                            <span class="pushbox-badge {{ $fcm ? 'pushbox-badge-success' : 'pushbox-badge-warning' }}"><span class="pushbox-dot"></span>{{ $fcm ? 'Payload ready' : 'No payload' }}</span>
                        </form>
                        @if($fcm && $pushboxCanSend)
                            <form id="pushbox-send-form" method="post" action="{{ route('pushbox.send') }}">
                                @csrf
                                <input type="hidden" name="class" value="{{ $selected->className() }}">
                                <input type="hidden" name="variant" value="{{ request('variant') }}">
                                <input type="hidden" name="locale" value="{{ $selectedLocale }}">
                                <input type="hidden" name="target_hash" value="{{ $fcmSendTargetHash }}">
                                <button type="submit" class="pushbox-button pushbox-button-primary" @disabled(! $pushboxReadyToSend)><svg class="pushbox-send-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="m22 2-11 11"/></svg><span data-send-label>Send notification</span></button>
                            </form>
                        @endif
                    </div>
                    <div class="pushbox-send-help">
                        @if($pushboxReadyToSend)
                            <span>Sends immediately to your configured device. FCM acceptance does not guarantee delivery.</span>
                        @elseif($pushboxCanSend)
                            <span>Add a device token to enable sending.</span><button type="button" class="pushbox-text-button" data-open-device>Configure device</button>
                        @else
                            <span>Test sending is disabled.</span>
                            <details class="pushbox-inline-details"><summary>Setup details</summary><div>@if(! ($send['enabled'] ?? false))<p>Set <code>PUSHBOX_ALLOW_SEND=true</code> in your local environment.</p>@endif @if(! $fcmDriverAvailable)<p>Install the optional driver: <code>composer require laravel-notification-channels/fcm:^6.2</code>.</p>@endif @if(! app()->isLocal() && ! $sendAllowNonLocal)<p>Staging test sends also require <code>PUSHBOX_SEND_NON_LOCAL=true</code>.</p>@endif</div></details>
                        @endif
                    </div>
                </header>
            @else
                <header class="pushbox-heading-row pushbox-empty-heading"><div><div class="pushbox-eyebrow">Notification studio</div><h1>Pushbox</h1></div><button type="button" class="pushbox-button pushbox-button-secondary" data-open-device aria-haspopup="dialog">Device settings</button></header>
            @endif

            <div id="pushbox-send-feedback">@include('pushbox::send-result')</div>
            @if(session('status') && ! session('pushbox_send_result'))<p class="pushbox-notice" role="status">{{ session('status') }}</p>@endif
            @if($previewError)<div class="pushbox-error" role="alert"><strong>Unable to preview this notification</strong><p>{{ $previewError }}</p></div>@endif

            @if($selected && $fcm)
                <div class="pushbox-workspace-grid">
                    <div class="pushbox-content-column">
                        <section class="pushbox-panel" aria-labelledby="pushbox-alert-heading">
                            <h2 id="pushbox-alert-heading">Notification content</h2>
                            <dl class="pushbox-content-fields"><div><dt>Title</dt><dd>{{ $alertTitle ?: 'Not set' }}</dd></div><div><dt>Body</dt><dd>{{ $alertBody ?: 'Not set' }}</dd></div></dl>
                            @if($alertImage)<figure class="pushbox-payload-image"><img src="{{ $alertImage }}" alt="Notification image" loading="lazy"><figcaption>{{ $alertImage }}</figcaption></figure>@endif
                        </section>
                        @if(count($dataRows) > 0)
                            <section class="pushbox-panel pushbox-data-panel" aria-labelledby="pushbox-data-heading"><h2 id="pushbox-data-heading">Data payload <span class="pushbox-count">{{ count($dataRows) }}</span></h2><div class="pushbox-table-wrap"><table class="pushbox-data-table"><thead><tr><th scope="col">Key</th><th scope="col">Value</th><th scope="col"><span class="pushbox-sr-only">Actions</span></th></tr></thead><tbody>@foreach($dataRows as $key => $value) @php($displayValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))<tr><th scope="row"><code>{{ $key }}</code></th><td><code>{{ $displayValue }}</code></td><td><button type="button" class="pushbox-copy" data-copy-value="{{ $displayValue }}" data-copy-label="{{ $key }} value" aria-label="Copy {{ $key }} value">Copy</button></td></tr>@endforeach</tbody></table></div></section>
                        @endif
                        @if($androidTtl || $apnsExpiration)
                            <details class="pushbox-panel pushbox-disclosure"><summary><h2>Platform settings</h2><span class="pushbox-disclosure-hint">TTL and expiration</span><span class="pushbox-chevron" aria-hidden="true">⌄</span></summary><dl class="pushbox-platform-fields">@if($androidTtl)<div><dt>Android TTL</dt><dd><code>{{ $androidTtl }}</code></dd></div>@endif @if($apnsExpiration)<div><dt>APNs expiration</dt><dd><code>{{ is_numeric($apnsExpiration) ? date('Y-m-d H:i:s P', (int) $apnsExpiration) : $apnsExpiration }}</code></dd></div>@endif</dl></details>
                        @endif
                        <details class="pushbox-panel pushbox-disclosure" id="pushbox-raw-payload"><summary><h2>Raw payload</h2><span class="pushbox-disclosure-hint">JSON</span><span class="pushbox-chevron" aria-hidden="true">⌄</span></summary><div class="pushbox-code-toolbar"><button type="button" class="pushbox-button pushbox-button-secondary pushbox-button-small" data-copy-source="pushbox-raw-json" data-copy-label="JSON">Copy JSON</button></div><pre id="pushbox-raw-json" class="pushbox-code">{{ $fcm['json'] ?? json_encode($structured, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>
                    </div>
                    @include('pushbox::device-preview')
                </div>
            @elseif($selected)
                <div class="pushbox-empty-state"><h2>No FCM Payload</h2><p>This notification does not expose a <code>toFcm()</code> payload.</p></div>
            @elseif(! $previewError)
                <div class="pushbox-empty-state"><span class="pushbox-empty-icon">@include('pushbox::logo')</span><h2>Register your first notification</h2><p>Run <code>php artisan pushbox:install</code>, then add a notification in <code>routes/pushbox.php</code>.</p><pre class="pushbox-code">use Andriichuk\Pushbox\Facades\Pushbox;

Pushbox::add(App\Notifications\WelcomeNotification::class);</pre></div>
            @endif
        </div>
    </main>
    @include('pushbox::device-token')
    <span id="pushbox-announcement" class="pushbox-sr-only" role="status" aria-live="polite" aria-atomic="true"></span>
</div>
<script>{!! $pushboxJs !!}</script>
</body>
</html>
