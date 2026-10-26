<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Preview\FcmDisplayExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FcmDisplayExtractorTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function imagePayloads(): array
    {
        $image = 'https://example.com/product.jpg';

        return [
            'notification' => [['notification' => ['image' => $image]]],
            'wrapped notification' => [['message' => ['notification' => ['image' => $image]]]],
            'android' => [['android' => ['notification' => ['image' => $image]]]],
            'wrapped android' => [['message' => ['android' => ['notification' => ['image' => $image]]]]],
            'webpush' => [['webpush' => ['notification' => ['image' => $image]]]],
            'wrapped webpush' => [['message' => ['webpush' => ['notification' => ['image' => $image]]]]],
            'apns' => [['apns' => ['fcm_options' => ['image' => $image]]]],
            'wrapped apns' => [['message' => ['apns' => ['fcm_options' => ['image' => $image]]]]],
            'data' => [['data' => ['image' => $image]]],
            'wrapped data' => [['message' => ['data' => ['image' => $image]]]],
            'legacy data key' => [['data' => ['gcm.notification.image' => $image]]],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('imagePayloads')]
    public function test_extracts_images_from_supported_payload_shapes(array $payload): void
    {
        self::assertSame('https://example.com/product.jpg', FcmDisplayExtractor::fromStructured($payload)['image']);
    }

    public function test_skips_missing_or_invalid_images_and_keeps_text_content(): void
    {
        $display = FcmDisplayExtractor::fromStructured([
            'notification' => ['title' => 'Order shipped', 'body' => 'Your parcel is on its way.', 'image' => []],
            'android' => ['notification' => ['image' => '']],
        ]);

        self::assertSame(['title' => 'Order shipped', 'body' => 'Your parcel is on its way.', 'image' => null], $display);
    }
}
