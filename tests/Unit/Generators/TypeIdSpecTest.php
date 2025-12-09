<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\TypeIdGenerator;

/**
 * TypeID Specification Tests.
 *
 * These tests verify compliance with the official TypeID specification.
 * Fixtures are from: https://github.com/jetify-com/typeid/tree/main/spec
 */
describe('TypeID Spec Compliance', function (): void {
    beforeEach(function (): void {
        $this->generator = new TypeIdGenerator();
        $this->validFixtures = json_decode(
            file_get_contents(__DIR__.'/../../Fixtures/typeid-valid.json'),
            true,
        );
        $this->invalidFixtures = json_decode(
            file_get_contents(__DIR__.'/../../Fixtures/typeid-invalid.json'),
            true,
        );
    });

    describe('Valid TypeIDs (from spec)', function (): void {
        it('parses all valid spec fixtures', function (): void {
            foreach ($this->validFixtures as $fixture) {
                $typeid = $this->generator->parse($fixture['typeid']);

                expect($typeid->getPrefix())->toBe($fixture['prefix'], 'Failed for: '.$fixture['name']);
                expect($typeid->toUuid())->toBe($fixture['uuid'], 'Failed UUID for: '.$fixture['name']);
            }
        });

        it('validates all valid spec fixtures', function (): void {
            foreach ($this->validFixtures as $fixture) {
                expect($this->generator->isValid($fixture['typeid']))
                    ->toBeTrue(sprintf('Should be valid: %s (%s)', $fixture['name'], $fixture['typeid']));
            }
        });

        it('decodes nil typeid correctly', function (): void {
            $fixture = collect($this->validFixtures)->firstWhere('name', 'nil');
            $typeid = $this->generator->parse($fixture['typeid']);

            expect($typeid->toUuid())->toBe('00000000-0000-0000-0000-000000000000');
        });

        it('decodes max-valid typeid correctly', function (): void {
            $fixture = collect($this->validFixtures)->firstWhere('name', 'max-valid');
            $typeid = $this->generator->parse($fixture['typeid']);

            expect($typeid->toUuid())->toBe('ffffffff-ffff-ffff-ffff-ffffffffffff');
        });

        it('handles valid-alphabet fixture', function (): void {
            $fixture = collect($this->validFixtures)->firstWhere('name', 'valid-alphabet');
            $typeid = $this->generator->parse($fixture['typeid']);

            expect($typeid->getPrefix())->toBe('prefix');
            expect($typeid->toUuid())->toBe('0110c853-1d09-52d8-d73e-1194e95b5f19');
        });

        it('handles prefix with underscore', function (): void {
            $fixture = collect($this->validFixtures)->firstWhere('name', 'prefix-underscore');
            $typeid = $this->generator->parse($fixture['typeid']);

            expect($typeid->getPrefix())->toBe('pre_fix');
            expect($typeid->toUuid())->toBe('00000000-0000-0000-0000-000000000000');
        });
    });

    describe('Invalid TypeIDs (from spec)', function (): void {
        it('rejects all invalid spec fixtures', function (): void {
            foreach ($this->invalidFixtures as $fixture) {
                expect($this->generator->isValid($fixture['typeid']))
                    ->toBeFalse(sprintf('Should be invalid: %s - %s', $fixture['name'], $fixture['description']));
            }
        });

        it('throws on parsing invalid fixtures', function (): void {
            foreach ($this->invalidFixtures as $fixture) {
                $caught = false;

                try {
                    $this->generator->parse($fixture['typeid']);
                } catch (InvalidIdentifierException) {
                    $caught = true;
                }

                expect($caught)->toBeTrue('Should throw for: '.$fixture['name']);
            }
        });

        it('rejects uppercase prefix', function (): void {
            expect($this->generator->isValid('PREFIX_00000000000000000000000000'))->toBeFalse();
        });

        it('rejects numeric prefix', function (): void {
            expect($this->generator->isValid('12345_00000000000000000000000000'))->toBeFalse();
        });

        it('rejects prefix with period', function (): void {
            expect($this->generator->isValid('pre.fix_00000000000000000000000000'))->toBeFalse();
        });

        it('rejects 64-char prefix', function (): void {
            $longPrefix = str_repeat('a', 64);
            expect($this->generator->isValid($longPrefix.'_00000000000000000000000000'))->toBeFalse();
        });

        it('rejects separator with empty prefix', function (): void {
            expect($this->generator->isValid('_00000000000000000000000000'))->toBeFalse();
        });

        it('rejects short suffix', function (): void {
            expect($this->generator->isValid('prefix_1234567890123456789012345'))->toBeFalse();
        });

        it('rejects long suffix', function (): void {
            expect($this->generator->isValid('prefix_123456789012345678901234567'))->toBeFalse();
        });

        it('rejects uppercase suffix', function (): void {
            expect($this->generator->isValid('prefix_0123456789ABCDEFGHJKMNPQRS'))->toBeFalse();
        });

        it('rejects suffix with ambiguous characters (i, l, o, u)', function (): void {
            expect($this->generator->isValid('prefix_ooooooiiiiiiuuuuuuulllllll'))->toBeFalse();
        });

        it('rejects overflow suffix', function (): void {
            expect($this->generator->isValid('prefix_8zzzzzzzzzzzzzzzzzzzzzzzzz'))->toBeFalse();
        });

        it('rejects prefix starting with underscore', function (): void {
            expect($this->generator->isValid('_prefix_00000000000000000000000000'))->toBeFalse();
        });

        it('rejects prefix ending with underscore', function (): void {
            expect($this->generator->isValid('prefix__00000000000000000000000000'))->toBeFalse();
        });

        it('rejects empty string', function (): void {
            expect($this->generator->isValid(''))->toBeFalse();
        });

        it('rejects empty suffix', function (): void {
            expect($this->generator->isValid('prefix_'))->toBeFalse();
        });
    });

    describe('Encoding/Decoding Roundtrip', function (): void {
        it('roundtrips through UUID correctly', function (): void {
            foreach ($this->validFixtures as $fixture) {
                $typeid = $this->generator->parse($fixture['typeid']);
                $uuid = $typeid->toUuid();

                expect($uuid)->toBe($fixture['uuid'], 'Roundtrip failed for: '.$fixture['name']);
            }
        });
    });
});
