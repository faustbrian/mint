<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\Cuid2Generator;
use Cline\Mint\Support\Identifiers\Cuid2;

describe('Cuid2Generator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid CUID2 with default length', function (): void {
            $generator = new Cuid2Generator();
            $cuid = $generator->generate();

            expect($cuid)->toBeInstanceOf(Cuid2::class);
            expect(mb_strlen($cuid->toString()))->toBe(24);
        });

        it('generates valid CUID2 with custom length', function (): void {
            $generator = new Cuid2Generator(32);
            $cuid = $generator->generate();

            expect(mb_strlen($cuid->toString()))->toBe(32);
        });

        it('generates unique CUID2s', function (): void {
            $generator = new Cuid2Generator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('generates lowercase alphanumeric CUID2', function (): void {
            $generator = new Cuid2Generator();
            $cuid = $generator->generate();

            expect($cuid->toString())->toMatch('/^[a-z][a-z0-9]+$/');
        });

        it('starts with a letter', function (): void {
            $generator = new Cuid2Generator();

            for ($i = 0; $i < 10; ++$i) {
                $cuid = $generator->generate();
                expect(ctype_alpha($cuid->toString()[0]))->toBeTrue();
            }
        });

        it('parses valid CUID2 string', function (): void {
            $generator = new Cuid2Generator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct CUID2 format', function (): void {
            $generator = new Cuid2Generator();
            $cuid = $generator->generate();

            expect($generator->isValid($cuid->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new Cuid2Generator();
            expect($generator->getName())->toBe('cuid2');
        });

        it('returns null timestamp (not extractable)', function (): void {
            $generator = new Cuid2Generator();
            $cuid = $generator->generate();

            expect($cuid->getTimestamp())->toBeNull();
        });

        it('is not sortable', function (): void {
            $generator = new Cuid2Generator();
            $cuid = $generator->generate();

            expect($cuid->isSortable())->toBeFalse();
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid CUID2 format', function (): void {
            $generator = new Cuid2Generator();
            $generator->parse('INVALID-CUID2-UPPERCASE');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for CUID2 starting with number', function (): void {
            $generator = new Cuid2Generator();
            $generator->parse('1invalid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short string', function (): void {
            $generator = new Cuid2Generator();
            $generator->parse('a');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('handles minimum length (2)', function (): void {
            $generator = new Cuid2Generator(2);
            $cuid = $generator->generate();

            expect(mb_strlen($cuid->toString()))->toBe(2);
        });

        it('handles maximum length (32)', function (): void {
            $generator = new Cuid2Generator(32);
            $cuid = $generator->generate();

            expect(mb_strlen($cuid->toString()))->toBe(32);
        });

        it('converts to bytes correctly', function (): void {
            $generator = new Cuid2Generator();
            $cuid = $generator->generate();

            expect(mb_strlen($cuid->toBytes(), '8bit'))->toBe(24);
        });

        it('handles rapid generation without collisions', function (): void {
            $generator = new Cuid2Generator();
            $ids = [];

            for ($i = 0; $i < 1_000; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(1_000);
        });

        it('returns configured length', function (): void {
            $generator = new Cuid2Generator(16);
            expect($generator->getLength())->toBe(16);
        });
    });
});
