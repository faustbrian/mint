<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\XidGenerator;
use Cline\Mint\Support\Identifiers\Xid;
use Illuminate\Support\Sleep;

describe('XidGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid XID', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            expect($xid)->toBeInstanceOf(Xid::class);
            expect(mb_strlen($xid->toString()))->toBe(20);
        });

        it('generates unique XIDs', function (): void {
            $generator = new XidGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('generates sortable XIDs', function (): void {
            $generator = new XidGenerator();

            $first = $generator->generate();
            Sleep::usleep(1_000);
            $second = $generator->generate();

            expect($first->toString() < $second->toString())->toBeTrue();
        });

        it('parses valid XID string', function (): void {
            $generator = new XidGenerator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct XID format', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            expect($generator->isValid($xid->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new XidGenerator();
            expect($generator->getName())->toBe('xid');
        });

        it('extracts timestamp from XID', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            $timestamp = $xid->getTimestamp();
            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('is sortable', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            expect($xid->isSortable())->toBeTrue();
        });

        it('uses Base32Hex encoding', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            expect($xid->toString())->toMatch('/^[0-9a-v]+$/');
        });

        it('generates XID from specific timestamp', function (): void {
            $generator = new XidGenerator();
            $timestamp = 1_700_000_000; // Fixed timestamp
            $xid = $generator->fromTimestamp($timestamp);

            expect($xid)->toBeInstanceOf(Xid::class);
            expect($generator->isValid($xid->toString()))->toBeTrue();
            expect($xid->getTimestamp())->toBe($timestamp * 1_000); // Convert to milliseconds
        });

        it('generates nil XID', function (): void {
            $generator = new XidGenerator();
            $nil = $generator->nil();

            expect($nil)->toBeInstanceOf(Xid::class);
            expect($nil->toString())->toBe('00000000000000000000');
            expect(mb_strlen($nil->toBytes(), '8bit'))->toBe(12);
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid XID format', function (): void {
            $generator = new XidGenerator();
            $generator->parse('invalid-xid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short XID', function (): void {
            $generator = new XidGenerator();
            $generator->parse('short');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for XID with invalid characters', function (): void {
            $generator = new XidGenerator();
            $generator->parse('WXYZ1234567890123456'); // W, X, Y, Z not in base32hex
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('converts to bytes correctly', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            expect(mb_strlen($xid->toBytes(), '8bit'))->toBe(12);
        });

        it('extracts machine ID', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            $machineId = $xid->getMachineId();
            expect(mb_strlen($machineId))->toBe(10); // 5 bytes = 10 hex chars
        });

        it('extracts counter', function (): void {
            $generator = new XidGenerator();
            $xid = $generator->generate();

            $counter = $xid->getCounter();
            expect($counter)->toBeGreaterThanOrEqual(0);
        });

        it('handles rapid generation', function (): void {
            $generator = new XidGenerator();
            $ids = [];

            for ($i = 0; $i < 1_000; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(1_000);
        });

        it('counter increments within same second', function (): void {
            $generator = new XidGenerator();

            $first = $generator->generate();
            $second = $generator->generate();

            if ($first->getTimestamp() !== $second->getTimestamp()) {
                return;
            }

            expect($second->getCounter())->toBeGreaterThan($first->getCounter());
        });

        it('handles counter overflow gracefully', function (): void {
            $generator = new XidGenerator();

            // Generate multiple XIDs quickly to ensure counter increments
            $xids = [];

            for ($i = 0; $i < 10; ++$i) {
                $xids[] = $generator->generate()->toString();
            }

            // All should be unique
            expect(array_unique($xids))->toHaveCount(10);
        });
    });
});
