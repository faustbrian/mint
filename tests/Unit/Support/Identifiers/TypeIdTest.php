<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Generators\TypeIdGenerator;
use Cline\Mint\Support\Identifiers\TypeId;

describe('TypeId', function (): void {
    describe('Happy Path', function (): void {
        it('parses TypeId string with prefix', function (): void {
            $result = TypeId::parseString('user_01h455vb4pex5vsknk084sn02q');

            expect($result)->toBe([
                'prefix' => 'user',
                'suffix' => '01h455vb4pex5vsknk084sn02q',
            ]);
        });

        it('returns prefix from TypeId', function (): void {
            $generator = new TypeIdGenerator(prefix: 'user');
            $typeId = $generator->generate();

            expect($typeId->getPrefix())->toBe('user');
        });

        it('returns suffix from TypeId', function (): void {
            $generator = new TypeIdGenerator(prefix: 'user');
            $typeId = $generator->generate();

            expect($typeId->getSuffix())->toHaveLength(26);
        });

        it('converts TypeId to UUID format', function (): void {
            $generator = new TypeIdGenerator(prefix: 'user');
            $typeId = $generator->generate();

            $uuid = $typeId->toUuid();
            expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
        });

        it('TypeId is always sortable', function (): void {
            $generator = new TypeIdGenerator(prefix: 'user');
            $typeId = $generator->generate();

            expect($typeId->isSortable())->toBeTrue();
        });

        it('TypeId has extractable timestamp', function (): void {
            $generator = new TypeIdGenerator(prefix: 'user');
            $typeId = $generator->generate();

            $timestamp = $typeId->getTimestamp();
            $now = (int) (microtime(true) * 1_000);

            expect($timestamp)->toBeInt();
            expect($timestamp)->toBeGreaterThan($now - 5_000);
            expect($timestamp)->toBeLessThan($now + 5_000);
        });
    });

    describe('Edge Cases', function (): void {
        it('parses TypeId string without prefix', function (): void {
            $result = TypeId::parseString('01h455vb4pex5vsknk084sn02q');

            expect($result)->toBe([
                'prefix' => '',
                'suffix' => '01h455vb4pex5vsknk084sn02q',
            ]);
        });

        it('parses TypeId string with multiple underscores', function (): void {
            $result = TypeId::parseString('user_type_01h455vb4pex5vsknk084sn02q');

            expect($result)->toBe([
                'prefix' => 'user',
                'suffix' => 'type_01h455vb4pex5vsknk084sn02q',
            ]);
        });

        it('handles empty prefix in TypeId', function (): void {
            $generator = new TypeIdGenerator(prefix: '');
            $typeId = $generator->generate();

            expect($typeId->getPrefix())->toBe('');
        });

        it('TypeId without prefix has no underscore', function (): void {
            $generator = new TypeIdGenerator();
            $typeId = $generator->generate();

            expect($typeId->toString())->not->toContain('_');
        });
    });
});
