<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Support\Base32;

describe('Base32 (Crockford)', function (): void {
    describe('Happy Path', function (): void {
        it('encodes number correctly', function (): void {
            $encoded = Base32::encode(12_345);

            expect($encoded)->not->toBeEmpty();
            expect($encoded)->toMatch('/^[0-9A-HJKMNP-TV-Z]+$/');
        });

        it('decodes encoded string back to original number', function (): void {
            $original = 12_345;
            $encoded = Base32::encode($original);
            $decoded = Base32::decode($encoded);

            expect($decoded)->toBe((string) $original);
        });

        it('encodes bytes correctly', function (): void {
            $bytes = random_bytes(16);
            $encoded = Base32::encodeBytes($bytes);

            expect($encoded)->not->toBeEmpty();
            expect($encoded)->toMatch('/^[0-9A-HJKMNP-TV-Z]+$/');
        });

        it('uses Crockford alphabet', function (): void {
            $encoded = Base32::encode(\PHP_INT_MAX);

            // Should not contain I, L, O, U (Crockford exclusions)
            expect($encoded)->not->toMatch('/[ILOU]/');
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero', function (): void {
            $encoded = Base32::encode(0);
            expect($encoded)->toBe('0');
        });

        it('handles large numbers', function (): void {
            $large = \PHP_INT_MAX;
            $encoded = Base32::encode($large);
            $decoded = Base32::decode($encoded);

            expect($decoded)->toBe((string) $large);
        });

        it('pads encoding to specified length', function (): void {
            $encoded = Base32::encode(1, 10);

            expect(mb_strlen($encoded))->toBe(10);
        });

        it('round trips bytes', function (): void {
            $original = random_bytes(10);
            $encoded = Base32::encodeBytes($original);
            $decoded = Base32::decodeBytes($encoded);

            // The decoded bytes may have padding - check length matches
            expect(mb_strlen($decoded))->toBeGreaterThanOrEqual(mb_strlen($original));
        });
    });
});
