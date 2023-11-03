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

use Pdp\Domain;
use Pdp\Idna;
use Pdp\IdnaInfo;
use Pdp\SyntaxError;
use Rollerworks\Component\PdbSfBridge\PdpManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class DomainNameSuffixValidator extends ConstraintValidator
{
    /** https://tools.ietf.org/html/rfc2606. */
    private const RESERVED_TLDS = [
        'example',
        'invalid',
        'localhost',
        'test',
    ];

    public function __construct(private readonly PdpManager $pdpManager) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! \is_scalar($value)
            && ! $value instanceof Domain
            && ! (\is_object($value) && method_exists($value, '__toString'))
        ) {
            throw new UnexpectedValueException($value, implode('|', ['string', Domain::class]));
        }

        if (! $constraint instanceof DomainNameSuffix) {
            throw new UnexpectedTypeException($constraint, DomainNameSuffix::class);
        }

        if ($value instanceof Domain) {
            $valueStr = $value->toString();
        } else {
            $valueStr = (string) $value;
        }

        try {
            $domainName = Domain::fromIDNA2008($value instanceof Domain ? $value : $valueStr);

            if (str_ends_with($valueStr, '.')) {
                throw SyntaxError::dueToMalformedValue($valueStr);
            }

            $this->validateIdn($valueStr);

            $resolvedDomainName = $this->pdpManager->getPublicSuffixList()->resolve($domainName)->toUnicode();
        } catch (SyntaxError $e) {
            $this->context->buildViolation($constraint->messageInvalidDomainName)
                ->setCode(DomainNameSuffix::INVALID_SYNTAX)
                ->setInvalidValue($value)
                ->setCause($e)
                ->addViolation();

            return;
        }

        if (! $resolvedDomainName->suffix()->isKnown() || \in_array($domainName->label(0), self::RESERVED_TLDS, true)) {
            $this->context->buildViolation($constraint->messageInvalidSuffix)
                ->atPath('suffix')
                ->setCode(\in_array($domainName->label(0), self::RESERVED_TLDS, true) ? DomainNameSuffix::RESERVED_TLD_USED : DomainNameSuffix::UNKNOWN_SUFFIX)
                ->setInvalidValue($value)
                ->addViolation();

            return;
        }

        if ($constraint->requireICANN && ! $resolvedDomainName->suffix()->isICANN()) {
            $this->context->buildViolation($constraint->messageNotICANNSupported)
                ->atPath('suffix')
                ->setCode(DomainNameSuffix::ICANN_UNKNOWN)
                ->setInvalidValue($value)
                ->addViolation();
        }
    }

    private function validateIdn(string $valueStr): void
    {
        if (! str_contains($valueStr, 'xn--')) {
            return;
        }

        /** @param-out array{errors: int, isTransitionalDifferent: bool, result: string} $idnaInfo */
        idn_to_utf8($valueStr, Idna::IDNA2008_UNICODE, \INTL_IDNA_VARIANT_UTS46, $idnaInfo);
        $info = IdnaInfo::fromIntl($idnaInfo);

        if ($info->errors() > 0) {
            throw SyntaxError::dueToIDNAError($valueStr, $info);
        }
    }
}
