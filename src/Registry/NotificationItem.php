<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Registry;

use Andriichuk\Pushbox\Exceptions\PushboxException;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Notifications\Notification;

class NotificationItem
{
    private ?string $selectedVariant = null;

    /** @var array<string, Closure> */
    private array $variants = [];

    /**
     * @param  Closure|string  $factory  Class name or closure returning Notification
     */
    public function __construct(
        private readonly string $className,
        private readonly ?string $label,
        private readonly ?string $category,
        private readonly Closure|string $factory,
        private mixed $notifiable,
    ) {}

    public function className(): string
    {
        return $this->className;
    }

    public function label(): string
    {
        return $this->label ?? class_basename($this->className);
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function hasCategory(): bool
    {
        return $this->category !== null && $this->category !== '';
    }

    /**
     * @return array<string, Closure>
     */
    public function variants(): array
    {
        return $this->variants;
    }

    public function variant(string $label, Closure $factory): self
    {
        $this->variants[$label] = $factory;

        return $this;
    }

    public function selectVariant(?string $variant): void
    {
        $this->selectedVariant = $variant;
    }

    public function selectedVariant(): ?string
    {
        return $this->selectedVariant;
    }

    public function resolve(Container $container): Notification
    {
        if ($this->selectedVariant !== null && isset($this->variants[$this->selectedVariant])) {
            return $container->call($this->variants[$this->selectedVariant]);
        }

        $factory = $this->factory;
        if (is_string($factory)) {
            return $container->make($factory);
        }

        return $container->call($factory);
    }

    public function notifiable(): mixed
    {
        return $this->notifiable;
    }

    public static function make(
        Closure|string $factory,
        ?string $label,
        ?string $category,
        mixed $notifiable,
    ): self {
        $className = is_string($factory)
            ? $factory
            : self::inferClassName($factory);

        return new self($className, $label, $category, $factory, $notifiable);
    }

    /**
     * @throws PushboxException
     */
    private static function inferClassName(Closure $factory): string
    {
        $r = new \ReflectionFunction($factory);
        $returnType = $r->getReturnType();
        if ($returnType instanceof \ReflectionNamedType && ! $returnType->isBuiltin()) {
            return $returnType->getName();
        }

        throw new PushboxException(
            'Closure factories must have a Notification return type hint, or pass a class string instead.'
        );
    }
}
