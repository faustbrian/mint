<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Algorithms\Sqids\Sqids;

describe('Sqids Alphabet', function (): void {
    it('encodes with simple alphabet', function (): void {
        $sqids = new Sqids('0123456789abcdef');

        $numbers = [1, 2, 3];
        $id = '489158';

        expect($sqids->encode($numbers))->toBe($id);
        expect($sqids->decode($id))->toBe($numbers);
    });

    it('encodes with short alphabet', function (): void {
        $sqids = new Sqids('abc');

        $numbers = [1, 2, 3];
        expect($sqids->decode($sqids->encode($numbers)))->toBe($numbers);
    });

    it('encodes with long alphabet', function (): void {
        $sqids = new Sqids('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_+|{}[];:\'"/?.>,<`~');

        $numbers = [1, 2, 3];
        expect($sqids->decode($sqids->encode($numbers)))->toBe($numbers);
    });

    it('throws for multibyte characters', function (): void {
        expect(fn (): Sqids => new Sqids('ë1092'))->toThrow(InvalidArgumentException::class);
    });

    it('throws for repeating alphabet characters', function (): void {
        expect(fn (): Sqids => new Sqids('aabcdefg'))->toThrow(InvalidArgumentException::class);
    });

    it('throws for too short alphabet', function (): void {
        expect(fn (): Sqids => new Sqids('ab'))->toThrow(InvalidArgumentException::class);
    });
});
