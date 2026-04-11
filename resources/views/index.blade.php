<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pushbox</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; line-height: 1.5; }
        .layout { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        aside { border-right: 1px solid #ccc; padding: 1rem; }
        main { padding: 1rem; }
        a { color: inherit; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .muted { opacity: .75; font-size: .875rem; }
        .group-title { font-weight: 600; margin: 1rem 0 .25rem; }
        pre { background: rgba(127,127,127,.12); padding: 1rem; overflow: auto; border-radius: 8px; }
        .toolbar { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; }
        select, button { font: inherit; padding: .35rem .5rem; }
        .error { color: #b00020; }
        .ok { color: #0a7; }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            aside { border-right: none; border-bottom: 1px solid #ccc; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside>
        <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem;">
            <strong>Pushbox</strong>
        </div>
        <p class="muted">Browse registered notifications.</p>
        <hr>
        @foreach($grouped as $entry)
            @if($entry instanceof \Andriichuk\Pushbox\Registry\NotificationGroup)
                <div class="group-title">{{ $entry->label }}</div>
                <ul style="margin:0 0 .5rem 1rem; padding:0;">
                    @foreach($entry as $n)
                        <li style="margin:.15rem 0;">
                            <a href="{{ route('pushbox.index', array_filter(['class' => $n->className(), 'variant' => request('variant'), 'locale' => request('locale')])) }}">{{ $n->label() }}</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div style="margin:.35rem 0;">
                    <a href="{{ route('pushbox.index', array_filter(['class' => $entry->className(), 'variant' => request('variant'), 'locale' => request('locale')])) }}">{{ $entry->label() }}</a>
                </div>
            @endif
        @endforeach
    </aside>
    <main>
        @if(session('status'))
            <p class="ok">{{ session('status') }}</p>
        @endif
        @if($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif

        @if(!$selected)
            <p>Select a notification from the list.</p>
        @else
            <h1 style="margin-top:0;">{{ $selected->label() }}</h1>
            <p class="muted">{{ $selected->className() }}</p>

            <form class="toolbar" method="get" action="{{ route('pushbox.index') }}">
                <input type="hidden" name="class" value="{{ $selected->className() }}">
                @if(count($selected->variants()) > 0)
                    <label>
                        Variant
                        <select name="variant" onchange="this.form.submit()">
                            <option value="">Default</option>
                            @foreach(array_keys($selected->variants()) as $v)
                                <option value="{{ $v }}" @selected(request('variant') === $v)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if(count($locales) > 0)
                    <label>
                        Locale
                        <select name="locale" onchange="this.form.submit()">
                            @foreach($locales as $code => $label)
                                <option value="{{ $code }}" @selected(request('locale', app()->getLocale()) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </form>

            @php($fcm = $payload['fcm'] ?? null)

            <h2>FCM</h2>
            @if(!$fcm)
                <p class="muted">No <code>toFcm()</code> payload (or method missing).</p>
            @else
                <pre>{{ $fcm['json'] ?? json_encode($fcm, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif

            @if(($send['enabled'] ?? false) && (app()->isLocal() || $sendAllowNonLocal) && $fcm)
                <hr>
                <h2>Test send</h2>
                <p class="muted">Uses your configured FCM driver. Requires <code>PUSHBOX_FCM_TOKEN</code> and <code>PUSHBOX_ALLOW_SEND</code>.</p>
                <form method="post" action="{{ route('pushbox.send') }}" onsubmit="return confirm('Send FCM to PUSHBOX_FCM_TOKEN?');">
                    @csrf
                    <input type="hidden" name="class" value="{{ $selected->className() }}">
                    <input type="hidden" name="variant" value="{{ request('variant') }}">
                    <input type="hidden" name="locale" value="{{ request('locale') }}">
                    <button type="submit">Send FCM</button>
                </form>
            @endif
        @endif
    </main>
</div>
</body>
</html>
