<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\ObjectIdGenerator;
use Cline\Mint\Support\Identifiers\ObjectId;
use Illuminate\Support\Sleep;

describe('ObjectIdGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid ObjectID', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            expect($objectId)->toBeInstanceOf(ObjectId::class);
            expect(mb_strlen($objectId->toString()))->toBe(24);
        });

        it('generates hex-encoded ObjectID', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            expect($objectId->toString())->toMatch('/^[0-9a-f]{24}$/');
        });

        it('generates unique ObjectIDs', function (): void {
            $generator = new ObjectIdGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('generates sortable ObjectIDs', function (): void {
            $generator = new ObjectIdGenerator();

            $first = $generator->generate();
            Sleep::usleep(1_000);
            $second = $generator->generate();

            expect($first->toString() < $second->toString())->toBeTrue();
        });

        it('parses valid ObjectID string', function (): void {
            $generator = new ObjectIdGenerator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct ObjectID format', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            expect($generator->isValid($objectId->toString()))->toBeTrue();
            expect($generator->isValid('507f1f77bcf86cd799439011'))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new ObjectIdGenerator();
            expect($generator->getName())->toBe('objectid');
        });

        it('extracts timestamp from ObjectID', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            $timestamp = $objectId->getTimestamp();
            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('is sortable', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            expect($objectId->isSortable())->toBeTrue();
        });

        it('generates ObjectId from specific timestamp', function (): void {
            $generator = new ObjectIdGenerator();
            $timestamp = 1_700_000_000; // Fixed timestamp in seconds
            $objectId = $generator->fromTimestamp($timestamp);

            expect($objectId)->toBeInstanceOf(ObjectId::class);
            expect($generator->isValid($objectId->toString()))->toBeTrue();
            expect(mb_strlen($objectId->toString()))->toBe(24);
        });

        it('generates ObjectId with correct timestamp from fromTimestamp', function (): void {
            $generator = new ObjectIdGenerator();
            $timestamp = 1_700_000_000;
            $objectId = $generator->fromTimestamp($timestamp);

            // The timestamp should be extractable (in milliseconds)
            expect($objectId->getTimestamp())->toBe($timestamp * 1_000);
        });

        it('generates different ObjectIds from same timestamp', function (): void {
            $generator = new ObjectIdGenerator();
            $timestamp = 1_700_000_000;

            $id1 = $generator->fromTimestamp($timestamp);
            $id2 = $generator->fromTimestamp($timestamp);

            // Should be different due to counter increment
            expect($id1->toString())->not->toBe($id2->toString());
            // But first 8 hex chars (timestamp) should be same
            expect(mb_substr($id1->toString(), 0, 8))->toBe(mb_substr($id2->toString(), 0, 8));
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid ObjectID format', function (): void {
            $generator = new ObjectIdGenerator();
            $generator->parse('invalid-objectid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short ObjectID', function (): void {
            $generator = new ObjectIdGenerator();
            $generator->parse('507f1f77bcf86cd7994390');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for ObjectID with invalid hex characters', function (): void {
            $generator = new ObjectIdGenerator();
            $generator->parse('507f1f77bcf86cd79943901g');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('converts to bytes correctly', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            expect(mb_strlen($objectId->toBytes(), '8bit'))->toBe(12);
        });

        it('extracts random value', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            $randomValue = $objectId->getRandomValue();
            expect(mb_strlen($randomValue))->toBe(10); // 5 bytes = 10 hex chars
        });

        it('extracts counter', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->generate();

            $counter = $objectId->getCounter();
            expect($counter)->toBeGreaterThanOrEqual(0);
        });

        it('handles rapid generation', function (): void {
            $generator = new ObjectIdGenerator();
            $ids = [];

            for ($i = 0; $i < 1_000; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(1_000);
        });

        it('parses MongoDB example ObjectID', function (): void {
            $generator = new ObjectIdGenerator();
            $objectId = $generator->parse('507f1f77bcf86cd799439011');

            expect($objectId->toString())->toBe('507f1f77bcf86cd799439011');
        });

        it('handles case insensitive parsing', function (): void {
            $generator = new ObjectIdGenerator();
            $lower = $generator->parse('507f1f77bcf86cd799439011');
            $upper = $generator->parse('507F1F77BCF86CD799439011');

            expect($lower->toString())->toBe($upper->toString());
        });
    });
});
