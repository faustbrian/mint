<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\GeneratorException;
use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\SnowflakeGenerator;
use Cline\Mint\Support\Identifiers\Snowflake;

describe('SnowflakeGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid Snowflake ID', function (): void {
            $generator = new SnowflakeGenerator(1);
            $snowflake = $generator->generate();

            expect($snowflake)->toBeInstanceOf(Snowflake::class);
            expect($snowflake->toString())->toMatch('/^\d+$/');
        });

        it('generates unique Snowflake IDs', function (): void {
            $generator = new SnowflakeGenerator(1);
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('generates monotonically increasing IDs', function (): void {
            $generator = new SnowflakeGenerator(1);

            $first = $generator->generate();
            $second = $generator->generate();
            $third = $generator->generate();

            expect((int) $first->toString() < (int) $second->toString())->toBeTrue();
            expect((int) $second->toString() < (int) $third->toString())->toBeTrue();
        });

        it('parses valid Snowflake ID string', function (): void {
            $generator = new SnowflakeGenerator(1);
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct Snowflake format', function (): void {
            $generator = new SnowflakeGenerator(1);
            $snowflake = $generator->generate();

            expect($generator->isValid($snowflake->toString()))->toBeTrue();
            expect($generator->isValid('1234567890123456789'))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new SnowflakeGenerator(1);
            expect($generator->getName())->toBe('snowflake');
        });

        it('extracts timestamp from Snowflake', function (): void {
            $generator = new SnowflakeGenerator(1);
            $snowflake = $generator->generate();

            $timestamp = $snowflake->getTimestamp();
            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('extracts node ID from Snowflake', function (): void {
            $generator = new SnowflakeGenerator(42);
            $snowflake = $generator->generate();

            expect($snowflake->getNodeId())->toBe(42);
        });

        it('extracts sequence from Snowflake', function (): void {
            $generator = new SnowflakeGenerator(1);
            $snowflake = $generator->generate();

            expect($snowflake->getSequence())->toBeGreaterThanOrEqual(0);
            expect($snowflake->getSequence())->toBeLessThan(4_096);
        });

        it('is sortable', function (): void {
            $generator = new SnowflakeGenerator(1);
            $snowflake = $generator->generate();

            expect($snowflake->isSortable())->toBeTrue();
        });

        it('uses custom epoch', function (): void {
            $customEpoch = 1_609_459_200_000; // 2021-01-01
            $generator = new SnowflakeGenerator(1, $customEpoch);
            $snowflake = $generator->generate();

            expect($snowflake)->toBeInstanceOf(Snowflake::class);
        });

        it('returns configured node ID', function (): void {
            $generator = new SnowflakeGenerator(nodeId: 512);
            expect($generator->getNodeId())->toBe(512);
        });

        it('returns configured epoch', function (): void {
            $customEpoch = 1_609_459_200_000; // 2021-01-01
            $generator = new SnowflakeGenerator(nodeId: 0, epoch: $customEpoch);
            expect($generator->getEpoch())->toBe($customEpoch);
        });

        it('returns default epoch when not specified', function (): void {
            $generator = new SnowflakeGenerator();
            expect($generator->getEpoch())->toBe(Snowflake::DEFAULT_EPOCH);
        });

        it('maintains chronological ordering', function (): void {
            $generator = new SnowflakeGenerator();

            $ids = [];

            for ($i = 0; $i < 10; ++$i) {
                $ids[] = (int) $generator->generate()->toString();
            }

            $counter = count($ids);

            // Each ID should be >= the previous (monotonic)
            for ($i = 1; $i < $counter; ++$i) {
                expect($ids[$i])->toBeGreaterThanOrEqual($ids[$i - 1]);
            }
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid node ID (too high)', function (): void {
            new SnowflakeGenerator(1_024);
        })->throws(GeneratorException::class);

        it('throws exception for invalid node ID (negative)', function (): void {
            new SnowflakeGenerator(-1);
        })->throws(GeneratorException::class);

        it('throws exception for invalid Snowflake string', function (): void {
            $generator = new SnowflakeGenerator(1);
            $generator->parse('invalid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for non-numeric Snowflake', function (): void {
            $generator = new SnowflakeGenerator(1);
            $generator->parse('abc123');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('handles node ID 0', function (): void {
            $generator = new SnowflakeGenerator(0);
            $snowflake = $generator->generate();

            expect($snowflake->getNodeId())->toBe(0);
        });

        it('handles maximum node ID (1023)', function (): void {
            $generator = new SnowflakeGenerator(1_023);
            $snowflake = $generator->generate();

            expect($snowflake->getNodeId())->toBe(1_023);
        });

        it('handles rapid generation within same millisecond', function (): void {
            $generator = new SnowflakeGenerator(1);
            $ids = [];

            for ($i = 0; $i < 1_000; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(1_000);
        });

        it('handles rapid sequential generation', function (): void {
            $generator = new SnowflakeGenerator();
            $ids = [];

            // Generate many IDs quickly
            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            // All should be unique
            expect(array_unique($ids))->toHaveCount(100);

            // All should be valid integers
            foreach ($ids as $id) {
                expect($generator->isValid($id))->toBeTrue();
            }
        });

        it('converts to bytes correctly', function (): void {
            $generator = new SnowflakeGenerator(1);
            $snowflake = $generator->generate();

            expect(mb_strlen($snowflake->toBytes(), '8bit'))->toBe(8);
        });
    });
});
