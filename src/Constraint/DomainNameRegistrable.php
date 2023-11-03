<?php

declare(strict_types=1);

/*
 * This file is part of the Rollerworks PdbValidator package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\PdbValidator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class DomainNameRegistrable extends Constraint
{
    public const INVALID_SYNTAX = '97e31849-cda5-4ec0-8a4f-8066e24a6aae';
    public const NOT_REGISTRABLE = '02247e1c-ed21-420a-829d-0b9a0b3d3c45';
    public const REGISTRABLE_LENGTH_EXCEEDED = 'd539cb2a-7800-4b44-a4ba-cd926f6ebe91';
    public const PRIVATE_SUFFIX = '5c21a871-bbd9-43e0-b048-cdc8ad7bd7c6';

    protected static $errorNames = [
        self::INVALID_SYNTAX => 'INVALID_SYNTAX',
        self::NOT_REGISTRABLE => 'NOT_REGISTRABLE',
        self::REGISTRABLE_LENGTH_EXCEEDED => 'REGISTRABLE_LENGTH_EXCEEDED',
        self::PRIVATE_SUFFIX => 'PRIVATE_SUFFIX',
    ];

    /**
     * @param array<string, mixed>    $options
     * @param array<int, string>|null $groups
     */
    public function __construct(
        array $options = [],
        array $groups = null,
        mixed $payload = null,
        public bool $allowPrivate = false,
        public string $message = 'This value is not a registrable domain name.',
        public string $registrableLengthMessage = 'This value exceeds the registrable "{{ registrable }}" part of the domain-name.',
        public string $privateSuffixMessage = 'This value contains a domain-name suffix that is not publicly registrable.',
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function getTargets(): array | string
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }

    public function getDefaultOption(): string
    {
        return 'allowPrivate';
    }
}
