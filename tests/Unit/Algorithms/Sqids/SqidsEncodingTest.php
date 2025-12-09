<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Algorithms\Sqids\Sqids;

describe('Sqids Encoding', function (): void {
    it('encodes simple numbers', function (): void {
        $sqids = new Sqids();

        $numbers = [1, 2, 3];
        $id = '86Rf07';

        expect($sqids->encode($numbers))->toBe($id);
        expect($sqids->decode($id))->toBe($numbers);
    });

    it('encodes different inputs', function (): void {
        $sqids = new Sqids();

        $numbers = [0, 0, 0, 1, 2, 3, 100, 1_000, 100_000, 1_000_000, \PHP_INT_MAX];
        expect($sqids->decode($sqids->encode($numbers)))->toBe($numbers);
    });

    it('encodes incremental numbers', function (): void {
        $sqids = new Sqids();

        $ids = [
            'bM' => [0],
            'Uk' => [1],
            'gb' => [2],
            'Ef' => [3],
            'Vq' => [4],
            'uw' => [5],
            'OI' => [6],
            'AX' => [7],
            'p6' => [8],
            'nJ' => [9],
        ];

        foreach ($ids as $id => $numbers) {
            expect($sqids->encode($numbers))->toBe($id);
            expect($sqids->decode($id))->toBe($numbers);
        }
    });

    it('encodes incremental numbers same index 0', function (): void {
        $sqids = new Sqids();

        $ids = [
            'SvIz' => [0, 0],
            'n3qa' => [0, 1],
            'tryF' => [0, 2],
            'eg6q' => [0, 3],
            'rSCF' => [0, 4],
            'sR8x' => [0, 5],
            'uY2M' => [0, 6],
            '74dI' => [0, 7],
            '30WX' => [0, 8],
            'moxr' => [0, 9],
        ];

        foreach ($ids as $id => $numbers) {
            expect($sqids->encode($numbers))->toBe($id);
            expect($sqids->decode($id))->toBe($numbers);
        }
    });

    it('encodes incremental numbers same index 1', function (): void {
        $sqids = new Sqids();

        $ids = [
            'SvIz' => [0, 0],
            'nWqP' => [1, 0],
            'tSyw' => [2, 0],
            'eX68' => [3, 0],
            'rxCY' => [4, 0],
            'sV8a' => [5, 0],
            'uf2K' => [6, 0],
            '7Cdk' => [7, 0],
            '3aWP' => [8, 0],
            'm2xn' => [9, 0],
        ];

        foreach ($ids as $id => $numbers) {
            expect($sqids->encode($numbers))->toBe($id);
            expect($sqids->decode($id))->toBe($numbers);
        }
    });

    it('encodes multi input', function (): void {
        $sqids = new Sqids();

        $numbers = range(0, 99);
        $output = $sqids->decode($sqids->encode($numbers));
        expect($output)->toBe($numbers);
    });

    it('returns empty string for no numbers', function (): void {
        $sqids = new Sqids();
        expect($sqids->encode([]))->toBe('');
    });

    it('returns empty array for empty string', function (): void {
        $sqids = new Sqids();
        expect($sqids->decode(''))->toBe([]);
    });

    it('returns empty array for invalid character', function (): void {
        $sqids = new Sqids();
        expect($sqids->decode('*'))->toBe([]);
    });

    it('throws for out of range numbers', function (): void {
        $sqids = new Sqids();
        expect(fn (): string => $sqids->encode([-1]))->toThrow(InvalidArgumentException::class);
    });
});
