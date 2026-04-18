<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Sending\PushboxFcmSendOutcome;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class PushboxFcmSendOutcomeTest extends TestCase
{
    #[Test]
    public function null_response_is_not_successful(): void
    {
        $this->assertFalse(PushboxFcmSendOutcome::isSuccessfulResponse(null));
    }

    #[Test]
    public function multicast_with_failed_item_is_not_successful(): void
    {
        $failure = new class
        {
            public function isFailure(): bool
            {
                return true;
            }

            public function error(): RuntimeException
            {
                return new RuntimeException('invalid registration');
            }
        };

        $success = new class
        {
            public function isFailure(): bool
            {
                return false;
            }
        };

        $multicast = new class($failure, $success)
        {
            public function __construct(
                private object $a,
                private object $b,
            ) {}

            /**
             * @return array<int, object>
             */
            public function getItems(): array
            {
                return [$this->a, $this->b];
            }
        };

        $this->assertFalse(PushboxFcmSendOutcome::isSuccessfulResponse($multicast));
        $this->assertSame('invalid registration', PushboxFcmSendOutcome::firstFailureMessage($multicast));
    }

    #[Test]
    public function collection_of_successful_multicasts_is_successful(): void
    {
        $multicast = new class
        {
            /**
             * @return array<int, object>
             */
            public function getItems(): array
            {
                return [
                    new class
                    {
                        public function isFailure(): bool
                        {
                            return false;
                        }
                    },
                ];
            }
        };

        $col = new Collection([$multicast]);
        $this->assertTrue(PushboxFcmSendOutcome::isSuccessfulResponse($col));
        $this->assertNull(PushboxFcmSendOutcome::firstFailureMessage($col));
    }
}
