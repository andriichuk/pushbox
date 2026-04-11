<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Registry;

use Andriichuk\Pushbox\Pushbox;
use Closure;
use Illuminate\Support\Collection;

class NotifyRegistrar
{
    private ?string $category = null;

    private mixed $notifiable = null;

    private ?string $label = null;

    public function __construct(
        private readonly Collection $collection,
    ) {}

    public static function make(Collection $collection): self
    {
        return new self($collection);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function category(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function to(mixed $notifiable): self
    {
        $this->notifiable = $notifiable;

        return $this;
    }

    public function group(Closure $callback): self
    {
        /** @var Pushbox $book */
        $book = app('pushbox');
        $book->setRegistrar($this);

        $callback();

        $book->clearRegistrar();

        return $this;
    }

    public function add(Closure|string $factory): NotificationItem
    {
        $item = NotificationItem::make(
            $factory,
            $this->label,
            $this->category,
            $this->notifiable,
        );

        $this->collection->push($item);

        return $item;
    }
}
