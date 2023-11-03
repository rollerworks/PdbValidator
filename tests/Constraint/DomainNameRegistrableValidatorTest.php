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
use Rollerworks\Component\PdbValidator\Constraint\DomainNameRegistrable;
use Rollerworks\Component\PdbValidator\Constraint\DomainNameRegistrableValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 *
 * @template-extends ConstraintValidatorTestCase<DomainNameRegistrableValidator>
 */
final class DomainNameRegistrableValidatorTest extends ConstraintValidatorTestCase
{
    use ConstraintViolationComparatorTrait;

    protected function createValidator(): DomainNameRegistrableValidator
    {
        return new DomainNameRegistrableValidator(PdpMockProvider::getPdpManager());
    }

    #[Test]
    public function it_ignores_null_and_empty(): void
    {
        $this->validator->validate(null, new DomainNameRegistrable());
        $this->assertNoViolation();

        $this->validator->validate('', new DomainNameRegistrable(allowPrivate: true));
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideIt_accepts_domain_names_with_known_suffixCases')]
    public function it_accepts_domain_names_with_known_suffix(string $name): void
    {
        $this->validator->validate($name, new DomainNameRegistrable());
        $this->assertNoViolation();

        $this->validator->validate(Domain::fromIDNA2008($name), new DomainNameRegistrable());
        $this->assertNoViolation();
    }

    /**
     * @return \Generator<int, array{0: string}>
     */
    public static function provideIt_accepts_domain_names_with_known_suffixCases(): iterable
    {
        yield ['example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    #[Test]
    #[DataProvider('provideIt_accepts_domain_names_with_known_suffix_and_privateCases')]
    public function it_accepts_domain_names_with_known_suffix_and_private(string $name): void
    {
        $this->validator->validate($name, new DomainNameRegistrable(allowPrivate: true));
        $this->assertNoViolation();
    }

    /**
     * @return \Generator<int, array{0: string}>
     */
    public static function provideIt_accepts_domain_names_with_known_suffix_and_privateCases(): iterable
    {
        yield ['example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['example.github.io'];
        yield ['github.com'];

        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    #[Test]
    #[DataProvider('provideIt_rejects_domain_names_with_private_suffixCases')]
    public function it_rejects_domain_names_with_private_suffix(string $name): void
    {
        $this->validator->validate($name, $constraint = new DomainNameRegistrable());
        $this->buildViolation($constraint->privateSuffixMessage)
            ->atPath('property.path.suffix')
            ->setCode(DomainNameRegistrable::PRIVATE_SUFFIX)
            ->setInvalidValue($name)
            ->assertRaised();
    }

    /**
     * @return \Generator<int, array{0: string}>
     */
    public static function provideIt_rejects_domain_names_with_private_suffixCases(): iterable
    {
        yield ['example.github.io'];
    }

    #[Test]
    public function it_rejects_non_registrable_domain_name(): void
    {
        $this->validator->validate('*.example.com', $constraints = new DomainNameRegistrable());
        $this->buildViolation($constraints->message)
            ->setCode(DomainNameRegistrable::NOT_REGISTRABLE)
            ->setInvalidValue('*.example.com')
            ->assertRaised();
    }

    #[Test]
    #[DataProvider('provideIt_rejects_domain_name_with_path_exceeding_registrableCases')]
    public function it_rejects_domain_name_with_path_exceeding_registrable(string $name, string $registrablePart): void
    {
        $this->validator->validate($name, $constraints = new DomainNameRegistrable(allowPrivate: true));
        $this->buildViolation($constraints->registrableLengthMessage)
            ->setParameter('{{ registrable }}', $registrablePart)
            ->setCode(DomainNameRegistrable::REGISTRABLE_LENGTH_EXCEEDED)
            ->setInvalidValue($name)
            ->assertRaised();
    }

    /**
     * @return \Generator<int, array{0: string, 1: string}>
     */
    public static function provideIt_rejects_domain_name_with_path_exceeding_registrableCases(): iterable
    {
        yield ['example.no.co.uk', 'no.co.uk'];
        yield ['test.example.github.io', 'example.github.io'];
        yield ['dev2.rollerscapes.net', 'rollerscapes.net'];
    }

    #[Test]
    #[DataProvider('provideIt_rejects_domain_name_when_failed_to_parseCases')]
    public function it_rejects_domain_name_when_failed_to_parse(string $name): void
    {
        $this->validator->validate($name, new DomainNameRegistrable());
        $this->buildViolation('This value is not a valid domain-name.')
            ->setCode(DomainNameRegistrable::INVALID_SYNTAX)
            ->setInvalidValue($name)
            ->assertRaised();
    }

    /**
     * @return \Generator<int, array{0: string}>
     */
    public static function provideIt_rejects_domain_name_when_failed_to_parseCases(): iterable
    {
        yield ['xn--94823482.nl']; // invalid IDN, which is actually thrown during the resolver phase
        yield ['nope.'];
        yield ['.nope'];
    }
}
