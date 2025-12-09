<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Enums\IdentifierType;

describe('IdentifierType', function (): void {
    describe('isSortable', function (): void {
        it('returns true for sortable types', function (): void {
            expect(IdentifierType::Ulid->isSortable())->toBeTrue();
            expect(IdentifierType::Snowflake->isSortable())->toBeTrue();
            expect(IdentifierType::Ksuid->isSortable())->toBeTrue();
            expect(IdentifierType::TypeId->isSortable())->toBeTrue();
            expect(IdentifierType::Xid->isSortable())->toBeTrue();
            expect(IdentifierType::ObjectId->isSortable())->toBeTrue();
            expect(IdentifierType::PushId->isSortable())->toBeTrue();
            expect(IdentifierType::Timeflake->isSortable())->toBeTrue();
        });

        it('returns false for non-sortable types', function (): void {
            expect(IdentifierType::Uuid->isSortable())->toBeFalse();
            expect(IdentifierType::NanoId->isSortable())->toBeFalse();
            expect(IdentifierType::Sqid->isSortable())->toBeFalse();
            expect(IdentifierType::Hashid->isSortable())->toBeFalse();
            expect(IdentifierType::Cuid2->isSortable())->toBeFalse();
        });
    });

    describe('getLength', function (): void {
        it('returns correct lengths for fixed-length types', function (): void {
            expect(IdentifierType::Uuid->getLength())->toBe(36);
            expect(IdentifierType::Ulid->getLength())->toBe(26);
            expect(IdentifierType::NanoId->getLength())->toBe(21);
            expect(IdentifierType::Ksuid->getLength())->toBe(27);
            expect(IdentifierType::Cuid2->getLength())->toBe(24);
            expect(IdentifierType::Xid->getLength())->toBe(20);
            expect(IdentifierType::ObjectId->getLength())->toBe(24);
            expect(IdentifierType::PushId->getLength())->toBe(20);
            expect(IdentifierType::Timeflake->getLength())->toBe(26);
        });

        it('returns null for variable-length types', function (): void {
            expect(IdentifierType::Snowflake->getLength())->toBeNull();
            expect(IdentifierType::Sqid->getLength())->toBeNull();
            expect(IdentifierType::Hashid->getLength())->toBeNull();
            expect(IdentifierType::TypeId->getLength())->toBeNull();
        });
    });

    describe('getBitSize', function (): void {
        it('returns correct bit sizes', function (): void {
            expect(IdentifierType::Uuid->getBitSize())->toBe(128);
            expect(IdentifierType::Ulid->getBitSize())->toBe(128);
            expect(IdentifierType::Timeflake->getBitSize())->toBe(128);
            expect(IdentifierType::Snowflake->getBitSize())->toBe(64);
            expect(IdentifierType::NanoId->getBitSize())->toBe(126);
            expect(IdentifierType::Sqid->getBitSize())->toBe(0);
            expect(IdentifierType::Hashid->getBitSize())->toBe(0);
            expect(IdentifierType::Ksuid->getBitSize())->toBe(160);
            expect(IdentifierType::Cuid2->getBitSize())->toBe(0);
            expect(IdentifierType::TypeId->getBitSize())->toBe(128);
            expect(IdentifierType::Xid->getBitSize())->toBe(96);
            expect(IdentifierType::ObjectId->getBitSize())->toBe(96);
            expect(IdentifierType::PushId->getBitSize())->toBe(120);
        });
    });

    describe('value', function (): void {
        it('has correct string values', function (): void {
            expect(IdentifierType::Uuid->value)->toBe('uuid');
            expect(IdentifierType::Ulid->value)->toBe('ulid');
            expect(IdentifierType::Snowflake->value)->toBe('snowflake');
            expect(IdentifierType::NanoId->value)->toBe('nanoid');
            expect(IdentifierType::Sqid->value)->toBe('sqid');
            expect(IdentifierType::Hashid->value)->toBe('hashid');
            expect(IdentifierType::Ksuid->value)->toBe('ksuid');
            expect(IdentifierType::Cuid2->value)->toBe('cuid2');
            expect(IdentifierType::TypeId->value)->toBe('typeid');
            expect(IdentifierType::Xid->value)->toBe('xid');
            expect(IdentifierType::ObjectId->value)->toBe('objectid');
            expect(IdentifierType::PushId->value)->toBe('pushid');
            expect(IdentifierType::Timeflake->value)->toBe('timeflake');
        });
    });
});
