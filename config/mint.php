<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Mint\Support\Identifiers\Snowflake;

return [
    /*
    |--------------------------------------------------------------------------
    | Snowflake Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Snowflake ID generator. The epoch is the starting timestamp
    | for Snowflake IDs. The default is Twitter's epoch (Nov 4, 2010).
    | You can set a custom epoch for your application.
    |
    */
    'snowflake' => [
        'epoch' => Snowflake::DEFAULT_EPOCH,
    ],

    /*
    |--------------------------------------------------------------------------
    | NanoID Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the NanoID generator. You can customize the alphabet used
    | for generating IDs. The default is URL-safe characters.
    |
    */
    'nanoid' => [
        'alphabet' => '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sqid Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Sqid generator. You can customize the alphabet,
    | minimum length, and blocklist for generated IDs.
    |
    | See: https://sqids.org/
    |
    */
    'sqid' => [
        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'min_length' => 0,
        'blocklist' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hashid Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Hashids generator. This is the original Hashids library.
    | Salt is used to make IDs unique to your application.
    | Minimum length pads shorter IDs. Alphabet must have 16+ unique chars.
    |
    | See: https://hashids.org/
    |
    */
    'hashid' => [
        'salt' => env('HASHIDS_SALT', ''),
        'min_length' => 0,
        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    ],
];
