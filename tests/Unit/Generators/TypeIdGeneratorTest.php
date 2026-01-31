<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\TypeIdGenerator;
use Cline\Mint\Support\Identifiers\TypeId;
use Illuminate\Support\Sleep;

describe('TypeIdGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid TypeID with prefix', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            expect($typeid)->toBeInstanceOf(TypeId::class);
            expect($typeid->toString())->toStartWith('user_');
        });

        it('generates valid TypeID without prefix', function (): void {
            $generator = new TypeIdGenerator();
            $typeid = $generator->generate();

            expect($typeid)->toBeInstanceOf(TypeId::class);
            expect($typeid->toString())->not->toContain('_');
        });

        it('generates unique TypeIDs', function (): void {
            $generator = new TypeIdGenerator('user');
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('parses valid TypeID string', function (): void {
            $generator = new TypeIdGenerator('user');
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct TypeID format', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            expect($generator->isValid($typeid->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new TypeIdGenerator('user');
            expect($generator->getName())->toBe('typeid');
        });

        it('extracts prefix from TypeID', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            expect($typeid->getPrefix())->toBe('user');
        });

        it('extracts UUID suffix', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            $suffix = $typeid->getSuffix();
            expect(mb_strlen($suffix))->toBe(26); // Base32-encoded UUIDv7
        });

        it('extracts timestamp from TypeID', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            expect($typeid->getTimestamp())->not->toBeNull();
        });

        it('is sortable', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            expect($typeid->isSortable())->toBeTrue();
        });

        it('generates sortable TypeIDs', function (): void {
            $generator = new TypeIdGenerator('user');

            $first = $generator->generate();
            Sleep::usleep(1_000);
            $second = $generator->generate();

            expect($first->toString() < $second->toString())->toBeTrue();
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid TypeID format', function (): void {
            $generator = new TypeIdGenerator('user');
            $generator->parse('invalid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for TypeID with invalid characters', function (): void {
            $generator = new TypeIdGenerator('user');
            $generator->parse('user_INVALID!@#');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('handles empty prefix', function (): void {
            $generator = new TypeIdGenerator('');
            $typeid = $generator->generate();

            expect($typeid->getPrefix())->toBe('');
            expect($typeid->toString())->not->toContain('_');
        });

        it('handles single character prefix', function (): void {
            $generator = new TypeIdGenerator('a');
            $typeid = $generator->generate();

            expect($typeid->toString())->toStartWith('a_');
        });

        it('converts to bytes correctly', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            expect(mb_strlen($typeid->toBytes(), '8bit'))->toBe(16);
        });

        it('converts to UUID', function (): void {
            $generator = new TypeIdGenerator('user');
            $typeid = $generator->generate();

            $uuid = $typeid->toUuid();
            expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
        });

        it('handles prefix with underscores', function (): void {
            $generator = new TypeIdGenerator('user_type');
            $typeid = $generator->generate();

            expect($typeid->getPrefix())->toBe('user_type');
        });

        it('generates with prefix using withPrefix method', function (): void {
            $generator = new TypeIdGenerator();
            $typeid = $generator->withPrefix('order');

            expect($typeid->getPrefix())->toBe('order');
            expect($typeid->toString())->toStartWith('order_');
        });

        it('returns configured prefix', function (): void {
            $generator = new TypeIdGenerator('user');
            expect($generator->getPrefix())->toBe('user');
        });
    });
});
