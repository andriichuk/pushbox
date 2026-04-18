<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-hidden bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Pushbox</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@100..900&family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @import "tailwindcss";

        :root {
            color-scheme: light;
            --background: oklch(0.98 0 0);
            --foreground: oklch(0.15 0 0);
            --card: oklch(1 0 0);
            --card-foreground: oklch(0.15 0 0);
            --popover: oklch(1 0 0);
            --popover-foreground: oklch(0.15 0 0);
            --primary: oklch(0.15 0 0);
            --primary-foreground: oklch(0.98 0 0);
            --secondary: oklch(0.94 0 0);
            --secondary-foreground: oklch(0.15 0 0);
            --muted: oklch(0.94 0 0);
            --muted-foreground: oklch(0.45 0 0);
            --accent: oklch(0.94 0 0);
            --accent-foreground: oklch(0.15 0 0);
            --destructive: oklch(0.55 0.2 25);
            --destructive-foreground: oklch(0.98 0 0);
            --border: oklch(0.88 0 0);
            --input: oklch(0.94 0 0);
            --ring: oklch(0.7 0 0);
            --chart-1: oklch(0.7 0.15 145);
            --chart-2: oklch(0.65 0.12 200);
            --chart-3: oklch(0.6 0.1 260);
            --chart-4: oklch(0.75 0.15 80);
            --chart-5: oklch(0.7 0.18 30);
            --radius: 0.5rem;
            --sidebar: oklch(0.96 0 0);
            --sidebar-foreground: oklch(0.15 0 0);
            --sidebar-primary: oklch(0.15 0 0);
            --sidebar-primary-foreground: oklch(0.98 0 0);
            --sidebar-accent: oklch(0.92 0 0);
            --sidebar-accent-foreground: oklch(0.15 0 0);
            --sidebar-border: oklch(0.88 0 0);
            --sidebar-ring: oklch(0.7 0 0);
            --success: oklch(0.55 0.15 145);
            --success-foreground: oklch(0.98 0 0);
            --warning: oklch(0.7 0.15 80);
            --warning-foreground: oklch(0.15 0 0);
            --info: oklch(0.55 0.12 230);
            --info-foreground: oklch(0.98 0 0);
        }

        @theme inline {
            --font-sans: "Geist", ui-sans-serif, system-ui, sans-serif;
            --font-mono: "Geist Mono", ui-monospace, monospace;
            --color-background: var(--background);
            --color-foreground: var(--foreground);
            --color-card: var(--card);
            --color-card-foreground: var(--card-foreground);
            --color-popover: var(--popover);
            --color-popover-foreground: var(--popover-foreground);
            --color-primary: var(--primary);
            --color-primary-foreground: var(--primary-foreground);
            --color-secondary: var(--secondary);
            --color-secondary-foreground: var(--secondary-foreground);
            --color-muted: var(--muted);
            --color-muted-foreground: var(--muted-foreground);
            --color-accent: var(--accent);
            --color-accent-foreground: var(--accent-foreground);
            --color-destructive: var(--destructive);
            --color-destructive-foreground: var(--destructive-foreground);
            --color-border: var(--border);
            --color-input: var(--input);
            --color-ring: var(--ring);
            --color-chart-1: var(--chart-1);
            --color-chart-2: var(--chart-2);
            --color-chart-3: var(--chart-3);
            --color-chart-4: var(--chart-4);
            --color-chart-5: var(--chart-5);
            --radius-sm: calc(var(--radius) - 4px);
            --radius-md: calc(var(--radius) - 2px);
            --radius-lg: var(--radius);
            --radius-xl: calc(var(--radius) + 4px);
            --color-sidebar: var(--sidebar);
            --color-sidebar-foreground: var(--sidebar-foreground);
            --color-sidebar-primary: var(--sidebar-primary);
            --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
            --color-sidebar-accent: var(--sidebar-accent);
            --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
            --color-sidebar-border: var(--sidebar-border);
            --color-sidebar-ring: var(--sidebar-ring);
            --color-success: var(--success);
            --color-success-foreground: var(--success-foreground);
            --color-warning: var(--warning);
            --color-warning-foreground: var(--warning-foreground);
            --color-info: var(--info);
            --color-info-foreground: var(--info-foreground);
        }

        @layer base {
            * {
                @apply border-border outline-ring/50;
            }
            html,
            body {
                @apply bg-background text-foreground;
            }
        }
    </style>
