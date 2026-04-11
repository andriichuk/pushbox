<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox;

use Andriichuk\Pushbox\Exceptions\PushboxException;
use Andriichuk\Pushbox\Preview\PreviewResolver;
use Andriichuk\Pushbox\Registry\NotificationGroup;
use Andriichuk\Pushbox\Registry\NotificationItem;
use Andriichuk\Pushbox\Registry\NotifyRegistrar;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class Pushbox
{
    private bool $hasCollected = false;

    private ?NotifyRegistrar $activeRegistrar = null;

    /**
     * @var Collection<int, NotificationItem>
     */
    private Collection $collection;

    private ?string $locale = null;

    public function __construct()
    {
        $this->collection = new Collection;
    }

    public function add(Closure|string $factory): NotificationItem
    {
        return $this->registrar()->add($factory);
    }

    public function label(string $label): NotifyRegistrar
    {
        return $this->registrar()->label($label);
    }

    public function category(string $category): NotifyRegistrar
    {
        return $this->registrar()->category($category);
    }

    public function to(mixed $notifiable): NotifyRegistrar
    {
        return $this->registrar()->to($notifiable);
    }

    private function registrar(): NotifyRegistrar
    {
        if ($this->activeRegistrar instanceof NotifyRegistrar) {
            return $this->activeRegistrar;
        }

        return NotifyRegistrar::make($this->collection);
    }

    /**
     * @return Collection<int, NotificationItem>
     */
    public function notifications(): Collection
    {
        $this->collect();

        return $this->collection;
    }

    /**
     * @return Collection<int, NotificationItem|NotificationGroup>
     */
    public function groupedNotifications(): Collection
    {
        $output = collect();
        $items = $this->notifications();
        $categories = [];

        foreach ($items as $item) {
            if ($item->hasCategory() && ! in_array($item->getCategory(), $categories, true)) {
                $categories[] = $item->getCategory();
                $categoryItems = $items
                    ->filter(fn (NotificationItem $n) => $n->getCategory() === $item->getCategory())
                    ->values();

                $output->push(new NotificationGroup(
                    label: $item->getCategory() ?? '',
                    items: $categoryItems,
                ));
            } elseif (! in_array($item->getCategory(), $categories, true)) {
                $output->push($item);
            }
        }

        return $output;
    }

    private function collect(): void
    {
        if ($this->hasCollected) {
            return;
        }

        $filename = config('pushbox.route_file', base_path('routes/pushbox.php'));

        if (is_string($filename) && file_exists($filename)) {
            include $filename;

            $this->hasCollected = true;
        }
    }

    public function setLocale(mixed $locale): ?string
    {
        if (! is_string($locale) || $locale === '') {
            return null;
        }

        $codes = $this->localeCodes();
        if ($codes !== [] && ! in_array($locale, $codes, true)) {
            return null;
        }

        return $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @return list<string>
     */
    private function localeCodes(): array
    {
        $locales = config('pushbox.locales');

        if (! is_array($locales)) {
            return [];
        }

        return array_keys($locales);
    }

    public function setRegistrar(?NotifyRegistrar $registrar): void
    {
        $this->activeRegistrar = $registrar;
    }

    public function clearRegistrar(): void
    {
        $this->activeRegistrar = null;
    }

    public function retrieve(?string $class, ?string $variant, ?string $locale, bool $fallback = false): ?NotificationItem
    {
        $items = $this->notifications();

        if ($items->isEmpty()) {
            throw new PushboxException('No Pushbox notifications registered');
        }

        $selected = null;

        if ($class) {
            $selected = $items->first(fn (NotificationItem $n) => mb_strtolower($n->className()) === mb_strtolower($class));
        }

        if (! $selected instanceof NotificationItem && $fallback) {
            $selected = $items->first();
        }

        if (! $selected instanceof NotificationItem) {
            return null;
        }

        $selected->selectVariant($variant);

        $this->setLocale($locale ?? (string) config('app.locale'));

        return $selected;
    }

    /**
     * Resolve preview payload (FCM) for a notification item.
     *
     * @return array{fcm: ?array<string, mixed>}
     */
    public function previewPayload(NotificationItem $item): array
    {
        $resolver = App::make(PreviewResolver::class);

        return $resolver->resolve($item);
    }
}
