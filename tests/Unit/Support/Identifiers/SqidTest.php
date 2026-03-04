<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Generators\SqidGenerator;
use Cline\Mint\Support\Identifiers\Sqid;

describe('Sqid', function (): void {
    describe('Happy Path', function (): void {
        it('returns encoded numbers', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([1, 2, 3]);

            expect($sqid->getNumbers())->toBe([1, 2, 3]);
        });

        it('returns first number from single-value Sqid', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([42]);

            expect($sqid->getNumber())->toBe(42);
        });

        it('returns first number from multi-value Sqid', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([10, 20, 30]);

            expect($sqid->getNumber())->toBe(10);
        });

        it('decodes Sqid back to original numbers', function (): void {
            $generator = new SqidGenerator();
            $numbers = [1, 2, 3, 4, 5];
            $sqid = $generator->encode($numbers);

            expect($sqid->decode())->toBe($numbers);
        });

        it('decode returns same as getNumbers', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([100, 200]);

            expect($sqid->decode())->toBe($sqid->getNumbers());
        });

        it('Sqid is never sortable', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([1]);

            expect($sqid->isSortable())->toBeFalse();
        });

        it('Sqid has no timestamp', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([1]);

            expect($sqid->getTimestamp())->toBeNull();
        });
    });

    describe('Edge Cases', function (): void {
        it('returns null for empty numbers array', function (): void {
            // Create a Sqid with empty numbers manually
            $sqid = new Sqid('test', 'test', []);

            expect($sqid->getNumber())->toBeNull();
        });

        it('getNumbers returns empty array when no numbers encoded', function (): void {
            $sqid = new Sqid('test', 'test', []);

            expect($sqid->getNumbers())->toBe([]);
        });

        it('decode returns empty array when no numbers encoded', function (): void {
            $sqid = new Sqid('test', 'test', []);

            expect($sqid->decode())->toBe([]);
        });

        it('handles zero as first number', function (): void {
            $generator = new SqidGenerator();
            $sqid = $generator->encode([0, 1, 2]);

            expect($sqid->getNumber())->toBe(0);
        });

        it('handles large numbers in getNumber', function (): void {
            $generator = new SqidGenerator();
            $largeNumber = 1_000_000_000;
            $sqid = $generator->encode([$largeNumber, 1, 2]);

            expect($sqid->getNumber())->toBe($largeNumber);
        });

        it('decode preserves order of multiple numbers', function (): void {
            $generator = new SqidGenerator();
            $numbers = [5, 10, 15, 20, 25];
            $sqid = $generator->encode($numbers);

            expect($sqid->decode())->toBe($numbers)
                ->and($sqid->decode()[0])->toBe(5)
                ->and($sqid->decode()[4])->toBe(25);
        });
    });
});