</head>
<body class="h-full overflow-hidden font-sans antialiased">
@php
    $pushboxFlashMessages = [];
    if (session('status')) {
        $pushboxFlashMessages[] = ['type' => 'success', 'text' => (string) session('status')];
    }
    if ($errors->any()) {
        $pushboxFlashMessages[] = ['type' => 'error', 'text' => (string) $errors->first()];
    }
@endphp

<div id="pushbox-app" class="flex h-full min-h-0 w-full flex-row overflow-hidden bg-background text-foreground">
    @if(count($pushboxFlashMessages) > 0)
        <div class="pointer-events-none fixed top-4 right-4 z-50 flex max-w-md flex-col gap-2 p-0 max-lg:left-4 max-lg:right-4">
            @foreach($pushboxFlashMessages as $msg)
                <div
                    role="alert"
                    class="pointer-events-auto flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg {{ $msg['type'] === 'success' ? 'border-success/30 bg-success/10 text-success' : 'border-destructive/30 bg-destructive/10 text-destructive' }}"
                >
                    @if($msg['type'] === 'success')
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 12 2 2 4-4"/></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                    @endif
                    <p class="flex-1 text-sm font-medium">{{ $msg['text'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Mobile header --}}
    <div class="fixed top-0 left-0 right-0 z-40 flex h-14 items-center justify-between border-b border-border bg-background px-4 lg:hidden">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-primary-foreground">
                    <path d="m7.5 4.27 9 5.15"/>
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                    <path d="m3.3 7 8.7 5 8.7-5"/>
                    <path d="M12 22V12"/>
                </svg>
            </div>
            <span class="font-semibold">Pushbox</span>
        </div>
        <button type="button" id="pushbox-menu-toggle" class="inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground" aria-label="Open menu">
            <svg id="pushbox-icon-menu" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
            <svg id="pushbox-icon-close" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <div id="pushbox-overlay" class="fixed inset-0 z-30 hidden bg-background/80 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    <div
        id="pushbox-sidebar-wrap"
        class="fixed inset-y-0 left-0 z-40 top-14 h-[calc(100vh-3.5rem)] w-72 shrink-0 -translate-x-full transform transition-transform duration-300 lg:relative lg:top-0 lg:h-full lg:min-h-0 lg:translate-x-0"
    >
        <aside class="flex h-full w-72 flex-col border-r border-border bg-sidebar">
            <div class="border-b border-border px-4 py-5">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-foreground"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                    <div>
                        <h1 class="text-base font-semibold text-foreground">Pushbox</h1>
                        <p class="text-xs text-muted-foreground">Browse registered notifications</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-2">
                <div class="mb-2 px-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Notifications</div>
                @foreach($grouped as $entry)
                    @if($entry instanceof \Andriichuk\Pushbox\Registry\NotificationGroup)
                        @php
                            $gl = strtolower($entry->label);
                            $isOrders = str_contains($gl, 'order');
                            $isUsers = str_contains($gl, 'user') || str_contains($gl, 'account');
                            $isMarketing = str_contains($gl, 'market') || str_contains($gl, 'promo');
                        @endphp
                        <details class="group mb-1" open>
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md px-2 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground marker:content-none [&::-webkit-details-marker]:hidden">
                                <svg class="h-3.5 w-3.5 shrink-0 -rotate-90 transition-transform group-open:rotate-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                @if($isOrders)
                                    <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                @elseif($isUsers)
                                    <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                @elseif($isMarketing)
                                    <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                                @else
                                    <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                @endif
                                <span class="font-medium">{{ $entry->label }}</span>
                                <span class="ml-auto text-xs text-muted-foreground/70">{{ $entry->count() }}</span>
                            </summary>
                            <div class="ml-4 mt-0.5 space-y-0.5">
                                @foreach($entry as $n)
                                    @php $active = $selected && $selected->className() === $n->className(); @endphp
                                    <a
                                        href="{{ route('pushbox.index', array_filter(['class' => $n->className(), 'variant' => request('variant'), 'locale' => request('locale')])) }}"
                                        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors {{ $active ? 'bg-accent text-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground' }}"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-muted-foreground/50"></span>
                                        <span class="truncate text-left">{{ $n->label() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @else
                        @php $active = $selected && $selected->className() === $entry->className(); @endphp
                        <a
                            href="{{ route('pushbox.index', array_filter(['class' => $entry->className(), 'variant' => request('variant'), 'locale' => request('locale')])) }}"
                            class="mb-1 flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors {{ $active ? 'bg-accent text-foreground' : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground' }}"
                        >
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-muted-foreground/50"></span>
                            <span class="truncate">{{ $entry->label() }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>
    </div>

    <main class="min-h-0 min-w-0 flex-1 overflow-y-auto overscroll-y-contain pt-14 lg:pt-0">
        @if(!$selected)
            <div class="mx-auto max-w-5xl p-6">
                <div class="mb-8 space-y-3">
                    <form method="post" action="{{ route('pushbox.device-token') }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="class" value="">
                        <input type="hidden" name="variant" value="{{ request('variant') }}">
                        <input type="hidden" name="locale" value="{{ request('locale') }}">
                        <div class="flex min-w-0 flex-1 flex-col sm:min-w-[18rem]">
                            <label for="pushbox-fcm-token" class="mb-1.5 block text-xs font-medium text-muted-foreground">FCM Device Token</label>
                            <textarea id="pushbox-fcm-token" name="fcm_token" rows="2" autocomplete="off" spellcheck="false"
                                      placeholder="Paste token or leave empty for env default…"
                                      class="w-full resize-y rounded-md border border-border bg-input px-3 py-2 font-mono text-xs text-foreground placeholder:text-muted-foreground/50 focus:outline-none focus:ring-1 focus:ring-ring">{{ old('fcm_token', $fcmDeviceToken ?? '') }}</textarea>
                        </div>
                        <button type="submit" class="inline-flex h-9 shrink-0 items-center justify-center rounded-md bg-foreground px-4 text-sm font-medium text-background hover:bg-foreground/90 focus:outline-none focus:ring-2 focus:ring-ring">
                            Send
                        </button>
                    </form>
                    <p class="text-xs text-muted-foreground">Send stores the token for this session. Select a notification to dispatch FCM when sending is enabled.</p>
                </div>
                <div class="flex min-h-[40vh] flex-1 items-center justify-center rounded-lg border-2 border-dashed border-border p-12 text-center">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto mb-4 text-muted-foreground/50"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        <h3 class="text-lg font-medium text-foreground">No notification selected</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Select a notification from the list to preview its FCM payload.</p>
                    </div>
                </div>
            </div>
        @else
            @php
                $fcm = $payload['fcm'] ?? null;
                $display = is_array($fcm) ? ($fcm['display'] ?? []) : [];
                $dTitle = $display['title'] ?? null;
                $dBody = $display['body'] ?? null;
                $dImage = $display['image'] ?? null;
                $appLabel = config('app.name', 'App');

                $structured = is_array($fcm) && isset($fcm['structured']) && is_array($fcm['structured'])
                    ? $fcm['structured']
                    : [];
                $metaLocale = request('locale', app()->getLocale());
                $localeName = is_array($locales ?? null) ? ($locales[$metaLocale] ?? $metaLocale) : $metaLocale;
                $variantQ = request('variant');

                $notifBlock = is_array($structured['notification'] ?? null) ? $structured['notification'] : [];
                $alertTitle = $notifBlock['title'] ?? $dTitle;
                $alertBody = $notifBlock['body'] ?? $dBody;
                $alertImage = $notifBlock['image'] ?? $dImage;

                $dataRows = [];
                if (isset($structured['data']) && is_array($structured['data'])) {
                    foreach ($structured['data'] as $key => $value) {
                        if (! is_string($key)) {
                            continue;
                        }
                        $dataRows[] = [
                            'key' => $key,
                            'label' => \Illuminate\Support\Str::title(str_replace('_', ' ', $key)),
                            'value' => is_string($value) ? $value : json_encode($value),
                        ];
                    }
                }

                $androidTtl = \Illuminate\Support\Arr::get($structured, 'android.ttl');
                $apnsExpRaw = \Illuminate\Support\Arr::get($structured, 'apns.headers.apns-expiration');
                $apnsReadable = null;
                if ($apnsExpRaw !== null && is_numeric($apnsExpRaw)) {
                    $apnsReadable = date('Y-m-d H:i:s P', (int) $apnsExpRaw);
                } elseif (is_string($apnsExpRaw) && $apnsExpRaw !== '') {
                    $apnsReadable = $apnsExpRaw;
                }

                $pushboxSendEnabled = (bool) ($send['enabled'] ?? false);
                $pushboxSendEnvOk = app()->isLocal() || $sendAllowNonLocal;
                $pushboxCanSend = $pushboxSendEnabled && $pushboxSendEnvOk;
            @endphp

            <div class="mx-auto max-w-5xl p-6">
                <div class="mb-6 flex flex-wrap items-center gap-3">
                    <form class="flex flex-wrap items-center gap-3" method="get" action="{{ route('pushbox.index') }}">
                        <input type="hidden" name="class" value="{{ $selected->className() }}">
                        @if(count($selected->variants()) > 0)
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted-foreground">
                                Variant
                                <select name="variant" onchange="this.form.submit()"
                                        class="h-9 w-44 rounded-md border border-border bg-input px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                                    <option value="">Default</option>
                                    @foreach(array_keys($selected->variants()) as $v)
                                        <option value="{{ $v }}" @selected(request('variant') === $v)>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        @if(count($locales) > 0)
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted-foreground">
                                Locale
                                <select name="locale" onchange="this.form.submit()"
                                        class="h-9 w-36 rounded-md border border-border bg-input px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                                    @foreach($locales as $code => $label)
                                        <option value="{{ $code }}" @selected(request('locale', app()->getLocale()) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                    </form>
                </div>

                <div class="mb-6 flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm">
                    <div class="grid auto-rows-min grid-rows-[auto_auto] items-start gap-2 px-6 pb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($selected->hasCategory())
                                <span class="inline-flex items-center justify-center rounded-md border border-transparent bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground"> {{ $selected->getCategory() }} </span>
                            @endif
                            <span class="inline-flex items-center justify-center rounded-md border border-border px-2 py-0.5 text-xs font-medium text-foreground">{{ $localeName }}</span>
                            @if(is_string($variantQ) && $variantQ !== '')
                                <span class="inline-flex items-center justify-center rounded-md border border-border px-2 py-0.5 text-xs font-medium text-foreground">{{ $variantQ }}</span>
                            @endif
                            @if($fcm)
                                <span class="inline-flex items-center justify-center rounded-md border border-transparent bg-success px-2 py-0.5 text-xs font-medium text-success-foreground">FCM Ready</span>
                            @else
                                <span class="inline-flex items-center justify-center rounded-md border border-transparent bg-destructive px-2 py-0.5 text-xs font-medium text-white">No FCM</span>
                            @endif
                        </div>
                        <div>
                            <div class="leading-none font-semibold text-xl">{{ $selected->label() }}</div>
                            <p class="mt-2 break-all font-mono text-sm text-muted-foreground">{{ $selected->className() }}</p>
                        </div>
                    </div>
                </div>

                {{-- FCM token: Send persists token; dispatches when FCM + send allowed --}}
                <div class="mb-6 space-y-3">
                    <form
                        method="post"
                        action="{{ ($fcm && $pushboxCanSend) ? route('pushbox.send') : route('pushbox.device-token') }}"
                        class="flex flex-wrap items-end gap-3"
                        @if($fcm && $pushboxCanSend)
                            onsubmit="return confirm(@json('Send (sync queue)? Token is saved for this session. Priority: field → session → env.'));"
                        @endif
                    >
                        @csrf
                        <input type="hidden" name="class" value="{{ $selected->className() }}">
                        <input type="hidden" name="variant" value="{{ request('variant') }}">
                        <input type="hidden" name="locale" value="{{ request('locale') }}">
                        <div class="flex min-w-0 flex-1 flex-col sm:min-w-[18rem]">
                            <label for="pushbox-fcm-token" class="mb-1.5 block text-xs font-medium text-muted-foreground">FCM Device Token</label>
                            <textarea id="pushbox-fcm-token" name="fcm_token" rows="2" autocomplete="off" spellcheck="false"
                                      placeholder="Paste token or leave empty for env default…"
                                      class="w-full resize-y rounded-md border border-border bg-input px-3 py-2 font-mono text-xs text-foreground placeholder:text-muted-foreground/50 focus:outline-none focus:ring-1 focus:ring-ring">{{ old('fcm_token', $fcmDeviceToken ?? '') }}</textarea>
                        </div>
                        <button type="submit" class="inline-flex h-9 shrink-0 items-center justify-center rounded-md bg-foreground px-4 text-sm font-medium text-background hover:bg-foreground/90 focus:outline-none focus:ring-2 focus:ring-ring">
                            Send
                        </button>
                    </form>
                    <p class="text-xs text-muted-foreground">Send always stores the token for this session (clear the field and Send to remove). When dispatching, uses this field if set, otherwise session or <code class="rounded bg-secondary px-1 font-mono text-[11px]">PUSHBOX_FCM_TOKEN</code>.</p>
                    @if($fcm && $pushboxCanSend)
                        <p class="text-sm text-muted-foreground">Dispatches on the <strong class="font-semibold text-foreground">sync</strong> queue.</p>
                    @elseif($fcm && ! $pushboxCanSend)
                        <p class="text-sm text-muted-foreground">Sending is disabled until configuration allows it.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-foreground">
                            @if(! $pushboxSendEnabled)
                                <li>Set <code class="rounded bg-secondary px-1 font-mono text-xs">PUSHBOX_ALLOW_SEND=true</code> in <code class="rounded bg-secondary px-1 font-mono text-xs">.env</code>.</li>
                            @endif
                            @if(! $pushboxSendEnvOk)
                                <li>Environment is <code class="rounded bg-secondary px-1 font-mono text-xs">{{ app()->environment() }}</code> — use <code class="rounded bg-secondary px-1 font-mono text-xs">APP_ENV=local</code> or <code class="rounded bg-secondary px-1 font-mono text-xs">PUSHBOX_SEND_NON_LOCAL=true</code>.</li>
                            @endif
                        </ul>
                    @elseif(! $fcm)
                        <p class="text-sm text-muted-foreground">With no FCM payload, <strong class="font-medium text-foreground">Send</strong> only updates the stored token.</p>
                    @endif
                    @if(($fcmSendTargetHint ?? '') !== '' && $fcm && $pushboxCanSend)
                        <p class="text-xs text-muted-foreground">Default target hint: {{ $fcmSendTargetHint }}</p>
                    @endif

                    @if(session('pushbox_send_result'))
                        @php
                            $sendResult = session('pushbox_send_result');
                            $srOk = is_array($sendResult) ? ($sendResult['ok'] ?? true) : true;
                            $srQueue = is_array($sendResult) ? ($sendResult['queue_connection'] ?? null) : null;
                            $srMs = is_array($sendResult) ? ($sendResult['duration_ms'] ?? null) : null;
                            $srTarget = is_array($sendResult) ? ($sendResult['target'] ?? null) : null;
                            $srClass = is_array($sendResult) ? ($sendResult['notification_class'] ?? null) : null;
                            $srChannel = is_array($sendResult) ? ($sendResult['channel'] ?? null) : null;
                            $srResponse = is_array($sendResult) ? ($sendResult['response_text'] ?? null) : null;
                            $srNote = is_array($sendResult) ? ($sendResult['note'] ?? null) : null;
                        @endphp
                        <div class="rounded-lg border px-4 py-3 text-sm {{ $srOk ? 'border-success/30 bg-success/10 text-success' : 'border-destructive/30 bg-destructive/10 text-destructive' }}">
                            <p class="font-medium text-foreground">Sending report</p>
                            <dl class="mt-2 grid gap-2 text-xs sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <dt class="text-muted-foreground">Status</dt>
                                    <dd class="mt-0.5 font-medium text-foreground">{{ $srOk ? 'Success' : 'Failed' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-muted-foreground">Queue</dt>
                                    <dd class="mt-0.5 font-mono text-foreground">{{ $srQueue ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-muted-foreground">Duration</dt>
                                    <dd class="mt-0.5 font-mono text-foreground">{{ $srMs !== null ? $srMs.' ms' : '—' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-muted-foreground">Target (masked)</dt>
                                    <dd class="mt-0.5 break-all font-mono text-foreground">{{ $srTarget ?? '—' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-muted-foreground">Notification</dt>
                                    <dd class="mt-0.5 break-all font-mono text-[11px] leading-snug text-foreground">{{ $srClass ?? '—' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-muted-foreground">Channel</dt>
                                    <dd class="mt-0.5 break-all font-mono text-[11px] leading-snug text-foreground">{{ $srChannel ?? '—' }}</dd>
                                </div>
                            </dl>
                            @if($srNote)
                                <p class="mt-3 text-xs text-muted-foreground">{{ $srNote }}</p>
                            @endif
                            @if(filled($srResponse))
                                <p class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{{ $srOk ? 'FCM response' : 'Error detail' }}</p>
                                <pre class="mt-1 max-h-[min(320px,40vh)] overflow-auto rounded-lg border border-border bg-card p-3 text-[11px] leading-relaxed text-foreground">{{ e($srResponse) }}</pre>
                            @elseif($srOk && $srResponse === null)
                                <p class="mt-3 text-xs italic text-muted-foreground">No FCM response body captured.</p>
                            @endif
                        </div>
                    @endif
                </div>

                @if(!$fcm)
                    <div class="mb-6 flex flex-col gap-6 rounded-xl border border-warning/50 bg-warning/10 py-6 shadow-sm">
                        <div class="flex items-start gap-3 px-6 pt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 shrink-0 text-warning"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            <div>
                                <h4 class="font-medium text-foreground">No FCM Payload</h4>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    This notification does not expose a <code class="rounded bg-secondary px-1.5 py-0.5 font-mono text-xs">toFcm()</code> payload (or the method returns empty).
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-6 flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm">
                        <div class="px-6">
                            <div class="leading-none font-semibold flex items-center gap-2 text-base">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                Alert content
                            </div>
                        </div>
                        <div class="space-y-4 px-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Title</p>
                                    <p class="text-sm {{ $alertTitle ? 'text-foreground' : 'italic text-muted-foreground' }}">{{ $alertTitle ?: 'Not set' }}</p>
                                </div>
                                <div>
                                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Body</p>
                                    <p class="text-sm {{ $alertBody ? 'text-foreground' : 'italic text-muted-foreground' }}">{{ $alertBody ?: 'Not set' }}</p>
                                </div>
                            </div>
                            @if($alertImage)
                                <div>
                                    <p class="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                        Image
                                    </p>
                                    <div class="overflow-hidden rounded-lg border border-border">
                                        <img src="{{ $alertImage }}" alt="" class="h-32 w-full object-cover" loading="lazy">
                                    </div>
                                    <p class="mt-1 truncate font-mono text-xs text-muted-foreground" title="{{ $alertImage }}">{{ $alertImage }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(count($dataRows) > 0)
                        <div class="mb-6 flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm">
                            <div class="px-6">
                                <div class="leading-none font-semibold flex items-center gap-2 text-base">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    Data payload
                                </div>
                            </div>
                            <div class="px-6">
                                <div class="overflow-hidden rounded-lg border border-border">
                                    <table class="w-full text-sm">
                                        <thead class="bg-secondary">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Key</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Value</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border">
                                            @foreach($dataRows as $row)
                                                <tr>
                                                    <td class="px-4 py-2.5 text-muted-foreground">{{ $row['label'] }}</td>
                                                    <td class="px-4 py-2.5 font-mono text-foreground">{{ $row['value'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($androidTtl || $apnsReadable)
                        <div class="mb-6 flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm">
                            <div class="px-6">
                                <div class="leading-none font-semibold text-base">Platform configuration</div>
                            </div>
                            <div class="grid gap-6 px-6 sm:grid-cols-2">
                                @if($androidTtl)
                                    <div>
                                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Android</p>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-muted-foreground">TTL</span>
                                            <span class="font-mono">{{ $androidTtl }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if($apnsReadable)
                                    <div>
                                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">APNs (iOS)</p>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-muted-foreground">Expiration</span>
                                            <span class="font-mono text-xs">{{ $apnsReadable }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mb-6 flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm">
                        <div class="px-6">
                            <div class="leading-none font-semibold flex items-center gap-2 text-base">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                                Device previews
                            </div>
                            <p class="mt-2 text-sm text-muted-foreground">Preview how the notification appears on iOS and Android lock screens. Title and body come from the FCM payload.</p>
                        </div>
                        <div class="px-6">
                            <div class="flex flex-wrap justify-center gap-8">
                                {{-- iPhone lock screen (theme device-preview) --}}
                                <div class="flex flex-col items-center">
                                    <p class="mb-3 text-xs font-medium text-muted-foreground">iPhone Lock Screen</p>
                                    <div class="w-[320px] overflow-hidden rounded-3xl border border-border/50 shadow-2xl">
                                        <div class="bg-gradient-to-b from-[#1a1a2e] via-[#16213e] to-[#0f0f23] px-5 pb-6 pt-8">
                                            <div class="mx-auto mb-6 h-[28px] w-[100px] rounded-full bg-black"></div>
                                            <div class="mb-6 text-center">
                                                <p class="text-[64px] font-light leading-none tracking-tight text-white">9:41</p>
                                                <p class="mt-2 text-[14px] font-medium text-white/60">Friday, January 17</p>
                                            </div>
                                            <div class="rounded-[22px] bg-white/12 p-4 shadow-[inset_0_0.5px_0_0_rgba(255,255,255,0.1)] backdrop-blur-2xl">
                                                <div class="flex items-start gap-3">
                                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[11px] bg-gradient-to-b from-primary to-primary/80 text-base font-semibold text-primary-foreground shadow-sm">
                                                        {{ \Illuminate\Support\Str::substr($appLabel, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="mb-1 flex items-center justify-between">
                                                            <span class="text-[13px] font-semibold tracking-wide text-white/90">{{ $appLabel }}</span>
                                                            <span class="text-[12px] text-white/50">now</span>
                                                        </div>
                                                        <p class="text-[15px] font-semibold leading-tight text-white {{ $dTitle ? '' : 'italic text-white/40' }}">{{ $dTitle ?: 'No title in payload' }}</p>
                                                        <p class="mt-1 line-clamp-2 text-[14px] leading-snug text-white/80 {{ $dBody ? '' : 'italic text-white/40' }}">{{ $dBody ?: 'No body in payload' }}</p>
                                                    </div>
                                                </div>
                                                @if($dImage)
                                                    <div class="mt-3 overflow-hidden rounded-[14px]">
                                                        <img src="{{ $dImage }}" alt="" class="h-32 w-full object-cover" loading="lazy">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Android lock screen --}}
                                <div class="flex flex-col items-center">
                                    <p class="mb-3 text-xs font-medium text-muted-foreground">Android Lock Screen</p>
                                    <div class="w-[320px] overflow-hidden rounded-3xl border border-border/50 shadow-2xl">
                                        <div class="bg-gradient-to-b from-[#0d1117] via-[#161b22] to-[#0d1117] px-5 pb-6 pt-6">
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
                                            <div class="mb-6 text-center">
                                                <p class="text-[64px] font-extralight leading-none tracking-tight text-white">9:41</p>
                                                <p class="mt-2 text-[14px] font-medium text-white/60">Fri, Jan 17</p>
                                            </div>
                                            <div class="rounded-[26px] bg-[#1f2937]/95 p-4 shadow-xl">
                                                <div class="flex items-start gap-3">
                                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/80 text-base font-medium text-primary-foreground shadow-sm">
                                                        {{ \Illuminate\Support\Str::substr($appLabel, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="mb-1 flex items-center gap-1.5">
                                                            <span class="text-[13px] font-medium text-white/80">{{ $appLabel }}</span>
                                                            <span class="text-[12px] text-white/40">• now</span>
                                                        </div>
                                                        <p class="text-[15px] font-medium leading-tight text-white {{ $dTitle ? '' : 'italic text-white/40' }}">{{ $dTitle ?: 'No title in payload' }}</p>
                                                        <p class="mt-1 line-clamp-2 text-[14px] leading-snug text-white/70 {{ $dBody ? '' : 'italic text-white/40' }}">{{ $dBody ?: 'No body in payload' }}</p>
                                                    </div>
                                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                                </div>
                                                @if($dImage)
                                                    <div class="mt-3 overflow-hidden rounded-[18px]">
                                                        <img src="{{ $dImage }}" alt="" class="h-32 w-full object-cover" loading="lazy">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <details class="group mb-6 rounded-xl border bg-card text-card-foreground shadow-sm">
                        <summary class="cursor-pointer list-none px-6 py-5 marker:content-none [&::-webkit-details-marker]:hidden">
                            <div class="flex items-center justify-between gap-2 leading-none font-semibold text-base">
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    Raw FCM payload (JSON)
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </summary>
                        <div class="border-t border-border px-6 pb-6">
                            <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-secondary p-4 font-mono text-xs leading-relaxed">{{ $fcm['json'] ?? json_encode($fcm, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </details>
                @endif
            </div>
        @endif
    </main>
</div>

<script>
(function () {
    var wrap = document.getElementById('pushbox-sidebar-wrap');
    var overlay = document.getElementById('pushbox-overlay');
    var toggle = document.getElementById('pushbox-menu-toggle');
    var iconMenu = document.getElementById('pushbox-icon-menu');
    var iconClose = document.getElementById('pushbox-icon-close');
    if (!wrap || !overlay || !toggle) return;

    function setOpen(open) {
        if (open) {
            wrap.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            if (iconMenu) iconMenu.classList.add('hidden');
            if (iconClose) iconClose.classList.remove('hidden');
            toggle.setAttribute('aria-label', 'Close menu');
        } else {
            wrap.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            if (iconMenu) iconMenu.classList.remove('hidden');
            if (iconClose) iconClose.classList.add('hidden');
            toggle.setAttribute('aria-label', 'Open menu');
        }
    }

    var mq = window.matchMedia('(min-width: 1024px)');
    function onResize() {
        if (mq.matches) setOpen(true);
        else setOpen(false);
    }

    toggle.addEventListener('click', function () {
        var open = wrap.classList.contains('-translate-x-full');
        setOpen(open);
    });
    overlay.addEventListener('click', function () { setOpen(false); });
    mq.addEventListener('change', onResize);
    onResize();
})();
</script>
</body>
</html>
