<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Algorithms\Sqids\Sqids;

describe('Sqids Blocklist', function (): void {
    it('uses default blocklist if no custom param', function (): void {
        $sqids = new Sqids();

        expect($sqids->decode('aho1e'))->toBe([4_572_721]);
        expect($sqids->encode([4_572_721]))->toBe('JExTR');
    });

    it('does not use blocklist if empty param passed', function (): void {
        $sqids = new Sqids('', 0, []);

        expect($sqids->decode('aho1e'))->toBe([4_572_721]);
        expect($sqids->encode([4_572_721]))->toBe('aho1e');
    });

    it('uses only custom blocklist if non-empty param passed', function (): void {
        $sqids = new Sqids('', 0, [
            'ArUO', // originally encoded [100000]
        ]);

        // Make sure we don't use the default blocklist
        expect($sqids->decode('aho1e'))->toBe([4_572_721]);
        expect($sqids->encode([4_572_721]))->toBe('aho1e');

        // Make sure we are using the passed blocklist
        expect($sqids->decode('ArUO'))->toBe([100_000]);
        expect($sqids->encode([100_000]))->toBe('QyG4');
        expect($sqids->decode('QyG4'))->toBe([100_000]);
    });

    it('blocks words in blocklist', function (): void {
        $sqids = new Sqids('', 0, [
            'JSwXFaosAN', // normal result of 1st encoding, let's block that word on purpose
            'OCjV9JK64o', // result of 2nd encoding
            'rBHf', // result of 3rd encoding is `4rBHfOiqd3`, let's block a substring
            '79SM', // result of 4th encoding is `dyhgw479SM`, let's block the postfix
            '7tE6', // result of 4th encoding is `7tE6jdAHLe`, let's block the prefix
        ]);

        expect($sqids->encode([1_000_000, 2_000_000]))->toBe('1aYeB7bRUt');
        expect($sqids->decode('1aYeB7bRUt'))->toBe([1_000_000, 2_000_000]);
    });

    it('decodes blocklist words correctly', function (): void {
        $sqids = new Sqids('', 0, ['86Rf07', 'se8ojk', 'ARsz1p', 'Q8AI49', '5sQRZO']);

        expect($sqids->decode('86Rf07'))->toBe([1, 2, 3]);
        expect($sqids->decode('se8ojk'))->toBe([1, 2, 3]);
        expect($sqids->decode('ARsz1p'))->toBe([1, 2, 3]);
        expect($sqids->decode('Q8AI49'))->toBe([1, 2, 3]);
        expect($sqids->decode('5sQRZO'))->toBe([1, 2, 3]);
    });

    it('matches against short blocklist word', function (): void {
        $sqids = new Sqids('', 0, ['pnd']);
        expect($sqids->decode($sqids->encode([1_000])))->toBe([1_000]);
    });

    it('filters blocklist in constructor', function (): void {
        // lowercase blocklist in only-uppercase alphabet
        $sqids = new Sqids('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 0, ['sxnzkl']);

        $id = $sqids->encode([1, 2, 3]);
        $numbers = $sqids->decode($id);

        expect($id)->toBe('IBSHOZ'); // without blocklist, would've been "SXNZKL"
        expect($numbers)->toBe([1, 2, 3]);
    });

    it('throws on max encoding attempts', function (): void {
        $alphabet = 'abc';
        $minLength = 3;
        $blocklist = ['cab', 'abc', 'bca'];

        $sqids = new Sqids($alphabet, $minLength, $blocklist);

        expect($minLength)->toBe(mb_strlen($alphabet));
        expect(count($blocklist))->toBe(mb_strlen($alphabet));

        expect(fn (): string => $sqids->encode([0]))->toThrow(InvalidArgumentException::class);
    });

    it('handles specific isBlockedId scenarios', function (): void {
        $sqids = new Sqids('', 0, ['hey']);
        expect($sqids->encode([100]))->toBe('86u');

        $sqids = new Sqids('', 0, ['86u']);
        expect($sqids->encode([100]))->toBe('sec');

        $sqids = new Sqids('', 0, ['vFo']);
        expect($sqids->encode([1_000_000]))->toBe('gMvFo');

        $sqids = new Sqids('', 0, ['lP3i']);
        expect($sqids->encode([100, 202, 303, 404]))->toBe('oDqljxrokxRt');

        $sqids = new Sqids('', 0, ['1HkYs']);
        expect($sqids->encode([100, 202, 303, 404]))->toBe('oDqljxrokxRt');

        $sqids = new Sqids('', 0, ['0hfxX']);
        expect($sqids->encode([101, 202, 303, 404, 505, 606, 707]))->toBe('862REt0hfxXVdsLG8vGWD');

        $sqids = new Sqids('', 0, ['hfxX']);
        expect($sqids->encode([101, 202, 303, 404, 505, 606, 707]))->toBe('seu8n1jO9C4KQQDxdOxsK');
    });
});
