<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Conductors\Cuid2Conductor;
use Cline\Mint\Conductors\HashidConductor;
use Cline\Mint\Conductors\KsuidConductor;
use Cline\Mint\Conductors\NanoIdConductor;
use Cline\Mint\Conductors\ObjectIdConductor;
use Cline\Mint\Conductors\PushIdConductor;
use Cline\Mint\Conductors\SnowflakeConductor;
use Cline\Mint\Conductors\SqidConductor;
use Cline\Mint\Conductors\TimeflakeConductor;
use Cline\Mint\Conductors\TypeIdConductor;
use Cline\Mint\Conductors\UlidConductor;
use Cline\Mint\Conductors\UuidConductor;
use Cline\Mint\Conductors\XidConductor;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Cuid2;
use Cline\Mint\Support\Identifiers\Hashid;
use Cline\Mint\Support\Identifiers\Ksuid;
use Cline\Mint\Support\Identifiers\NanoId;
use Cline\Mint\Support\Identifiers\ObjectId;
use Cline\Mint\Support\Identifiers\PushId;
use Cline\Mint\Support\Identifiers\Snowflake;
use Cline\Mint\Support\Identifiers\Sqid;
use Cline\Mint\Support\Identifiers\Timeflake;
use Cline\Mint\Support\Identifiers\TypeId;
use Cline\Mint\Support\Identifiers\Ulid;
use Cline\Mint\Support\Identifiers\Uuid;
use Cline\Mint\Support\Identifiers\Xid;

