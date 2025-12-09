<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\XidAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Exceptions\InvalidXidFormatException;
use Cline\Mint\Support\Identifiers\Xid;
use Override;

use function str_repeat;

/**
 * XID generator.
 *
 * Generates 96-bit (12 byte) globally unique identifiers based on
 * the MongoDB ObjectID algorithm with base32hex encoding.
 *
 * Structure:
 * - 4 bytes: timestamp (seconds since Unix epoch)
 * - 5 bytes: machine/process identifier
 * - 3 bytes: counter
 *
 * Encoded as 20 base32hex characters.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class XidGenerator implements GeneratorInterface
{
    /**
     * The underlying XID algorithm instance.
     */
    private XidAlgorithm $algorithm;

    /**
     * Create a new XID generator instance.
     */
    public function __construct()
    {
        $this->algorithm = new XidAlgorithm();
    }

    /**
     * Generate a new XID with current timestamp.
     *
     * Creates a globally unique identifier combining the current Unix timestamp
     * (in seconds), a machine/process identifier, and an incrementing counter.
     * The result is encoded as 20 base32hex characters for URL-safe usage.
     *
     * @return Xid A new XID value object with timestamp, machine ID, and counter
     */
    #[Override()]
    public function generate(): Xid
    {
        $data = $this->algorithm->generate();

        return new Xid($data['value'], $data['bytes']);
    }

    /**
     * Generate an XID from a specific Unix timestamp.
     *
     * Useful for creating XIDs that represent a specific point in time,
     * such as for testing or backfilling historical data. The machine ID
     * and counter are still generated/incremented normally.
     *
     * @param int $timestamp Unix timestamp in seconds (not milliseconds)
     *
     * @return Xid An XID value object with the specified timestamp
     */
    public function fromTimestamp(int $timestamp): Xid
    {
        $data = $this->algorithm->generateFromTimestamp($timestamp);

        return new Xid($data['value'], $data['bytes']);
    }

    /**
     * Parse an XID string into an Xid value object.
     *
     * Decodes the base32hex string back into binary representation and
     * creates a value object. The timestamp and other components can be
     * extracted from the returned Xid object.
     *
     * @param string $value The XID string to parse (20 base32hex characters)
     *
     * @throws InvalidXidFormatException When the string doesn't match XID format
     * @return Xid                       The parsed XID value object
     */
    #[Override()]
    public function parse(string $value): Xid
    {
        $data = $this->algorithm->parse($value);

        return new Xid($data['value'], $data['bytes']);
    }

    /**
     * Validate whether a string matches XID format.
     *
     * Checks for correct length (20 characters) and valid base32hex alphabet
     * (0-9 and a-v). Case-insensitive validation.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid XID format, false otherwise
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator identifier name.
     *
     * @return string The string 'xid' identifying this generator type
     */
    #[Override()]
    public function getName(): string
    {
        return 'xid';
    }

    /**
     * Generate a nil XID representing the zero value.
     *
     * Returns an XID with all bytes set to zero, useful as a null placeholder
     * or default value. The timestamp will be Unix epoch (1970-01-01).
     *
     * @return Xid The nil XID value object with all bits set to zero
     */
    public function nil(): Xid
    {
        $byteLength = $this->algorithm->getByteLength();
        $stringLength = $this->algorithm->getStringLength();

        $bytes = str_repeat("\x00", $byteLength);
        $value = str_repeat('0', $stringLength);

        return new Xid($value, $bytes);
    }
}
