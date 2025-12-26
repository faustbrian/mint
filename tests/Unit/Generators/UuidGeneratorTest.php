<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\UuidGenerator;
use Cline\Mint\Support\Identifiers\Uuid;
use Illuminate\Support\Sleep;

describe('UuidGenerator', function (): void {
    describe('Happy Path', function (): void {
        it('generates valid UUID v1', function (): void {
            $generator = new UuidGenerator(UuidVersion::V1);
            $uuid = $generator->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-1[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
            expect($uuid->getVersion())->toBe(UuidVersion::V1);
        });

        it('generates valid UUID v4', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $uuid = $generator->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
            expect($uuid->getVersion())->toBe(UuidVersion::V4);
        });

        it('generates valid UUID v7', function (): void {
            $generator = new UuidGenerator(UuidVersion::V7);
            $uuid = $generator->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
            expect($uuid->getVersion())->toBe(UuidVersion::V7);
            expect($uuid->getTimestamp())->not->toBeNull();
        });

        it('generates unique UUIDs', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $uuids = [];

            for ($i = 0; $i < 100; ++$i) {
                $uuids[] = $generator->generate()->toString();
            }

            expect(array_unique($uuids))->toHaveCount(100);
        });

        it('parses valid UUID string', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $original = $generator->generate();
            $parsed = $generator->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
            expect($parsed->equals($original))->toBeTrue();
        });

        it('validates correct UUID format', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);

            expect($generator->isValid('550e8400-e29b-41d4-a716-446655440000'))->toBeTrue();
            expect($generator->isValid('550E8400-E29B-41D4-A716-446655440000'))->toBeTrue();
        });

        it('returns correct generator name', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            expect($generator->getName())->toBe('uuid');
        });

        it('converts to bytes correctly', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $uuid = $generator->generate();

            expect(mb_strlen($uuid->toBytes(), '8bit'))->toBe(16);
        });

        it('generates nil UUID', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $nil = $generator->nil();

            expect($nil)->toBeInstanceOf(Uuid::class);
            expect($nil->toString())->toBe('00000000-0000-0000-0000-000000000000');
            expect(mb_strlen($nil->toBytes(), '8bit'))->toBe(16);
        });

        it('generates max UUID', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $max = $generator->max();

            expect($max)->toBeInstanceOf(Uuid::class);
            expect($max->toString())->toBe('ffffffff-ffff-ffff-ffff-ffffffffffff');
            expect(mb_strlen($max->toBytes(), '8bit'))->toBe(16);
        });

        it('generates valid UUID v3 with namespace and name', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V3,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'example.com',
            );
            $uuid = $generator->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
            expect($uuid->getVersion())->toBe(UuidVersion::V3);
        });

        it('generates deterministic UUID v3 for same inputs', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V3,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'example.com',
            );

            $uuid1 = $generator->generate();
            $uuid2 = $generator->generate();

            expect($uuid1->toString())->toBe($uuid2->toString());
        });

        it('generates valid UUID v5 with namespace and name', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V5,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'example.com',
            );
            $uuid = $generator->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
            expect($uuid->getVersion())->toBe(UuidVersion::V5);
        });

        it('generates deterministic UUID v5 for same inputs', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V5,
                namespace: UuidGenerator::NAMESPACE_URL,
                name: 'https://example.com',
            );

            $uuid1 = $generator->generate();
            $uuid2 = $generator->generate();

            expect($uuid1->toString())->toBe($uuid2->toString());
        });

        it('generates valid UUID v6', function (): void {
            $generator = new UuidGenerator(UuidVersion::V6);
            $uuid = $generator->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-6[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
            expect($uuid->getVersion())->toBe(UuidVersion::V6);
            expect($uuid->isSortable())->toBeTrue();
        });

        it('generates valid UUID v8', function (): void {
            $generator = new UuidGenerator(UuidVersion::V8);
            $uuid = $generator->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-8[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
            expect($uuid->getVersion())->toBe(UuidVersion::V8);
        });
    });

    describe('Sad Path', function (): void {
        it('throws exception for invalid UUID format', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $generator->parse('invalid-uuid');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for too short UUID', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $generator->parse('550e8400-e29b-41d4-a716');
        })->throws(InvalidIdentifierException::class);

        it('throws exception for UUID with invalid characters', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $generator->parse('550e8400-e29b-41d4-a716-44665544000g');
        })->throws(InvalidIdentifierException::class);
    });

    describe('Edge Cases', function (): void {
        it('handles nil UUID', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $nilUuid = '00000000-0000-0000-0000-000000000000';

            expect($generator->isValid($nilUuid))->toBeTrue();
        });

        it('handles max UUID', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $maxUuid = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

            expect($generator->isValid($maxUuid))->toBeTrue();
        });

        it('UUID v7 is sortable', function (): void {
            $generator = new UuidGenerator(UuidVersion::V7);

            $first = $generator->generate();
            Sleep::usleep(1_000);
            $second = $generator->generate();

            expect($first->toString() < $second->toString())->toBeTrue();
            expect($first->isSortable())->toBeTrue();
        });

        it('detects version when parsing UUID v1', function (): void {
            $generator = new UuidGenerator(UuidVersion::V1);
            $uuid = $generator->generate();
            $parsed = $generator->parse($uuid->toString());

            expect($parsed->getVersion())->toBe(UuidVersion::V1);
        });

        it('detects version when parsing UUID v3', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V3,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'test',
            );
            $uuid = $generator->generate();
            $parsed = new UuidGenerator()->parse($uuid->toString());

            expect($parsed->getVersion())->toBe(UuidVersion::V3);
        });

        it('detects version when parsing UUID v5', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V5,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'test',
            );
            $uuid = $generator->generate();
            $parsed = new UuidGenerator()->parse($uuid->toString());

            expect($parsed->getVersion())->toBe(UuidVersion::V5);
        });

        it('detects version when parsing UUID v6', function (): void {
            $generator = new UuidGenerator(UuidVersion::V6);
            $uuid = $generator->generate();
            $parsed = new UuidGenerator()->parse($uuid->toString());

            expect($parsed->getVersion())->toBe(UuidVersion::V6);
        });

        it('detects version when parsing UUID v8', function (): void {
            $generator = new UuidGenerator(UuidVersion::V8);
            $uuid = $generator->generate();
            $parsed = new UuidGenerator()->parse($uuid->toString());

            expect($parsed->getVersion())->toBe(UuidVersion::V8);
        });

        it('defaults to v4 for unknown version', function (): void {
            $generator = new UuidGenerator();
            // Parse a UUID with an invalid version nibble (0)
            $parsed = $generator->parse('00000000-0000-0000-0000-000000000000');

            expect($parsed->getVersion())->toBe(UuidVersion::V4);
        });
    });
});
