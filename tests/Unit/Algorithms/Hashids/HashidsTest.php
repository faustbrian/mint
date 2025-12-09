<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Algorithms\Hashids\Hashids;

describe('Hashids', function (): void {
    describe('Validation', function (): void {
        it('throws for small alphabet', function (): void {
            expect(fn (): Hashids => new Hashids('', 0, '1234567890'))->toThrow(InvalidArgumentException::class);
        });

        it('throws for alphabet with space', function (): void {
            expect(fn (): Hashids => new Hashids('', 0, 'a cdefghijklmnopqrstuvwxyz'))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Bad Input', function (): void {
        it('handles bad encode input', function (): void {
            $hashids = new Hashids();

            expect($hashids->encode())->toBe('');
            expect($hashids->encode([]))->toBe('');
            expect($hashids->encode(-1))->toBe('');
            expect($hashids->encode('6B'))->toBe('');
            expect($hashids->encode('123a'))->toBe('');
            expect($hashids->encode(null))->toBe('');
            expect($hashids->encode(['z']))->toBe('');
        });

        it('handles bad decode input', function (): void {
            $hashids = new Hashids();

            expect($hashids->decode(''))->toBe([]);
            expect($hashids->decode('f'))->toBe([]);
        });

        it('handles bad hex input', function (): void {
            $hashids = new Hashids();

            expect($hashids->encodeHex('z'))->toBe('');
            expect($hashids->decodeHex('f'))->toBe('');
        });
    });

    describe('Alphabet', function (): void {
        $alphabets = [
            'cCsSfFhHuUiItT01',
            'abdegjklCFHISTUc',
            'abdegjklmnopqrSF',
            'abdegjklmnopqrvwxyzABDEGJKLMNOPQRVWXYZ1234567890',
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+\\|\'";:/?.>,<{[}]',
            '`~!@#$%^&*()-_=+\\|\'";:/?.>,<{[}]',
            'áàãăâeéèêiíìĩoóòõôơuúùũưyýỳđ',
        ];

        foreach ($alphabets as $alphabet) {
            it('works with alphabet: '.$alphabet, function () use ($alphabet): void {
                $numbers = [1, 2, 3];

                $hashids = new Hashids('', 0, $alphabet);

                $id = $hashids->encode($numbers);
                expect($hashids->decode($id))->toBe($numbers);
            });
        }
    });

    describe('Salt', function (): void {
        $salts = [
            '',
            '0',
            '   ',
            'this is my salt',
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+\\|\'";:/?.>,<{[}]',
            '`~!@#$%^&*()-_=+\\|\'";:/?.>,<{[}]',
            '!áàãăâ eéèê iíìĩ oóòõôơ uúùũư yýỳ đ',
        ];

        foreach ($salts as $salt) {
            it(sprintf("works with salt: '%s'", $salt), function () use ($salt): void {
                $numbers = [1, 2, 3];

                $hashids = new Hashids($salt);

                $id = $hashids->encode($numbers);
                expect($hashids->decode($id))->toBe($numbers);
            });
        }
    });

    describe('Min Length', function (): void {
        $lengths = [0, 1, 10, 999, 1_000];

        foreach ($lengths as $length) {
            it('works with min length: '.$length, function () use ($length): void {
                $numbers = [1, 2, 3];

                $hashids = new Hashids('', $length);

                $id = $hashids->encode($numbers);
                expect($hashids->decode($id))->toBe($numbers);
                expect(mb_strlen($id))->toBeGreaterThanOrEqual($length);
            });
        }
    });

    describe('Encode Types', function (): void {
        it('encodes array of integers', function (): void {
            $numbers = [1, 2, 3];
            $hashids = new Hashids();

            $id = $hashids->encode($numbers);
            $decodedNumbers = $hashids->decode($id);
            expect($hashids->encode($decodedNumbers))->toBe($id);
        });

        it('encodes array of strings', function (): void {
            $hashids = new Hashids();

            $id = $hashids->encode(['1', '2', '3']);
            $decodedNumbers = $hashids->decode($id);
            expect($hashids->encode($decodedNumbers))->toBe($id);
        });

        it('encodes mixed array', function (): void {
            $hashids = new Hashids();

            $id = $hashids->encode(['1', 2, '3']);
            $decodedNumbers = $hashids->decode($id);
            expect($hashids->encode($decodedNumbers))->toBe($id);
        });
    });

    describe('Default Params', function (): void {
        $testCases = [
            ['gY', [0]],
            ['jR', [1]],
            ['R8ZN0', [928_728]],
            ['o2fXhV', [1, 2, 3]],
            ['jRfMcP', [1, 0, 0]],
            ['jQcMcW', [0, 0, 1]],
            ['gYcxcr', [0, 0, 0]],
            ['gLpmopgO6', [1_000_000_000_000]],
            ['lEW77X7g527', [9_007_199_254_740_991]],
            ['BrtltWt2tyt1tvt7tJt2t1tD', [5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5]],
            ['G6XOnGQgIpcVcXcqZ4B8Q8B9y', [10_000_000_000, 0, 0, 0, 999_999_999_999_999]],
            ['5KoLLVL49RLhYkppOplM6piwWNNANny8N', [9_007_199_254_740_991, 9_007_199_254_740_991, 9_007_199_254_740_991]],
            ['BPg3Qx5f8VrvQkS16wpmwIgj9Q4Jsr93gqx', [1_000_000_001, 1_000_000_002, 1_000_000_003, 1_000_000_004, 1_000_000_005]],
            ['1wfphpilsMtNumCRFRHXIDSqT2UPcWf1hZi3s7tN', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]],
        ];

        foreach ($testCases as [$id, $numbers]) {
            it('encodes '.implode(',', $numbers).(' to '.$id), function () use ($id, $numbers): void {
                $hashids = new Hashids();

                $encodedId = $hashids->encode($numbers);
                $decodedNumbers = $hashids->decode($encodedId);

                expect($encodedId)->toBe($id);
                expect($decodedNumbers)->toBe($numbers);
            });
        }
    });

    describe('Custom Params', function (): void {
        $testCases = [
            ['nej1m3d5a6yn875e7gr9kbwpqol02q', [0]],
            ['dw1nqdp92yrajvl9v6k3gl5mb0o8ea', [1]],
            ['onqr0bk58p642wldq14djmw21ygl39', [928_728]],
            ['18apy3wlqkjvd5h1id7mn5ore2d06b', [1, 2, 3]],
            ['o60edky1ng3vl9hbfavwr5pa2q8mb9', [1, 0, 0]],
            ['o60edky1ng3vlqfbfp4wr5pa2q8mb9', [0, 0, 1]],
            ['qek2a08gpl575efrfd7yomj9dwbr63', [0, 0, 0]],
            ['m3d5a6yn875rae8y81a94gr9kbwpqo', [1_000_000_000_000]],
            ['1q3y98ln48w96kpo0wgk314w5mak2d', [9_007_199_254_740_991]],
            ['op7qrcdc3cgc2c0cbcrcoc5clce4d6', [5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5]],
            ['5430bd2jo0lxyfkfjfyojej5adqdy4', [10_000_000_000, 0, 0, 0, 999_999_999_999_999]],
            ['aa5kow86ano1pt3e1aqm239awkt9pk380w9l3q6', [9_007_199_254_740_991, 9_007_199_254_740_991, 9_007_199_254_740_991]],
            ['mmmykr5nuaabgwnohmml6dakt00jmo3ainnpy2mk', [1_000_000_001, 1_000_000_002, 1_000_000_003, 1_000_000_004, 1_000_000_005]],
            ['w1hwinuwt1cbs6xwzafmhdinuotpcosrxaz0fahl', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]],
        ];

        $minLength = 30;
        $salt = 'this is my salt';
        $alphabet = 'xzal86grmb4jhysfoqp3we7291kuct5iv0nd';

        foreach ($testCases as [$id, $numbers]) {
            it('encodes '.implode(',', $numbers).' with custom params', function () use ($id, $numbers, $minLength, $salt, $alphabet): void {
                $hashids = new Hashids($salt, $minLength, $alphabet);

                $encodedId = $hashids->encode($numbers);
                $decodedNumbers = $hashids->decode($encodedId);

                expect($encodedId)->toBe($id);
                expect($decodedNumbers)->toBe($numbers);
                expect(mb_strlen($encodedId))->toBeGreaterThanOrEqual($minLength);
            });
        }
    });

    describe('Default Params Hex', function (): void {
        $testCases = [
            ['wpVL4j9g', 'deadbeef'],
            ['kmP69lB3xv', 'abcdef123456'],
            ['47JWg0kv4VU0G2KBO2', 'ABCDDD6666DDEEEEEEEEE'],
            ['y42LW46J9luq3Xq9XMly', '507f1f77bcf86cd799439011'],
            ['m1rO8xBQNquXmLvmO65BUO9KQmj', 'f00000fddddddeeeee4444444ababab'],
            ['wBlnMA23NLIQDgw7XxErc2mlNyAjpw', 'abcdef123456abcdef123456abcdef123456'],
            ['VwLAoD9BqlT7xn4ZnBXJFmGZ51ZqrBhqrymEyvYLIP199', 'f000000000000000000000000000000000000000000000000000f'],
            ['nBrz1rYyV0C0XKNXxB54fWN0yNvVjlip7127Jo3ri0Pqw', 'fffffffffffffffffffffffffffffffffffffffffffffffffffff'],
        ];

        foreach ($testCases as [$id, $hex]) {
            it('encodes hex '.$hex, function () use ($id, $hex): void {
                $hashids = new Hashids();

                $encodedId = $hashids->encodeHex($hex);
                $decodedHex = $hashids->decodeHex($encodedId);

                expect($encodedId)->toBe($id);
                expect($decodedHex)->toBe(mb_strtolower($hex));
            });
        }
    });

    describe('Custom Params Hex', function (): void {
        $testCases = [
            ['0dbq3jwa8p4b3gk6gb8bv21goerm96', 'deadbeef'],
            ['190obdnk4j02pajjdande7aqj628mr', 'abcdef123456'],
            ['a1nvl5d9m3yo8pj1fqag8p9pqw4dyl', 'ABCDDD6666DDEEEEEEEEE'],
            ['1nvlml93k3066oas3l9lr1wn1k67dy', '507f1f77bcf86cd799439011'],
            ['mgyband33ye3c6jj16yq1jayh6krqjbo', 'f00000fddddddeeeee4444444ababab'],
            ['9mnwgllqg1q2tdo63yya35a9ukgl6bbn6qn8', 'abcdef123456abcdef123456abcdef123456'],
            ['edjrkn9m6o69s0ewnq5lqanqsmk6loayorlohwd963r53e63xmml29', 'f000000000000000000000000000000000000000000000000000f'],
            ['grekpy53r2pjxwyjkl9aw0k3t5la1b8d5r1ex9bgeqmy93eata0eq0', 'fffffffffffffffffffffffffffffffffffffffffffffffffffff'],
        ];

        $minLength = 30;
        $salt = 'this is my salt';
        $alphabet = 'xzal86grmb4jhysfoqp3we7291kuct5iv0nd';

        foreach ($testCases as [$id, $hex]) {
            it(sprintf('encodes hex %s with custom params', $hex), function () use ($id, $hex, $minLength, $salt, $alphabet): void {
                $hashids = new Hashids($salt, $minLength, $alphabet);

                $encodedId = $hashids->encodeHex($hex);
                $decodedHex = $hashids->decodeHex($encodedId);

                expect($encodedId)->toBe($id);
                expect($decodedHex)->toBe(mb_strtolower($hex));
                expect(mb_strlen($encodedId))->toBeGreaterThanOrEqual($minLength);
            });
        }
    });

    describe('Big Numbers', function (): void {
        $testCases = [
            [2_147_483_647, 'ykJWW1g'], // max 32-bit signed integer
            [4_294_967_295, 'j4r6j8Y'], // max 32-bit unsigned integer
            ['9223372036854775807', 'jvNx4BjM5KYjv'], // max 64-bit signed integer
            ['18446744073709551615', 'zXVjmzBamYlqX'], // max 64-bit unsigned integer
        ];

        foreach ($testCases as [$number, $hash]) {
            it('encodes big number '.$number, function () use ($number, $hash): void {
                $hashids = new Hashids('this is my salt');
                $encoded = $hashids->encode($number);
                expect($encoded)->toBe($hash);
            });

            it('decodes to big number '.$number, function () use ($number, $hash): void {
                $hashids = new Hashids('this is my salt');
                $decoded = $hashids->decode($hash);
                expect($decoded[0])->toEqual($number);
            });
        }
    });

    describe('JS Hashids Compatible', function (): void {
        $testCases = [
            ['', 0, 'áàãăâeéèêiíìĩoóòõôơuúùũưyýỳđ', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 'íóuđáìàúãỳăyâôeyiôuĩ'],
            ['世界', 0, 'áàãăâeéèêiíìĩoóòõôơuúùũưyýỳđ', [9_007_199_254_740_991], 'óôòúỳưúoỳééưýy'],
            ['', 0, 'cCsSfFhHuUiItT01', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], '10h10i00s100t010u110C000F1000H0110I1010'],
            ['', 9, '零0一1二2三3四4五5六6七7七8八9九', [4_231], '5三77九58三九'],
        ];

        foreach ($testCases as [$salt, $minHashLength, $alphabet, $numbers, $hash]) {
            it('is compatible with JS hashids for '.$hash, function () use ($salt, $minHashLength, $alphabet, $numbers, $hash): void {
                $hashids = new Hashids($salt, $minHashLength, $alphabet);
                expect($hashids->encode($numbers))->toBe($hash);
            });
        }
    });
});
