<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Identifiers;

use Cline\Mint\Support\AbstractIdentifier;
use Override;

use function array_search;
use function mb_str_split;
use function mb_substr;

/**
 * Firebase Push ID value object for realtime database keys.
 *
 * A 120-bit identifier designed specifically for Firebase Realtime Database,
 * optimized for chronological ordering and distributed generation without
 * coordination. Encoded as 20 characters using a custom base64-like alphabet
 * that sorts correctly lexicographically.
 *
 * Structure:
 * - 8 characters: timestamp (milliseconds since Unix epoch, base64-encoded)
 * - 12 characters: cryptographically random data
 *
 * The timestamp-first design ensures that Push IDs naturally sort in
 * chronological order, which optimizes Firebase's tree-based data structure
 * and query performance.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://firebase.blog/posts/2015/02/the-2120-ways-to-ensure-unique_68
 */
final class PushId extends AbstractIdentifier
{
    /**
     * Custom alphabet for base64-like encoding with correct lexicographic ordering.
     *
     * This 64-character alphabet is specifically ordered so that encoded
     * timestamps sort correctly as strings, ensuring Push IDs maintain
     * chronological order when sorted lexicographically.
     */
    public const string ALPHABET = '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

    /**
     * Get the timestamp component in milliseconds since Unix epoch.
     *
     * Decodes the first 8 characters from the custom base64-like encoding
     * to extract the original millisecond timestamp. Each character
     * represents a base-64 digit in the timestamp value.
     */
    #[Override()]
    public function getTimestamp(): int
    {
        $timestampPart = mb_substr($this->value, 0, 8);
        $chars = mb_str_split(self::ALPHABET);

        $timestamp = 0;

        for ($i = 0; $i < 8; ++$i) {
            $char = mb_substr($timestampPart, $i, 1);
            $index = array_search($char, $chars, true);
            $timestamp = $timestamp * 64 + (int) $index;
        }

        return $timestamp;
    }

    /**
     * Get the random component of the Push ID.
     *
     * Returns the 12-character random suffix that ensures uniqueness when
     * multiple Push IDs are generated within the same millisecond across
     * distributed clients.
     */
    public function getRandomPart(): string
    {
        return mb_substr($this->value, 8, 12);
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * Push IDs are inherently sortable, with lexicographic string ordering
     * matching chronological order due to the timestamp-first structure and
     * specially ordered alphabet.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }
}
