<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Generators\SqidGenerator;
use Cline\Mint\Support\Identifiers\Sqid;

describe('SqidGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('encodes single number', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encodeNumber(1);

            expect($sqid)->toBeInstanceOf(Sqid::class);
            expect($sqid->toString())->not->toBeEmpty();
        });

        it('encodes multiple numbers', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([1, 2, 3]);

            expect($sqid)->toBeInstanceOf(Sqid::class);
            expect($sqid->toString())->not->toBeEmpty();
        });

        it('decodes to original numbers', function (): void {
            $generator = new SqidGenerator();
            $original = [1, 2, 3];
            $sqid = $generator->encode($original);
            $decoded = $sqid->decode();

            expect($decoded)->toBe($original);
        });

        it('decodes single number correctly', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encodeNumber(42);
            $decoded = $sqid->decode();

            expect($decoded)->toBe([42]);
        });

        it('parses valid Sqid string', function (): void {
            $generator = new SqidGenerator();
            $original = $generator->encode([1, 2, 3]);
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
            expect($parsed->decode())->toBe([1, 2, 3]);
        });

        it('validates correct Sqid format', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encodeNumber(1);

            expect($generator->isValid($sqid->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new SqidGenerator();
            expect($generator->getName())->toBe('sqid');
        });

        it('generates same output for same input', function (): void {
            $generator = new SqidGenerator();
            $first = $generator->encode([1, 2, 3]);
            $second = $generator->encode([1, 2, 3]);

            expect($first->toString())->toBe($second->toString());
        });

        it('uses custom alphabet', function (): void {
            $alphabet = 'abcdefghijklmnopqrstuvwxyz';
            $generator = new SqidGenerator($alphabet);
            $sqid = $generator->encodeNumber(1);

            expect($sqid->toString())->toMatch('/^[a-z]+$/');
        });

        it('respects minimum length', function (): void {
            $generator = new SqidGenerator(minLength: 10);
            $sqid = $generator->encodeNumber(1);

            expect(mb_strlen($sqid->toString()))->toBeGreaterThanOrEqual(10);
        });

        it('is not sortable', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encodeNumber(1);

            expect($sqid->isSortable())->toBeFalse();
        });

        it('returns null timestamp', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encodeNumber(1);

            expect($sqid->getTimestamp())->toBeNull();
        });
    });

    describe('Sad Path', function (): void {
        it('returns empty array when decoding invalid string', function (): void {
            $generator = new SqidGenerator();
            // Completely invalid string should fail validation
            expect($generator->isValid(''))->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encodeNumber(0);

            expect($sqid->decode())->toBe([0]);
        });

        it('handles large numbers', function (): void {
            $generator = new SqidGenerator();
            $largeNumber = 1_000_000_000;
            $sqid = $generator->encodeNumber($largeNumber);

            expect($sqid->decode())->toBe([$largeNumber]);
        });

        it('handles many numbers', function (): void {
            $generator = new SqidGenerator();
            $numbers = range(0, 99);
            $sqid = $generator->encode($numbers);

            expect($sqid->decode())->toBe($numbers);
        });

        it('converts to bytes correctly', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encodeNumber(1);

            expect(mb_strlen($sqid->toBytes(), '8bit'))->toBe(mb_strlen($sqid->toString()));
        });

        it('generates unique IDs using auto-generate', function (): void {
            $generator = new SqidGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('decodes using direct method', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([1, 2, 3]);
            $decoded = $generator->decode($sqid->toString());

            expect($decoded)->toBe([1, 2, 3]);
        });
    });
});
