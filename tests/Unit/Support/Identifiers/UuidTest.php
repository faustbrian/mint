<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\Generators\UuidGenerator;

describe('Uuid', function (): void {
    describe('Happy Path', function (): void {
        it('extracts timestamp from UUID v1', function (): void {
            $generator = new UuidGenerator(UuidVersion::V1);
            $uuid = $generator->generate();

            $timestamp = $uuid->getTimestamp();

            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeInt();
            // Timestamp should be close to now (within last few seconds)
            $now = (int) (microtime(true) * 1_000);
            expect($timestamp)->toBeGreaterThan($now - 5_000);
            expect($timestamp)->toBeLessThan($now + 5_000);
        });

        it('extracts timestamp from UUID v6', function (): void {
            $generator = new UuidGenerator(UuidVersion::V6);
            $uuid = $generator->generate();

            $timestamp = $uuid->getTimestamp();

            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeInt();
            // Timestamp should be close to now (within last few seconds)
            $now = (int) (microtime(true) * 1_000);
            expect($timestamp)->toBeGreaterThan($now - 5_000);
            expect($timestamp)->toBeLessThan($now + 5_000);
        });

        it('extracts timestamp from UUID v7', function (): void {
            $generator = new UuidGenerator(UuidVersion::V7);
            $uuid = $generator->generate();

            $timestamp = $uuid->getTimestamp();

            expect($timestamp)->not->toBeNull();
            expect($timestamp)->toBeInt();
            // Timestamp should be close to now (within last few seconds)
            $now = (int) (microtime(true) * 1_000);
            expect($timestamp)->toBeGreaterThan($now - 5_000);
            expect($timestamp)->toBeLessThan($now + 5_000);
        });

        it('v1 is sortable', function (): void {
            $generator = new UuidGenerator(UuidVersion::V1);
            $uuid = $generator->generate();
            expect($uuid->isSortable())->toBeTrue();
        });

        it('v6 is sortable', function (): void {
            $generator = new UuidGenerator(UuidVersion::V6);
            $uuid = $generator->generate();
            expect($uuid->isSortable())->toBeTrue();
        });

        it('v7 is sortable', function (): void {
            $generator = new UuidGenerator(UuidVersion::V7);
            $uuid = $generator->generate();
            expect($uuid->isSortable())->toBeTrue();
        });

        it('returns correct UUID version', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $uuid = $generator->generate();

            expect($uuid->getVersion())->toBe(UuidVersion::V4);
        });
    });

    describe('Edge Cases', function (): void {
        it('returns null timestamp for UUID v3', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V3,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'test',
            );
            $uuid = $generator->generate();

            expect($uuid->getTimestamp())->toBeNull();
        });

        it('returns null timestamp for UUID v4', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $uuid = $generator->generate();

            expect($uuid->getTimestamp())->toBeNull();
        });

        it('returns null timestamp for UUID v5', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V5,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'test',
            );
            $uuid = $generator->generate();

            expect($uuid->getTimestamp())->toBeNull();
        });

        it('returns null timestamp for UUID v8', function (): void {
            $generator = new UuidGenerator(UuidVersion::V8);
            $uuid = $generator->generate();

            expect($uuid->getTimestamp())->toBeNull();
        });

        it('v3 is not sortable', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V3,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'test',
            );
            $uuid = $generator->generate();
            expect($uuid->isSortable())->toBeFalse();
        });

        it('v4 is not sortable', function (): void {
            $generator = new UuidGenerator(UuidVersion::V4);
            $uuid = $generator->generate();
            expect($uuid->isSortable())->toBeFalse();
        });

        it('v5 is not sortable', function (): void {
            $generator = new UuidGenerator(
                version: UuidVersion::V5,
                namespace: UuidGenerator::NAMESPACE_DNS,
                name: 'test',
            );
            $uuid = $generator->generate();
            expect($uuid->isSortable())->toBeFalse();
        });

        it('v8 is not sortable', function (): void {
            $generator = new UuidGenerator(UuidVersion::V8);
            $uuid = $generator->generate();
            expect($uuid->isSortable())->toBeFalse();
        });

        it('v1 timestamps are consistent across multiple generations', function (): void {
            $generator = new UuidGenerator(UuidVersion::V1);
            $uuid1 = $generator->generate();
            $uuid2 = $generator->generate();

            $timestamp1 = $uuid1->getTimestamp();
            $timestamp2 = $uuid2->getTimestamp();

            expect($timestamp1)->not->toBeNull();
            expect($timestamp2)->not->toBeNull();
            // Both timestamps should be within milliseconds of each other
            expect(abs($timestamp1 - $timestamp2))->toBeLessThan(100);
        });

        it('v6 timestamps are consistent across multiple generations', function (): void {
            $generator = new UuidGenerator(UuidVersion::V6);
            $uuid1 = $generator->generate();
            $uuid2 = $generator->generate();

            $timestamp1 = $uuid1->getTimestamp();
            $timestamp2 = $uuid2->getTimestamp();

            expect($timestamp1)->not->toBeNull();
            expect($timestamp2)->not->toBeNull();
            // Both timestamps should be within milliseconds of each other
            expect(abs($timestamp1 - $timestamp2))->toBeLessThan(100);
        });

        it('v7 timestamps are consistent across multiple generations', function (): void {
            $generator = new UuidGenerator(UuidVersion::V7);
            $uuid1 = $generator->generate();
            $uuid2 = $generator->generate();

            $timestamp1 = $uuid1->getTimestamp();
            $timestamp2 = $uuid2->getTimestamp();

            expect($timestamp1)->not->toBeNull();
            expect($timestamp2)->not->toBeNull();
            // Both timestamps should be within milliseconds of each other
            expect(abs($timestamp1 - $timestamp2))->toBeLessThan(100);
        });
    });
});
