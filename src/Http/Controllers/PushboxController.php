<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Http\Controllers;

use Andriichuk\Pushbox\Jobs\SendPushboxFcmNotificationJob;
use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Support\TokenMask;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PushboxController extends Controller
{
    public const SESSION_FCM_DEVICE_TOKEN = 'pushbox.fcm_device_token';

    public function index(Request $request, Pushbox $pushbox): View
    {
        $class = $request->query('class');
        $variant = $request->query('variant');
        $locale = $request->query('locale');

        $item = $pushbox->retrieve(
            is_string($class) ? $class : null,
            is_string($variant) ? $variant : null,
            is_string($locale) ? $locale : null,
            true,
        );

        $payload = $item ? $pushbox->previewPayload($item) : ['fcm' => null];

        /** @var view-string $view */
        $view = 'pushbox::index';

        return view($view, [
            'grouped' => $pushbox->groupedNotifications(),
            'selected' => $item,
            'payload' => $payload,
            'locales' => (array) config('pushbox.locales', []),
            'send' => (array) config('pushbox.send', []),
            'sendAllowNonLocal' => (bool) config('pushbox.send_allow_non_local', false),
            'fcmDeviceToken' => $this->sessionDeviceToken($request),
            'fcmSendTargetHint' => $this->sendTargetLabel($request),
        ]);
    }

    public function saveDeviceToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['nullable', 'string', 'max:8192'],
            'class' => ['nullable', 'string'],
            'variant' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
        ]);

        $token = $this->persistFcmTokenToSession($request, $validated['fcm_token'] ?? null);

        return redirect()
            ->route('pushbox.index', array_filter([
                'class' => $validated['class'] ?? null,
                'variant' => $validated['variant'] ?? null,
                'locale' => $validated['locale'] ?? null,
            ]))
            ->with('status', $token === '' ? 'Device token cleared.' : 'Device token saved for this session.');
    }

    public function send(Request $request, Pushbox $pushbox, Dispatcher $dispatcher): RedirectResponse
    {
        if (! (bool) config('pushbox.send.enabled', false)) {
            abort(403);
        }

        if (! app()->isLocal() && ! (bool) config('pushbox.send_allow_non_local', false)) {
            abort(403);
        }

        $validated = $request->validate([
            'class' => ['required', 'string'],
            'variant' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'fcm_token' => ['nullable', 'string', 'max:8192'],
        ]);

        $item = $pushbox->retrieve(
            $validated['class'],
            $validated['variant'] ?? null,
            $validated['locale'] ?? null,
            false,
        );

        if (! $item) {
            return redirect()->route('pushbox.index')->withErrors(['pushbox' => 'Notification not found.']);
        }

        // Resolve before persisting: an empty POST must not wipe session before we read it for
        // "field → session → env", and must not make an env token look like a deliberate send.
        $token = $this->resolvedSendToken($request);
        if ($token === '') {
            return redirect()
                ->route('pushbox.index', $request->only(['class', 'variant', 'locale']))
                ->withErrors(['pushbox' => 'Set a device token in the FCM field (or configure PUSHBOX_FCM_TOKEN).']);
        }

        $this->persistFcmTokenToSession($request, $validated['fcm_token'] ?? null);

        try {
            $dispatcher->dispatch(
                (new SendPushboxFcmNotificationJob(
                    $token,
                    $validated['class'],
                    $validated['variant'] ?? null,
                    $validated['locale'] ?? null,
                ))->onConnection('sync'),
            );
        } catch (\Throwable $e) {
            Log::error('pushbox.send_failed', [
                'exception' => $e->getMessage(),
                'channel' => 'fcm',
                'class' => $validated['class'],
            ]);

            $failureReport = app()->bound('pushbox.send_report') ? app('pushbox.send_report') : null;
            if (app()->bound('pushbox.send_report')) {
                app()->forgetInstance('pushbox.send_report');
            }

            if (is_array($failureReport)) {
                return redirect()
                    ->route('pushbox.index', array_filter([
                        'class' => $validated['class'],
                        'variant' => $validated['variant'] ?? null,
                        'locale' => $validated['locale'] ?? null,
                    ]))
                    ->with('pushbox_send_result', $failureReport)
                    ->withInput($request->only('fcm_token'));
            }

            return redirect()
                ->route('pushbox.index', $request->only(['class', 'variant', 'locale']))
                ->withErrors(['pushbox' => 'Send failed: '.$e->getMessage()])
                ->withInput($request->only('fcm_token'));
        }

        Log::info('pushbox.sent', [
            'channel' => 'fcm',
            'class' => $validated['class'],
            'queue' => 'sync',
        ]);

        $sendReport = app()->bound('pushbox.send_report')
            ? app('pushbox.send_report')
            : null;
        app()->forgetInstance('pushbox.send_report');

        if (! is_array($sendReport)) {
            $sendReport = [
                'ok' => true,
                'queue_connection' => 'sync',
                'duration_ms' => null,
                'target' => TokenMask::mask($token),
                'notification_class' => $validated['class'],
                'channel' => SendPushboxFcmNotificationJob::FCM_CHANNEL,
                'response_text' => null,
                'note' => 'No detailed report (the send job did not run).',
            ];
        }

        $redirect = redirect()
            ->route('pushbox.index', array_filter([
                'class' => $validated['class'],
                'variant' => $validated['variant'] ?? null,
                'locale' => $validated['locale'] ?? null,
            ]))
            ->with('pushbox_send_result', $sendReport);

        if (($sendReport['ok'] ?? false) === true) {
            $redirect->with('status', 'Sent via sync queue.');
        }

        return $redirect;
    }

    private function resolvedSendToken(Request $request): string
    {
        $raw = $request->input('fcm_token');
        if (is_string($raw)) {
            $posted = trim($raw);
            if ($posted !== '') {
                return $posted;
            }
        }

        $session = $this->sessionDeviceToken($request);
        if ($session !== '') {
            return $session;
        }

        return trim((string) config('pushbox.send.fcm.token', ''));
    }

    private function sessionDeviceToken(Request $request): string
    {
        $t = $request->session()->get(self::SESSION_FCM_DEVICE_TOKEN);

        return is_string($t) ? trim($t) : '';
    }

    private function sendTargetLabel(Request $request): string
    {
        $fromSession = $this->sessionDeviceToken($request);
        if ($fromSession !== '') {
            return 'session · '.TokenMask::mask($fromSession);
        }

        $env = trim((string) config('pushbox.send.fcm.token', ''));
        if ($env !== '') {
            return 'env · '.TokenMask::mask($env);
        }

        return 'FCM token field (posted with Send)';
    }

    /**
     * @return string Trimmed token, or empty string if cleared
     */
    private function persistFcmTokenToSession(Request $request, mixed $raw): string
    {
        $token = is_string($raw) ? trim($raw) : '';

        if ($token === '') {
            $request->session()->forget(self::SESSION_FCM_DEVICE_TOKEN);
        } else {
            $request->session()->put(self::SESSION_FCM_DEVICE_TOKEN, $token);
        }

        return $token;
    }
}
