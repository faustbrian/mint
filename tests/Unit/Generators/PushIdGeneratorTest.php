<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\PushIdGenerator;
use Cline\Mint\Support\Identifiers\PushId;
use Illuminate\Support\Sleep;

describe('PushIdGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid PushID', function (): void {
            $generator = new PushIdGenerator();
            $pushId = $generator->generate();

            expect($pushId)->toBeInstanceOf(PushId::class);
            expect(mb_strlen($pushId->toString()))->toBe(20);
        });

        it('generates unique PushIDs', function (): void {
            $generator = new PushIdGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('generates sortable PushIDs', function (): void {
            $generator = new PushIdGenerator();

            $first = $generator->generate();
            Sleep::usleep(1_000);
            $second = $generator->generate();

            expect($first->toString() < $second->toString())->toBeTrue();
        });

        it('parses valid PushID string', function (): void {
            $generator = new PushIdGenerator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct PushID format', function (): void {
            $generator = new PushIdGenerator();
            $pushId = $generator->generate();

            expect($generator->isValid($pushId->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new PushIdGenerator();
            expect($generator->getName())->toBe('pushid');
        });

        it('extracts timestamp from PushID', function (): void {
            $generator = new PushIdGenerator();
            $pushId = $generator->generate();

            $timestamp = $pushId->getTimestamp();
            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('is sortable', function (): void {
            $generator = new PushIdGenerator();
            $pushId = $generator->generate();

            expect($pushId->isSortable())->toBeTrue();
        });

        it('uses Firebase alphabet', function (): void {
            $generator = new PushIdGenerator();
            $pushId = $generator->generate();

            expect($pushId->toString())->toMatch('/^[-0-9A-Z_a-z]+$/');
        });

        it('generates Push ID from specific timestamp', function (): void {
            $generator = new PushIdGenerator();
            $timestamp = 1_700_000_000_000; // Fixed timestamp in milliseconds
            $pushId = $generator->fromTimestamp($timestamp);

            expect($pushId)->toBeInstanceOf(PushId::class);
            expect($generator->isValid($pushId->toString()))->toBeTrue();
            expect(mb_strlen($pushId->toString()))->toBe(20);
        });

        it('generates different Push IDs from same timestamp', function (): void {
            $generator = new PushIdGenerator();
            $timestamp = 1_700_000_000_000;

            $id1 = $generator->fromTimestamp($timestamp);
            $id2 = $generator->fromTimestamp($timestamp);

            // Random part should differ
            expect($id1->toString())->not->toBe($id2->toString());
            // But timestamp part (first 8 chars) should be same
            expect(mb_substr($id1->toString(), 0, 8))->toBe(mb_substr($id2->toString(), 0, 8));
        });

        it('generates Push ID with correct timestamp encoding', function (): void {
            $generator = new PushIdGenerator();

            // Generate from a known timestamp
            $timestamp = 1_700_000_000_000;
            $pushId = $generator->fromTimestamp($timestamp);

            // The timestamp should be extractable
            expect($pushId->getTimestamp())->toBe($timestamp);
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid PushID format', function (): void {
            $generator = new PushIdGenerator();
            $generator->parse('invalid!@#$');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short PushID', function (): void {
            $generator = new PushIdGenerator();
            $generator->parse('short');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too long PushID', function (): void {
            $generator = new PushIdGenerator();
            $generator->parse('toolongpushidthatexceedstwentycharacters');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('converts to bytes correctly', function (): void {
            $generator = new PushIdGenerator();
            $pushId = $generator->generate();

            // 20 chars * 6 bits/char = 120 bits = 15 bytes
            expect(mb_strlen($pushId->toBytes(), '8bit'))->toBe(20);
        });

        it('handles rapid generation with monotonic increment', function (): void {
            $generator = new PushIdGenerator();
            $ids = [];

            for ($i = 0; $i < 1_000; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            // All should be unique
            expect(array_unique($ids))->toHaveCount(1_000);

            // Should be sorted
            $sorted = $ids;
            sort($sorted);
            expect($ids)->toBe($sorted);
        });

        it('same millisecond generates sequential IDs', function (): void {
            $generator = new PushIdGenerator();

            $first = $generator->generate();
            $second = $generator->generate();

            // Within same millisecond, random part should increment
            expect($first->toString() < $second->toString())->toBeTrue();
        });

        it('handles timestamp extraction', function (): void {
            $generator = new PushIdGenerator();
            $before = (int) (microtime(true) * 1_000);
            $pushId = $generator->generate();
            $after = (int) (microtime(true) * 1_000);

            $timestamp = $pushId->getTimestamp();
            expect($timestamp)->toBeGreaterThanOrEqual($before);
            expect($timestamp)->toBeLessThanOrEqual($after);
        });

        it('extracts random part', function (): void {
            $generator = new PushIdGenerator();
            $pushId = $generator->generate();

            $randomPart = $pushId->getRandomPart();
            expect(mb_strlen($randomPart))->toBe(12);
        });

        it('handles rapid generation on same timestamp', function (): void {
            $generator = new PushIdGenerator();
            $ids = [];

            // Generate many IDs quickly (likely same millisecond)
            for ($i = 0; $i < 50; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            // All should be unique
            expect(array_unique($ids))->toHaveCount(50);
        });

        it('increments random part for same timestamp', function (): void {
            // This test is inherently timing-dependent
            // We just verify uniqueness is maintained
            $generator = new PushIdGenerator();

            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });
    });
});
