<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Http\Controllers;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Support\TokenMask;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushboxController extends Controller
{
    public const SESSION_FCM_DEVICE_TOKEN = 'pushbox.fcm_device_token';

    public function index(Request $request, Pushbox $pushbox): View
    {
        // The view also reads these query values when building links and forms.
        foreach (['class', 'variant', 'locale'] as $key) {
            $value = $request->query($key);
            $request->query->set($key, is_string($value) ? $value : null);
        }

        $grouped = collect();
        $item = null;
        $payload = ['fcm' => null];
        $previewError = null;

        try {
            $grouped = $pushbox->groupedNotifications();
            $item = $pushbox->retrieve($request->query('class'), $request->query('variant'), $request->query('locale'), true);
            $payload = $item ? $pushbox->previewPayload($item) : $payload;
        } catch (Throwable $exception) {
            $previewError = $exception->getMessage();
        }

        /** @var view-string $view */
        $view = 'pushbox::index';

        return view($view, [
            'grouped' => $grouped,
            'selected' => $item,
            'payload' => $payload,
            'previewError' => $previewError,
            'selectedLocale' => $pushbox->getLocale() ?? app()->getLocale(),
            'locales' => (array) config('pushbox.locales', []),
            'send' => (array) config('pushbox.send', []),
            'sendAllowNonLocal' => (bool) config('pushbox.send_allow_non_local', false),
            'fcmDriverAvailable' => class_exists(SendFcmNotification::FCM_CHANNEL),
            'fcmDeviceToken' => $this->sessionDeviceToken($request),
            'fcmSendTargetHint' => $this->sendTargetLabel($request),
            'fcmSendTargetHash' => hash('sha256', $this->resolvedSendToken($request)),
            'hasSendTarget' => $this->resolvedSendToken($request) !== '',
        ]);
    }

    public function saveDeviceToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['nullable', 'string', 'max:8192'],
            ...$this->selectionRules(),
        ]);

        $token = trim($validated['fcm_token'] ?? '');
        if ($token === '') {
            $request->session()->forget(self::SESSION_FCM_DEVICE_TOKEN);
        } else {
            $request->session()->put(self::SESSION_FCM_DEVICE_TOKEN, $token);
        }

        return $this->backToSelection($request)->with('status', $token === ''
            ? 'Saved device token cleared. The configured default, if any, is now the send target.'
            : 'Device token saved for this session. No notification was sent.');
    }

    public function clearDeviceToken(Request $request): RedirectResponse
    {
        $request->validate($this->selectionRules());
        $request->session()->forget(self::SESSION_FCM_DEVICE_TOKEN);

        return $this->backToSelection($request)
            ->with('status', 'Saved device token cleared. No notification was sent.');
    }

    public function send(Request $request, SendFcmNotification $sender): RedirectResponse
    {
        abort_unless((bool) config('pushbox.send.enabled', false), 403);
        abort_unless(app()->isLocal() || (bool) config('pushbox.send_allow_non_local', false), 403);

        $validated = $request->validate([
            ...$this->selectionRules(),
            'class' => ['required', 'string', 'max:1024'],
            'target_hash' => ['required', 'string', 'size:64'],
            'fcm_token' => ['prohibited'],
        ]);

        $token = $this->resolvedSendToken($request);
        if ($token === '') {
            return $this->backToSelection($request)->withErrors(['pushbox' => 'Save a test device token or configure PUSHBOX_FCM_TOKEN before sending.']);
        }

        if (! hash_equals(hash('sha256', $token), $validated['target_hash'])) {
            return $this->backToSelection($request)->withErrors(['pushbox' => 'The target changed. Reload the preview before sending again.']);
        }

        $report = $sender->send(
            $token,
            $validated['class'],
            $validated['variant'] ?? null,
            $validated['locale'] ?? null,
        );

        $ok = $report['ok'];
        $status = $report['status'];
        Log::log($ok ? 'info' : 'warning', 'pushbox.'.$status, [
            'channel' => 'fcm',
            'class' => $validated['class'],
            'target' => TokenMask::mask($token),
        ]);

        $redirect = $this->backToSelection($request)->with('pushbox_send_result', $report);
        if ($ok) {
            $redirect->with('status', 'Accepted by FCM. Device delivery is not guaranteed.');
        }

        return $redirect;
    }

    /** @return array<string, list<string>> */
    private function selectionRules(): array
    {
        return [
            'class' => ['nullable', 'string', 'max:1024'],
            'variant' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:32'],
        ];
    }

    private function backToSelection(Request $request): RedirectResponse
    {
        return redirect()->route('pushbox.index', array_filter($request->only(['class', 'variant', 'locale'])));
    }

    private function resolvedSendToken(Request $request): string
    {
        return $this->sessionDeviceToken($request) ?: trim((string) config('pushbox.send.fcm.token', ''));
    }

    private function sessionDeviceToken(Request $request): string
    {
        $token = $request->session()->get(self::SESSION_FCM_DEVICE_TOKEN);

        return is_string($token) ? trim($token) : '';
    }

    private function sendTargetLabel(Request $request): string
    {
        $session = $this->sessionDeviceToken($request);
        if ($session !== '') {
            return 'Saved device · '.TokenMask::mask($session);
        }

        $default = trim((string) config('pushbox.send.fcm.token', ''));

        return $default !== '' ? 'Configured default · '.TokenMask::mask($default) : 'No test device configured';
    }
}
