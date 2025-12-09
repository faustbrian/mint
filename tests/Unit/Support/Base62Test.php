<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Support\Base62;

describe('Base62', function (): void {
    describe('Happy Path', function (): void {
        it('encodes integer correctly', function (): void {
            $encoded = Base62::encode(12_345);

            expect($encoded)->not->toBeEmpty();
            expect($encoded)->toMatch('/^[0-9A-Za-z]+$/');
        });

        it('decodes encoded string back to original integer as string', function (): void {
            $original = 12_345;
            $encoded = Base62::encode($original);
            $decoded = Base62::decode($encoded);

            expect($decoded)->toBe((string) $original);
        });

        it('encodes bytes correctly', function (): void {
            $bytes = hex2bin('0123456789abcdef');
            $encoded = Base62::encodeBytes($bytes);

            expect($encoded)->not->toBeEmpty();
            expect($encoded)->toMatch('/^[0-9A-Za-z]+$/');
        });

        it('decodes bytes correctly', function (): void {
            $original = hex2bin('0123456789abcdef');
            $encoded = Base62::encodeBytes($original);
            $decoded = Base62::decodeBytes($encoded, mb_strlen($original));

            expect($decoded)->toBe($original);
        });

        it('uses standard Base62 alphabet', function (): void {
            $encoded = Base62::encode(\PHP_INT_MAX);

            expect($encoded)->toMatch('/^[0-9A-Za-z]+$/');
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero', function (): void {
            $encoded = Base62::encode(0);
            $decoded = Base62::decode($encoded);

            expect($decoded)->toBe('0');
        });

        it('handles large numbers', function (): void {
            $large = \PHP_INT_MAX;
            $encoded = Base62::encode($large);
            $decoded = Base62::decode($encoded);

            expect($decoded)->toBe((string) $large);
        });

        it('handles single byte', function (): void {
            $bytes = chr(0xFF);
            $encoded = Base62::encodeBytes($bytes);
            $decoded = Base62::decodeBytes($encoded, 1);

            expect($decoded)->toBe($bytes);
        });

        it('pads encoding to specified length', function (): void {
            $bytes = chr(1);
            $encoded = Base62::encodeBytes($bytes, 10);

            expect(mb_strlen($encoded))->toBe(10);
        });

        it('handles 128-bit values (like UUIDs)', function (): void {
            $bytes = random_bytes(16);
            $encoded = Base62::encodeBytes($bytes, 22);
            $decoded = Base62::decodeBytes($encoded, 16);

            expect($decoded)->toBe($bytes);
        });

        it('handles 160-bit values (like KSUIDs)', function (): void {
            $bytes = random_bytes(20);
            $encoded = Base62::encodeBytes($bytes, 27);
            $decoded = Base62::decodeBytes($encoded, 20);

            expect($decoded)->toBe($bytes);
        });
    });
});
