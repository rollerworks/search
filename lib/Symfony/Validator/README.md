RollerworksSearch Symfony Validator
===================================

This package provides the Symfony Validator extension for [RollerworksSearch][1].

If you'd like to contribute to this project, please see the [contributing guide lines][2]
for more information.

**Note:**

> This validation is meant to be used for business rules like a minimum/maximum
> value range or disallowing specific patterns. The data transformers already ensure
> the value is properly transformed.

Installation
------------

To install this extension, add the `rollerworks/search-symfony-validator`
package in your composer.json and update your dependencies.

```bash
$ composer require rollerworks/search-symfony-validator
```

Symfony framework integration
-----------------------------

**Note:** The Symfony integration bundle for RollerworksSearch already enables
the Symfony validator service. You don't need to do anything but configure your
field's constraints.

This package provides the Symfony integration bundle for [RollerworksSearch][1].

License
-------

The source of this package is subject to the MIT license that is bundled
with this source code in the file [LICENSE](LICENSE).

[1]: https://github.com/rollerworks/RollerworksSearch
[2]: https://github.com/rollerworks/RollerworksSearch#contributing
