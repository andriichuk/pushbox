<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Preview;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Registry\NotificationItem;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Localizable;
use stdClass;

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
            $connection = DB::connection();
            $transactionLevel = $connection->transactionLevel();
            $connection->beginTransaction();

            try {
                return $this->resolveWithoutTransaction($item);
            } finally {
                $connection->rollBack($transactionLevel);
            }
        }

        return $this->resolveWithoutTransaction($item);
    }

    /**
     * @return array{fcm: ?array<string, mixed>}
     */
    private function resolveWithoutTransaction(NotificationItem $item): array
    {
        $locale = $this->container->make(Pushbox::class)->getLocale();

        return $this->withLocale($locale, function () use ($item, $locale): array {
            $notification = clone $item->resolve($this->container);
            $notification->locale($locale);
            $notifiable = $item->notifiable() ?? new stdClass;

            $fcm = null;

            if (method_exists($notification, 'toFcm')) {
                $fcm = $this->fcmNormalizer->normalize($notification->toFcm($notifiable));
            }

            return ['fcm' => $fcm];
        });
    }
}
