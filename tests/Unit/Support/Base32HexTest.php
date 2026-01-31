<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Support\Base32Hex;

describe('Base32Hex (RFC 4648)', function (): void {
    describe('Happy Path', function (): void {
        it('encodes bytes correctly', function (): void {
            $bytes = hex2bin('0123456789abcdef');
            $encoded = Base32Hex::encode($bytes);

            expect($encoded)->not->toBeEmpty();
            expect($encoded)->toMatch('/^[0-9a-v]+$/');
        });

        it('decodes encoded string back to original bytes', function (): void {
            $original = hex2bin('0123456789abcdef');
            $encoded = Base32Hex::encode($original);
            $decoded = Base32Hex::decode($encoded);

            expect($decoded)->toBe($original);
        });

        it('uses lowercase Base32Hex alphabet', function (): void {
            $bytes = random_bytes(12);
            $encoded = Base32Hex::encode($bytes);

            // 0-9, a-v (lowercase only)
            expect($encoded)->toMatch('/^[0-9a-v]+$/');
        });
    });

    describe('Edge Cases', function (): void {
        it('handles empty bytes', function (): void {
            $encoded = Base32Hex::encode('');
            expect($encoded)->toBe('');
        });

        it('handles single byte', function (): void {
            $bytes = chr(0xFF);
            $encoded = Base32Hex::encode($bytes);
            $decoded = Base32Hex::decode($encoded);

            expect($decoded)->toBe($bytes);
        });

        it('handles all zeros', function (): void {
            $bytes = str_repeat(chr(0), 12);
            $encoded = Base32Hex::encode($bytes);

            expect($encoded)->toMatch('/^0+$/');
        });

        it('handles all ones', function (): void {
            $bytes = str_repeat(chr(0xFF), 12);
            $encoded = Base32Hex::encode($bytes);
            $decoded = Base32Hex::decode($encoded);

            expect($decoded)->toBe($bytes);
        });

        it('handles 12 bytes (XID size)', function (): void {
            $bytes = random_bytes(12);
            $encoded = Base32Hex::encode($bytes);

            expect(mb_strlen($encoded))->toBe(20); // 12 bytes = 20 base32hex chars
        });

        it('round trips random data', function (): void {
            for ($i = 0; $i < 10; ++$i) {
                $original = random_bytes(12);
                $encoded = Base32Hex::encode($original);
                $decoded = Base32Hex::decode($encoded);

                expect($decoded)->toBe($original);
            }
        });
    });
});
