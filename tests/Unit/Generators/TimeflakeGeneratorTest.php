<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\TimeflakeGenerator;
use Cline\Mint\Support\Identifiers\Timeflake;
use Illuminate\Support\Sleep;

describe('TimeflakeGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid Timeflake', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generate();

            expect($timeflake)->toBeInstanceOf(Timeflake::class);
            expect(mb_strlen($timeflake->toString()))->toBe(22);
        });

        it('generates unique Timeflakes', function (): void {
            $generator = new TimeflakeGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('generates sortable Timeflakes', function (): void {
            $generator = new TimeflakeGenerator();

            $first = $generator->generate();
            Sleep::usleep(1_000);
            $second = $generator->generate();

            expect($first->toString() < $second->toString())->toBeTrue();
        });

        it('parses valid Timeflake string', function (): void {
            $generator = new TimeflakeGenerator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct Timeflake format', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generate();

            expect($generator->isValid($timeflake->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new TimeflakeGenerator();
            expect($generator->getName())->toBe('timeflake');
        });

        it('extracts timestamp from Timeflake', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generate();

            $timestamp = $timeflake->getTimestamp();
            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('is sortable', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generate();

            expect($timeflake->isSortable())->toBeTrue();
        });

        it('uses Base62 encoding', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generate();

            expect($timeflake->toString())->toMatch('/^[0-9A-Za-z]+$/');
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid Timeflake format', function (): void {
            $generator = new TimeflakeGenerator();
            $generator->parse('invalid!@#$%');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short Timeflake', function (): void {
            $generator = new TimeflakeGenerator();
            $generator->parse('short');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('converts to bytes correctly', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generate();

            expect(mb_strlen($timeflake->toBytes(), '8bit'))->toBe(16);
        });

        it('generates hex format', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generateHex();

            expect(mb_strlen($timeflake->toString()))->toBe(32);
            expect($timeflake->toString())->toMatch('/^[0-9a-f]{32}$/');
        });

        it('parses hex format', function (): void {
            $generator = new TimeflakeGenerator();
            $original = $generator->generateHex();
            $parsed = $generator->parse($original->toString());

            expect(mb_strlen($parsed->toBytes(), '8bit'))->toBe(16);
        });

        it('generates from specific timestamp', function (): void {
            $generator = new TimeflakeGenerator();
            $timestamp = 1_609_459_200_000; // 2021-01-01 00:00:00 UTC in ms

            $timeflake = $generator->fromTimestamp($timestamp);

            expect($timeflake->getTimestamp())->toBe($timestamp);
        });

        it('handles rapid generation', function (): void {
            $generator = new TimeflakeGenerator();
            $ids = [];

            for ($i = 0; $i < 1_000; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(1_000);
        });

        it('converts to UUID format', function (): void {
            $generator = new TimeflakeGenerator();
            $timeflake = $generator->generate();

            $uuid = $timeflake->toUuid();
            expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
        });
    });
});
