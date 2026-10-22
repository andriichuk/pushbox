<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Preview;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Registry\NotificationItem;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Localizable;

class PreviewResolver
{
    use Localizable;

    public function __construct(
        private readonly Container $container,
        private readonly FcmPreviewNormalizer $fcmNormalizer,
    ) {}

    /**
     * @return array{fcm: ?array<string, mixed>}
     */
    public function resolve(NotificationItem $item): array
    {
        $rollback = (bool) config('pushbox.database_rollback', false);

        if ($rollback) {
            DB::beginTransaction();

            try {
                return $this->resolveWithoutTransaction($item);
            } finally {
                DB::rollBack();
            }
        }

        return $this->resolveWithoutTransaction($item);
    }

    /**
     * @return array{fcm: ?array<string, mixed>}
     */
    private function resolveWithoutTransaction(NotificationItem $item): array
    {
        $book = $this->container->make('pushbox');
        $locale = $book instanceof Pushbox ? $book->getLocale() : null;

        return $this->withLocale($locale, function () use ($item): array {
            $notification = $item->resolve($this->container);
            $notifiable = $item->notifiable() ?? new \stdClass;

            $fcm = null;
            if (method_exists($notification, 'toFcm')) {
                $fcm = $this->fcmNormalizer->normalize($notification->toFcm($notifiable));
            }

            return ['fcm' => $fcm];
        });
    }
}
