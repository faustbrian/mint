<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\HashidGenerator;
use Cline\Mint\Support\Identifiers\Hashid;

describe('HashidGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('encodes single number', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeNumber(1);

            expect($hashid)->toBeInstanceOf(Hashid::class);
            expect($hashid->toString())->not->toBeEmpty();
        });

        it('encodes multiple numbers', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encode([1, 2, 3]);

            expect($hashid)->toBeInstanceOf(Hashid::class);
            expect($hashid->toString())->not->toBeEmpty();
        });

        it('decodes to original numbers', function (): void {
            $generator = new HashidGenerator();
            $original = [1, 2, 3];
            $hashid = $generator->encode($original);
            $decoded = $hashid->decode();

            expect($decoded)->toBe($original);
        });

        it('decodes single number correctly', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeNumber(42);
            $decoded = $hashid->decode();

            expect($decoded)->toBe([42]);
        });

        it('parses valid Hashid string', function (): void {
            $generator = new HashidGenerator();
            $original = $generator->encode([1, 2, 3]);
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
            expect($parsed->decode())->toBe([1, 2, 3]);
        });

        it('validates correct Hashid format', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeNumber(1);

            expect($generator->isValid($hashid->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new HashidGenerator();
            expect($generator->getName())->toBe('hashid');
        });

        it('generates same output for same input', function (): void {
            $generator = new HashidGenerator();
            $first = $generator->encode([1, 2, 3]);
            $second = $generator->encode([1, 2, 3]);

            expect($first->toString())->toBe($second->toString());
        });

        it('uses custom salt for different outputs', function (): void {
            $generator1 = new HashidGenerator('salt1');
            $generator2 = new HashidGenerator('salt2');

            $hashid1 = $generator1->encodeNumber(1);
            $hashid2 = $generator2->encodeNumber(1);

            expect($hashid1->toString())->not->toBe($hashid2->toString());
        });

        it('respects minimum length', function (): void {
            $generator = new HashidGenerator(minLength: 10);
            $hashid = $generator->encodeNumber(1);

            expect(mb_strlen($hashid->toString()))->toBeGreaterThanOrEqual(10);
        });

        it('is not sortable', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeNumber(1);

            expect($hashid->isSortable())->toBeFalse();
        });

        it('returns null timestamp', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeNumber(1);

            expect($hashid->getTimestamp())->toBeNull();
        });
    });

    describe('Hex Encoding', function (): void {
        it('encodes hex string', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeHex('507f1f77bcf86cd799439011');

            expect($hashid)->toBeInstanceOf(Hashid::class);
            expect($hashid->toString())->not->toBeEmpty();
            expect($hashid->isHexEncoded())->toBeTrue();
            expect($hashid->getHex())->toBe('507f1f77bcf86cd799439011');
        });

        it('decodes hex from generator', function (): void {
            $generator = new HashidGenerator();
            $originalHex = '507f1f77bcf86cd799439011';
            $hashid = $generator->encodeHex($originalHex);
            $decodedHex = $generator->decodeHex($hashid->toString());

            expect($decodedHex)->toBe($originalHex);
        });

        it('validates hex-encoded hashid', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeHex('abcdef');

            expect($generator->isValid($hashid->toString()))->toBeTrue();
        });
    });

    describe('Sad Path', function (): void {
        it('throws on parsing invalid string', function (): void {
            $generator = new HashidGenerator();

            expect(fn (): Hashid => $generator->parse('!!!invalid!!!'))
                ->toThrow(InvalidIdentifierException::class);
        });

        it('rejects empty string', function (): void {
            $generator = new HashidGenerator();
            expect($generator->isValid(''))->toBeFalse();
        });

        it('rejects invalid characters', function (): void {
            $generator = new HashidGenerator();
            expect($generator->isValid('!!!@@@###'))->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeNumber(0);

            expect($hashid->decode())->toBe([0]);
        });

        it('handles large numbers', function (): void {
            $generator = new HashidGenerator();
            $largeNumber = 1_000_000_000;
            $hashid = $generator->encodeNumber($largeNumber);

            expect($hashid->decode())->toBe([$largeNumber]);
        });

        it('handles many numbers', function (): void {
            $generator = new HashidGenerator();
            $numbers = range(0, 99);
            $hashid = $generator->encode($numbers);

            expect($hashid->decode())->toBe($numbers);
        });

        it('converts to bytes correctly', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encodeNumber(1);

            expect(mb_strlen($hashid->toBytes(), '8bit'))->toBe(mb_strlen($hashid->toString()));
        });

        it('generates unique IDs using auto-generate', function (): void {
            $generator = new HashidGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('decodes using direct method', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encode([1, 2, 3]);
            $decoded = $generator->decode($hashid->toString());

            expect($decoded)->toBe([1, 2, 3]);
        });

        it('getNumber returns first number', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encode([42, 100, 200]);

            expect($hashid->getNumber())->toBe(42);
        });

        it('getNumbers returns all numbers', function (): void {
            $generator = new HashidGenerator();
            $hashid = $generator->encode([42, 100, 200]);

            expect($hashid->getNumbers())->toBe([42, 100, 200]);
        });

        it('uses custom alphabet', function (): void {
            $alphabet = 'abcdefghijklmnopqrstuvwxyz1234567890';
            $generator = new HashidGenerator(alphabet: $alphabet);
            $hashid = $generator->encodeNumber(1);

            expect($hashid->toString())->toMatch('/^[a-z0-9]+$/');
        });
    });
});
