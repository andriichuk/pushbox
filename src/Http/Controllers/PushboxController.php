<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Http\Controllers;

use Andriichuk\Pushbox\Pushbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PushboxController extends Controller
{
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
        ]);
    }

    public function send(Request $request, Pushbox $pushbox): RedirectResponse
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

        $notification = $item->resolve(app());

        try {
            $this->sendFcm($notification);
        } catch (\Throwable $e) {
            Log::error('pushbox.send_failed', [
                'exception' => $e->getMessage(),
                'channel' => 'fcm',
                'class' => $validated['class'],
            ]);

            return redirect()
                ->route('pushbox.index', $request->only(['class', 'variant', 'locale']))
                ->withErrors(['pushbox' => 'Send failed: '.$e->getMessage()]);
        }

        Log::info('pushbox.sent', [
            'channel' => 'fcm',
            'class' => $validated['class'],
        ]);

        return redirect()
            ->route('pushbox.index', array_filter([
                'class' => $validated['class'],
                'variant' => $validated['variant'] ?? null,
                'locale' => $validated['locale'] ?? null,
            ]))
            ->with('status', 'Sent.');
    }

    private function sendFcm(object $notification): void
    {
        $token = (string) config('pushbox.send.fcm.token', '');
        if ($token === '') {
            throw new \InvalidArgumentException('PUSHBOX_FCM_TOKEN is not set.');
        }

        $channel = 'NotificationChannels\\Fcm\\FcmChannel';
        if (! class_exists($channel)) {
            throw new \RuntimeException('laravel-notification-channels/fcm is not installed.');
        }

        Notification::route($channel, $token)->notify($notification);
    }
}
