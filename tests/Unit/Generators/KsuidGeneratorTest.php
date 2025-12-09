<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\KsuidGenerator;
use Cline\Mint\Support\Identifiers\Ksuid;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Sleep;

describe('KsuidGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid KSUID', function (): void {
            $generator = new KsuidGenerator();
            $ksuid = $generator->generate();

            expect($ksuid)->toBeInstanceOf(Ksuid::class);
            expect(mb_strlen($ksuid->toString()))->toBe(27);
        });

        it('generates unique KSUIDs', function (): void {
            $generator = new KsuidGenerator();
            $ids = [];

            for ($i = 0; $i < 100; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(100);
        });

        it('parses valid KSUID string', function (): void {
            $generator = new KsuidGenerator();
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('validates correct KSUID format', function (): void {
            $generator = new KsuidGenerator();
            $ksuid = $generator->generate();

            expect($generator->isValid($ksuid->toString()))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new KsuidGenerator();
            expect($generator->getName())->toBe('ksuid');
        });

        it('extracts timestamp from KSUID', function (): void {
            $generator = new KsuidGenerator();
            $ksuid = $generator->generate();

            $timestamp = $ksuid->getTimestamp();
            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeGreaterThan(0);
        });

        it('is sortable', function (): void {
            $generator = new KsuidGenerator();
            $ksuid = $generator->generate();

            expect($ksuid->isSortable())->toBeTrue();
        });

        it('uses Base62 encoding', function (): void {
            $generator = new KsuidGenerator();
            $ksuid = $generator->generate();

            expect($ksuid->toString())->toMatch('/^[0-9A-Za-z]+$/');
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid KSUID format', function (): void {
            $generator = new KsuidGenerator();
            $generator->parse('invalid-ksuid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short KSUID', function (): void {
            $generator = new KsuidGenerator();
            $generator->parse('short');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for KSUID with invalid characters', function (): void {
            $generator = new KsuidGenerator();
            $generator->parse('!@#$%^&*()_+{}[]|\\:";\'<>?');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('converts to bytes correctly', function (): void {
            $generator = new KsuidGenerator();
            $ksuid = $generator->generate();

            expect(mb_strlen($ksuid->toBytes(), '8bit'))->toBe(20);
        });

        it('generates KSUID from specific timestamp', function (): void {
            $generator = new KsuidGenerator();
            $timestamp = Date::now()->getTimestamp();

            $ksuid = $generator->fromTimestamp($timestamp);

            // getTimestamp returns milliseconds, so multiply input by 1000
            expect($ksuid->getTimestamp())->toBe($timestamp * 1_000);
        });

        it('handles rapid generation', function (): void {
            $generator = new KsuidGenerator();
            $ids = [];

            for ($i = 0; $i < 1_000; ++$i) {
                $ids[] = $generator->generate()->toString();
            }

            expect(array_unique($ids))->toHaveCount(1_000);
        });

        it('generates minimum KSUID', function (): void {
            $generator = new KsuidGenerator();
            $min = $generator->min();

            expect(mb_strlen($min->toString()))->toBe(27);
        });

        it('generates maximum KSUID', function (): void {
            $generator = new KsuidGenerator();
            $max = $generator->max();

            expect(mb_strlen($max->toString()))->toBe(27);
        });

        it('KSUIDs from later timestamp sort after earlier', function (): void {
            $generator = new KsuidGenerator();
            $first = $generator->fromTimestamp(Date::now()->getTimestamp());
            Sleep::sleep(1);
            $second = $generator->fromTimestamp(Date::now()->getTimestamp());

            expect($first->toString() < $second->toString())->toBeTrue();
        });
    });
});