describe('MintManager', function (): void {
    describe('Conductor Factories', function (): void {
        it('returns UuidConductor', function (): void {
            $manager = new MintManager();
            expect($manager->uuid())->toBeInstanceOf(UuidConductor::class);
        });

        it('returns UlidConductor', function (): void {
            $manager = new MintManager();
            expect($manager->ulid())->toBeInstanceOf(UlidConductor::class);
        });

        it('returns SnowflakeConductor', function (): void {
            $manager = new MintManager();
            expect($manager->snowflake())->toBeInstanceOf(SnowflakeConductor::class);
        });

        it('returns NanoIdConductor', function (): void {
            $manager = new MintManager();
            expect($manager->nanoid())->toBeInstanceOf(NanoIdConductor::class);
        });

        it('returns SqidConductor', function (): void {
            $manager = new MintManager();
            expect($manager->sqid())->toBeInstanceOf(SqidConductor::class);
        });

        it('returns HashidConductor', function (): void {
            $manager = new MintManager();
            expect($manager->hashid())->toBeInstanceOf(HashidConductor::class);
        });

        it('returns KsuidConductor', function (): void {
            $manager = new MintManager();
            expect($manager->ksuid())->toBeInstanceOf(KsuidConductor::class);
        });

        it('returns Cuid2Conductor', function (): void {
            $manager = new MintManager();
            expect($manager->cuid2())->toBeInstanceOf(Cuid2Conductor::class);
        });

        it('returns TypeIdConductor', function (): void {
            $manager = new MintManager();
            expect($manager->typeId())->toBeInstanceOf(TypeIdConductor::class);
        });

        it('returns XidConductor', function (): void {
            $manager = new MintManager();
            expect($manager->xid())->toBeInstanceOf(XidConductor::class);
        });

        it('returns ObjectIdConductor', function (): void {
            $manager = new MintManager();
            expect($manager->objectId())->toBeInstanceOf(ObjectIdConductor::class);
        });

        it('returns PushIdConductor', function (): void {
            $manager = new MintManager();
            expect($manager->pushId())->toBeInstanceOf(PushIdConductor::class);
        });

        it('returns TimeflakeConductor', function (): void {
            $manager = new MintManager();
            expect($manager->timeflake())->toBeInstanceOf(TimeflakeConductor::class);
        });
    });

    describe('UUID via Conductor', function (): void {
        it('generates UUID with default version (v7)', function (): void {
            $manager = new MintManager();
            $uuid = $manager->uuid()->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->getVersion())->toBe(UuidVersion::V7);
        });

        it('generates UUID v1', function (): void {
            $manager = new MintManager();
            $uuid = $manager->uuid()->v1()->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->getVersion())->toBe(UuidVersion::V1);
        });

        it('generates UUID v4', function (): void {
            $manager = new MintManager();
            $uuid = $manager->uuid()->v4()->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->getVersion())->toBe(UuidVersion::V4);
        });

        it('generates UUID v7', function (): void {
            $manager = new MintManager();
            $uuid = $manager->uuid()->v7()->generate();

            expect($uuid)->toBeInstanceOf(Uuid::class);
            expect($uuid->getVersion())->toBe(UuidVersion::V7);
        });

        it('parses UUID string', function (): void {
            $manager = new MintManager();
            $original = $manager->uuid()->v4()->generate();
            $parsed = $manager->uuid()->parse($original->toString());

            expect($parsed->equals($original))->toBeTrue();
        });
    });

    describe('ULID via Conductor', function (): void {
        it('generates ULID', function (): void {
            $manager = new MintManager();
            $ulid = $manager->ulid()->generate();

            expect($ulid)->toBeInstanceOf(Ulid::class);
        });

        it('parses ULID string', function (): void {
            $manager = new MintManager();
            $original = $manager->ulid()->generate();
            $parsed = $manager->ulid()->parse($original->toString());

            expect($parsed->equals($original))->toBeTrue();
        });
    });

    describe('Snowflake via Conductor', function (): void {
        it('generates Snowflake with default node', function (): void {
            $manager = new MintManager();
            $snowflake = $manager->snowflake()->generate();

            expect($snowflake)->toBeInstanceOf(Snowflake::class);
            expect($snowflake->getNodeId())->toBe(0);
        });

        it('generates Snowflake with specific node', function (): void {
            $manager = new MintManager();
            $snowflake = $manager->snowflake()->nodeId(42)->generate();

            expect($snowflake->getNodeId())->toBe(42);
        });

        it('parses Snowflake string', function (): void {
            $manager = new MintManager();
            $original = $manager->snowflake()->generate();
            $parsed = $manager->snowflake()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('uses custom epoch', function (): void {
            $manager = new MintManager();
            $snowflake = $manager->snowflake()->epoch(1_609_459_200_000)->generate();

            expect($snowflake)->toBeInstanceOf(Snowflake::class);
        });
    });

    describe('NanoID via Conductor', function (): void {
        it('generates NanoID with default length', function (): void {
            $manager = new MintManager();
            $nanoid = $manager->nanoid()->generate();

            expect($nanoid)->toBeInstanceOf(NanoId::class);
            expect(mb_strlen($nanoid->toString()))->toBe(21);
        });

        it('generates NanoID with custom length', function (): void {
            $manager = new MintManager();
            $nanoid = $manager->nanoid()->length(10)->generate();

            expect(mb_strlen($nanoid->toString()))->toBe(10);
        });

        it('parses NanoID string', function (): void {
            $manager = new MintManager();
            $original = $manager->nanoid()->generate();
            $parsed = $manager->nanoid()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });

        it('generates with custom alphabet', function (): void {
            $manager = new MintManager();
            $nanoid = $manager->nanoid()->alphabet('0123456789')->generate();

            expect($nanoid->toString())->toMatch('/^\d+$/');
        });
    });

    describe('Sqid via Conductor', function (): void {
        it('encodes numbers to Sqid', function (): void {
            $manager = new MintManager();
            $sqid = $manager->sqid()->encode([1, 2, 3]);

            expect($sqid)->toBeInstanceOf(Sqid::class);
            expect($sqid->decode())->toBe([1, 2, 3]);
        });

        it('encodes single number', function (): void {
            $manager = new MintManager();
            $sqid = $manager->sqid()->encodeNumber(42);

            expect($sqid->decode())->toBe([42]);
        });

        it('parses Sqid string', function (): void {
            $manager = new MintManager();
            $original = $manager->sqid()->encode([1, 2, 3]);
            $parsed = $manager->sqid()->parse($original->toString());

            expect($parsed->decode())->toBe([1, 2, 3]);
        });

        it('generates with custom alphabet', function (): void {
            $manager = new MintManager();
            $sqid = $manager->sqid()->alphabet('abcdefghijklmnopqrstuvwxyz')->encodeNumber(1);

            expect($sqid->toString())->toMatch('/^[a-z]+$/');
        });

        it('generates with minLength', function (): void {
            $manager = new MintManager();
            $sqid = $manager->sqid()->minLength(10)->encodeNumber(1);

            expect(mb_strlen($sqid->toString()))->toBeGreaterThanOrEqual(10);
        });
    });

    describe('Hashid via Conductor', function (): void {
        it('encodes numbers to Hashid', function (): void {
            $manager = new MintManager();
            $hashid = $manager->hashid()->encode([1, 2, 3]);

            expect($hashid)->toBeInstanceOf(Hashid::class);
            expect($hashid->getNumbers())->toBe([1, 2, 3]);
        });

        it('encodes single number', function (): void {
            $manager = new MintManager();
            $hashid = $manager->hashid()->encodeNumber(42);

            expect($hashid->getNumber())->toBe(42);
        });

        it('parses Hashid string', function (): void {
            $manager = new MintManager();
            $original = $manager->hashid()->encode([1, 2, 3]);
            $parsed = $manager->hashid()->parse($original->toString());

            expect($parsed->getNumbers())->toBe([1, 2, 3]);
        });

        it('generates with custom salt', function (): void {
            $manager = new MintManager();
            $hashid1 = $manager->hashid()->salt('salt1')->encodeNumber(1);
            $hashid2 = $manager->hashid()->salt('salt2')->encodeNumber(1);

            expect($hashid1->toString())->not->toBe($hashid2->toString());
        });
    });

    describe('KSUID via Conductor', function (): void {
        it('generates KSUID', function (): void {
            $manager = new MintManager();
            $ksuid = $manager->ksuid()->generate();

            expect($ksuid)->toBeInstanceOf(Ksuid::class);
        });

        it('parses KSUID string', function (): void {
            $manager = new MintManager();
            $original = $manager->ksuid()->generate();
            $parsed = $manager->ksuid()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });
    });

    describe('CUID2 via Conductor', function (): void {
        it('generates CUID2 with default length', function (): void {
            $manager = new MintManager();
            $cuid = $manager->cuid2()->generate();

            expect($cuid)->toBeInstanceOf(Cuid2::class);
            expect(mb_strlen($cuid->toString()))->toBe(24);
        });

        it('generates CUID2 with custom length', function (): void {
            $manager = new MintManager();
            $cuid = $manager->cuid2()->length(32)->generate();

            expect(mb_strlen($cuid->toString()))->toBe(32);
        });

        it('parses CUID2 string', function (): void {
            $manager = new MintManager();
            $original = $manager->cuid2()->generate();
            $parsed = $manager->cuid2()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });
    });

    describe('TypeID via Conductor', function (): void {
        it('generates TypeID with prefix', function (): void {
            $manager = new MintManager();
            $typeid = $manager->typeId()->prefix('user')->generate();

            expect($typeid)->toBeInstanceOf(TypeId::class);
            expect($typeid->getPrefix())->toBe('user');
        });

        it('generates TypeID without prefix', function (): void {
            $manager = new MintManager();
            $typeid = $manager->typeId()->generate();

            expect($typeid->getPrefix())->toBe('');
        });

        it('parses TypeID string', function (): void {
            $manager = new MintManager();
            $original = $manager->typeId()->prefix('user')->generate();
            $parsed = $manager->typeId()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });
    });

    describe('XID via Conductor', function (): void {
        it('generates XID', function (): void {
            $manager = new MintManager();
            $xid = $manager->xid()->generate();

            expect($xid)->toBeInstanceOf(Xid::class);
        });

        it('parses XID string', function (): void {
            $manager = new MintManager();
            $original = $manager->xid()->generate();
            $parsed = $manager->xid()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });
    });

    describe('ObjectID via Conductor', function (): void {
        it('generates ObjectID', function (): void {
            $manager = new MintManager();
            $objectId = $manager->objectId()->generate();

            expect($objectId)->toBeInstanceOf(ObjectId::class);
        });

        it('parses ObjectID string', function (): void {
            $manager = new MintManager();
            $original = $manager->objectId()->generate();
            $parsed = $manager->objectId()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });
    });

    describe('PushID via Conductor', function (): void {
        it('generates PushID', function (): void {
            $manager = new MintManager();
            $pushId = $manager->pushId()->generate();

            expect($pushId)->toBeInstanceOf(PushId::class);
        });

        it('parses PushID string', function (): void {
            $manager = new MintManager();
            $original = $manager->pushId()->generate();
            $parsed = $manager->pushId()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });
    });

    describe('Timeflake via Conductor', function (): void {
        it('generates Timeflake', function (): void {
            $manager = new MintManager();
            $timeflake = $manager->timeflake()->generate();

            expect($timeflake)->toBeInstanceOf(Timeflake::class);
        });

        it('parses Timeflake string', function (): void {
            $manager = new MintManager();
            $original = $manager->timeflake()->generate();
            $parsed = $manager->timeflake()->parse($original->toString());

            expect($parsed->toString())->toBe($original->toString());
        });
    });

    describe('getGenerator Method', function (): void {
        it('returns generator by type', function (): void {
            $manager = new MintManager();

            foreach (IdentifierType::cases() as $type) {
                $generator = $manager->getGenerator($type);
                expect($generator)->toBeInstanceOf(GeneratorInterface::class);
            }
        });
    });

    describe('Generator Caching', function (): void {
        it('caches generator instances', function (): void {
            $manager = new MintManager();

            $first = $manager->uuid()->v4()->generate();
            $second = $manager->uuid()->v4()->generate();

            // Both should work (testing the caching doesn't break functionality)
            expect($first)->toBeInstanceOf(Uuid::class);
            expect($second)->toBeInstanceOf(Uuid::class);
        });

        it('caches generators per configuration', function (): void {
            $manager = new MintManager();

            // Different node IDs should work
            $snowflake1 = $manager->snowflake()->nodeId(1)->generate();
            $snowflake2 = $manager->snowflake()->nodeId(2)->generate();

            expect($snowflake1->getNodeId())->toBe(1);
            expect($snowflake2->getNodeId())->toBe(2);
        });
    });

    describe('Conductor Immutability', function (): void {
        it('uuid conductor is immutable', function (): void {
            $manager = new MintManager();

            $conductor1 = $manager->uuid();
            $conductor2 = $conductor1->v4();

            expect($conductor1)->not->toBe($conductor2);
        });

        it('snowflake conductor is immutable', function (): void {
            $manager = new MintManager();

            $conductor1 = $manager->snowflake();
            $conductor2 = $conductor1->nodeId(42);

            expect($conductor1)->not->toBe($conductor2);
        });

        it('nanoid conductor is immutable', function (): void {
            $manager = new MintManager();

            $conductor1 = $manager->nanoid();
            $conductor2 = $conductor1->length(10);

            expect($conductor1)->not->toBe($conductor2);
        });

        it('sqid conductor is immutable', function (): void {
            $manager = new MintManager();

            $conductor1 = $manager->sqid();
            $conductor2 = $conductor1->minLength(10);

            expect($conductor1)->not->toBe($conductor2);
        });

        it('hashid conductor is immutable', function (): void {
            $manager = new MintManager();

            $conductor1 = $manager->hashid();
            $conductor2 = $conductor1->salt('test');

            expect($conductor1)->not->toBe($conductor2);
        });
    });
});
