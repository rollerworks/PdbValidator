Rollerworks PdbValidator
========================

This package provides public domain-suffix and top-level domain validators for
the Symfony Validator component.

Allowing to validate the following (per constraint):

* If the domain-name is registrable (you cannot register name.github.io
  as this is a public-suffix managed by GitHub for example, and you cannot
  use custom TLDs;

* If the public-suffix of domain name is valid;

**Note:** These constraints are used by the [Rollerworks X509Validator]
to ensure no invalid hostnames are used in the Certificate's subject.

## Installation

To install this package, add `rollerworks/pdb-validator` to your composer.json:

```bash
$ php composer.phar require rollerworks/pdb-validator
```

Now, [Composer][composer] will automatically download all required files,
and install them for you.

[Symfony Flex][flex] (with contrib) is assumed to enable the Bundle and add
required configuration. https://symfony.com/doc/current/bundles.html

Otherwise enable the `Rollerworks\Component\PdbValidator\Bundle\RollerworksPdbValidatorBundle`
and the `Rollerworks\Component\PdbSfBridge\Bundle\RollerworksPdbBundle`

**Note:** Don't forget to configure the `RollerworksPdbBundle`.

## Requirements

You need at least PHP 8.1, and configure the PdbManager as provided by 
[Rollerworks PdbSfBridge](https://github.com/rollerworks/PdbSfBridge).

## Basic Usage

### Validators Set-up

The Validators need to be registered with a ConstraintValidatorFactory,
the bundle is already ready to use.

Both the `DomainNameRegistrableValidator` and `DomainNameSuffixValidator`
require a `Rollerworks\Component\PdbSfBridge\PdpManager` instance 
is passed to their constructor.

### Constraints

* The `DomainNameRegistrable` constraint has one specific option `allowPrivate`
  which specifies if private-prefixes (like github.io) are allowed. Default is
  `false`.

* The `DomainNameSuffix` constraint has one specific option `requireICANN`
  which configures whether the effective TLD requires a matching rule in 
  a Public Suffix List ICANN Section. Default is `true`.

  When set to `false` the suffix is still required to exist.

## Versioning

For transparency and insight into the release cycle, and for striving to
maintain backward compatibility, this package is maintained under the
Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit <http://semver.org/>.

## License

This library is released under the [MIT license](LICENSE).

## Contributing

This is an open source project. If you'd like to contribute,
please read the [Contributing Guidelines][contributing]. If you're submitting
a pull request, please follow the guidelines in the [Submitting a Patch][patches] section.

[Rollerworks X509Validator]: https://github.com/rollerworks/x509Validator
[composer]: https://getcomposer.org/doc/00-intro.md
[flex]: https://symfony.com/doc/current/setup/flex.html
[contributing]: https://contributing.rollerscapes.net/
[patches]: https://contributing.rollerscapes.net/latest/patches.html
