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

namespace Rollerworks\Component\PdbValidator\Tests\Constraint;

use Pdp\Domain;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Rollerworks\Component\PdbSfBridge\PdpMockProvider;
use Rollerworks\Component\PdbValidator\Constraint\DomainNameSuffix;
use Rollerworks\Component\PdbValidator\Constraint\DomainNameSuffixValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 *
 * @template-extends ConstraintValidatorTestCase<DomainNameSuffixValidator>
 */
final class DomainNameSuffixValidatorTest extends ConstraintValidatorTestCase
{
    use ConstraintViolationComparatorTrait;

    protected function createValidator(): DomainNameSuffixValidator
    {
        return new DomainNameSuffixValidator(PdpMockProvider::getPdpManager());
    }

    #[Test]
    public function it_ignores_null_and_empty(): void
    {
        $this->validator->validate(null, new DomainNameSuffix());
        $this->assertNoViolation();

        $this->validator->validate('', new DomainNameSuffix(requireICANN: false));
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideIt_accepts_domain_names_with_known_suffixCases')]
    public function it_accepts_domain_names_with_known_suffix(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->assertNoViolation();

        $this->validator->validate(Domain::fromIDNA2008($name), new DomainNameSuffix());
        $this->assertNoViolation();
    }

    /** @return \Generator<int, array{0: string}> */
    public static function provideIt_accepts_domain_names_with_known_suffixCases(): iterable
    {
        yield ['example.com'];
        yield ['example.com'];
        yield ['*.example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    #[Test]
    #[DataProvider('provideIt_accepts_domain_names_with_known_suffix_and_no_icann_requirementCases')]
    public function it_accepts_domain_names_with_known_suffix_and_no_icann_requirement(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix(requireICANN: false));
        $this->assertNoViolation();
    }

    /** @return \Generator<int, array{0: string}> */
    public static function provideIt_accepts_domain_names_with_known_suffix_and_no_icann_requirementCases(): iterable
    {
        yield ['example.com'];
        yield ['example.com'];
        yield ['*.example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['example.github.io']; // While valid, this domain is not registrable. Thus non-ICANN

        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    #[Test]
    #[DataProvider('provideRejectedDomainNames')]
    public function it_rejects_domain_names_with_unknown_suffix(string $name, string $code = DomainNameSuffix::UNKNOWN_SUFFIX): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->buildViolation('This value does not contain a valid domain-name suffix.')
            ->atPath('property.path.suffix')
            ->setCode($code)
            ->setInvalidValue($name)
            ->assertRaised();
    }

    /** @return \Generator<int, array{0: string}|array{0: string, 1: string}> */
    public static function provideRejectedDomainNames(): iterable
    {
        yield ['example.cong'];
        yield ['example.co.urk'];

        // Reserved.
        yield ['example.example', DomainNameSuffix::RESERVED_TLD_USED];
        yield ['example.localhost', DomainNameSuffix::RESERVED_TLD_USED];
        yield ['example.test', DomainNameSuffix::RESERVED_TLD_USED];
    }

    #[Test]
    #[DataProvider('provideRejectedDomainNames')]
    public function it_rejects_domain_names_with_unknown_suffix_and_no_icann(string $name, string $code = DomainNameSuffix::UNKNOWN_SUFFIX): void
    {
        $this->validator->validate($name, new DomainNameSuffix(requireICANN: false));
        $this->buildViolation('This value does not contain a valid domain-name suffix.')
            ->atPath('property.path.suffix')
            ->setCode($code)
            ->setInvalidValue($name)
            ->assertRaised();
    }

    #[Test]
    #[DataProvider('provideIt_rejects_domain_name_when_icann_is_required_but_not_supported_by_domainCases')]
    public function it_rejects_domain_name_when_icann_is_required_but_not_supported_by_domain(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->buildViolation('This value does not contain a domain-name suffix that is supported by ICANN.')
            ->atPath('property.path.suffix')
            ->setCode(DomainNameSuffix::ICANN_UNKNOWN)
            ->setInvalidValue($name)
            ->assertRaised();
    }

    /** @return \Generator<int, array{0: string}> */
    public static function provideIt_rejects_domain_name_when_icann_is_required_but_not_supported_by_domainCases(): iterable
    {
        yield ['example.github.io']; // Private suffix-registration.
    }

    #[Test]
    #[DataProvider('provideIt_rejects_domain_name_when_failed_to_parseCases')]
    public function it_rejects_domain_name_when_failed_to_parse(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->buildViolation('This value is not a valid domain-name.')
            ->setCode(DomainNameSuffix::INVALID_SYNTAX)
            ->setInvalidValue($name)
            ->assertRaised();
    }

    /** @return \Generator<int, array{0: string}> */
    public static function provideIt_rejects_domain_name_when_failed_to_parseCases(): iterable
    {
        yield ['xn--94823482.nl']; // invalid IDN, which is actually thrown during the resolver phase
        yield ['nope.'];
        yield ['.nope'];
    }
}
