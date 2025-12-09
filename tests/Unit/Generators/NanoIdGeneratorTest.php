<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\NanoIdGenerator;
use Cline\Mint\Support\Identifiers\NanoId;

describe('NanoIdGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid NanoID with default length', function (): void {
            $generator = new NanoIdGenerator();
            $nanoid = $generator->generate();

            expect($nanoid)->toBeInstanceOf(NanoId::class);
            expect(mb_strlen($nanoid->toString()))->toBe(21);
        });

        it('generates valid NanoID with custom length', function (): void {
            $generator = new NanoIdGenerator(10);
            $nanoid = $generator->generate();

            expect(mb_strlen($nanoid->toString()))->toBe(10);
        });

        it('generates unique NanoIDs', function (): void {
            $generator = new NanoIdGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('generates URL-safe NanoIDs by default', function (): void {
            $generator = new NanoIdGenerator();
            $nanoid = $generator->generate();

            expect($nanoid->toString())->toMatch('/^[A-Za-z0-9_-]+$/');
        });

        it('generates NanoID with custom alphabet', function (): void {
            $alphabet = '0123456789';
            $generator = new NanoIdGenerator(21, $alphabet);
            $nanoid = $generator->generate();

            expect($nanoid->toString())->toMatch('/^\d+$/');
        });

        it('parses valid NanoID string', function (): void {
            $generator = new NanoIdGenerator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct NanoID format', function (): void {
            $generator = new NanoIdGenerator();

            expect($generator->isValid('V1StGXR8_Z5jdHi6B-myT'))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new NanoIdGenerator();
            expect($generator->getName())->toBe('nanoid');
        });

        it('returns null timestamp (non-time-based)', function (): void {
            $generator = new NanoIdGenerator();
            $nanoid = $generator->generate();

            expect($nanoid->getTimestamp())->toBeNull();
        });

        it('is not sortable', function (): void {
            $generator = new NanoIdGenerator();
            $nanoid = $generator->generate();

            expect($nanoid->isSortable())->toBeFalse();
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid NanoID characters', function (): void {
            $generator = new NanoIdGenerator();
            $generator->parse('invalid id with spaces');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for empty string', function (): void {
            $generator = new NanoIdGenerator();
            $generator->parse('');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('handles minimum length (1)', function (): void {
            $generator = new NanoIdGenerator(1);
            $nanoid = $generator->generate();

            expect(mb_strlen($nanoid->toString()))->toBe(1);
        });

        it('handles large length', function (): void {
            $generator = new NanoIdGenerator(100);
            $nanoid = $generator->generate();

            expect(mb_strlen($nanoid->toString()))->toBe(100);
        });

        it('handles alphabet with special characters', function (): void {
            $alphabet = 'abc!@#$%';
            $generator = new NanoIdGenerator(21, $alphabet);
            $nanoid = $generator->generate();

            expect(mb_strlen($nanoid->toString()))->toBe(21);
        });

        it('converts to bytes correctly', function (): void {
            $generator = new NanoIdGenerator();
            $nanoid = $generator->generate();

            expect(mb_strlen($nanoid->toBytes(), '8bit'))->toBe(21);
        });

        it('creates alphanumeric generator', function (): void {
            $generator = NanoIdGenerator::alphanumeric();
            $nanoid = $generator->generate();

            expect($nanoid->toString())->toMatch('/^[0-9a-z]+$/');
        });

        it('creates numeric generator', function (): void {
            $generator = NanoIdGenerator::numeric();
            $nanoid = $generator->generate();

            expect($nanoid->toString())->toMatch('/^\d+$/');
        });

        it('creates hex generator', function (): void {
            $generator = NanoIdGenerator::hex();
            $nanoid = $generator->generate();

            expect($nanoid->toString())->toMatch('/^[0-9a-f]+$/');
        });
    });
});
