<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\UlidGenerator;
use Cline\Mint\Support\Identifiers\Ulid;

describe('UlidGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid ULID', function (): void {
            $generator = new UlidGenerator();
            $ulid = $generator->generate();

            expect($ulid)->toBeInstanceOf(Ulid::class);
            expect($ulid->toString())->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
            expect(mb_strlen($ulid->toString()))->toBe(26);
        });

        it('generates unique ULIDs', function (): void {
            $generator = new UlidGenerator();
            $ulids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ulids[] = $generator->generate()->toString();
            }

            expect(array_unique($ulids))->toHaveCount(100);
        });

        it('generates monotonic ULIDs', function (): void {
            $generator = new UlidGenerator();

            $first = $generator->generate();
            $second = $generator->generate();
            $third = $generator->generate();

            expect($first->toString() < $second->toString())->toBeTrue();
            expect($second->toString() < $third->toString())->toBeTrue();
        });

        it('parses valid ULID string', function (): void {
            $generator = new UlidGenerator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
            expect($parsed->equals($original))->toBeTrue();
        });

        it('validates correct ULID format', function (): void {
            $generator = new UlidGenerator();

            expect($generator->isValid('01ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new UlidGenerator();
            expect($generator->getName())->toBe('ulid');
        });

        it('extracts timestamp from ULID', function (): void {
            $generator = new UlidGenerator();
            $ulid = $generator->generate();

            $timestamp = $ulid->getTimestamp();
            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('converts to bytes correctly', function (): void {
            $generator = new UlidGenerator();
            $ulid = $generator->generate();

            expect(mb_strlen($ulid->toBytes(), '8bit'))->toBe(16);
        });

        it('is sortable', function (): void {
            $generator = new UlidGenerator();
            $ulid = $generator->generate();

            expect($ulid->isSortable())->toBeTrue();
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid ULID format', function (): void {
            $generator = new UlidGenerator();
            $generator->parse('invalid-ulid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short ULID', function (): void {
            $generator = new UlidGenerator();
            $generator->parse('01ARZ3NDEKTSV4RRFFQ69G5');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for ULID with invalid characters', function (): void {
            $generator = new UlidGenerator();
            $generator->parse('01ARZ3NDEKTSV4RRFFQ69G5FAI'); // I is invalid in Crockford
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('handles minimum ULID', function (): void {
            $generator = new UlidGenerator();
            $minUlid = '00000000000000000000000000';

            expect($generator->isValid($minUlid))->toBeTrue();
        });

        it('handles maximum ULID', function (): void {
            $generator = new UlidGenerator();
            $maxUlid = '7ZZZZZZZZZZZZZZZZZZZZZZZZZ';

            expect($generator->isValid($maxUlid))->toBeTrue();
        });

        it('generates ULID from specific timestamp', function (): void {
            $generator = new UlidGenerator();
            $timestamp = 1_609_459_200_000; // 2021-01-01 00:00:00 UTC

            $ulid = $generator->fromTimestamp($timestamp);

            expect($ulid->getTimestamp())->toBe($timestamp);
        });

        it('converts to UUID format', function (): void {
            $generator = new UlidGenerator();
            $ulid = $generator->generate();

            $uuid = $ulid->toUuid();
            expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
        });
    });
});
