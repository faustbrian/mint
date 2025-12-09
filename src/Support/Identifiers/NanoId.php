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

use function mb_strlen;

/**
 * NanoID value object for compact, URL-safe unique identifiers.
 *
 * A modern alternative to UUID offering smaller size with similar collision
 * resistance. Default configuration generates 21-character IDs using a URL-safe
 * alphabet (A-Za-z0-9_-), providing approximately the same collision resistance
 * as UUID v4 but with 46% fewer characters. Supports custom alphabets and
 * configurable lengths for different use cases.
 *
 * Unlike timestamp-based identifiers, NanoIDs are purely random and contain
 * no temporal or sequential information.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/ai/nanoid
 */
final class NanoId extends AbstractIdentifier
{
    /**
     * Get the timestamp component.
     *
     * NanoIDs are purely random and contain no temporal information,
     * so this always returns null.
     */
    #[Override()]
    public function getTimestamp(): ?int
    {
        return null;
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * NanoIDs are random and non-sequential, providing no ordering
     * guarantees for chronological sorting.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return false;
    }

    /**
     * Get the character length of this NanoID.
     *
     * Returns the number of characters in the encoded identifier. Default
     * NanoIDs are 21 characters, but this can vary based on generation
     * configuration.
     */
    public function getLength(): int
    {
        return mb_strlen($this->value);
    }
}
