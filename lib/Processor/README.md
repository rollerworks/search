RollerworksSearch SearchProcessor
=================================

This package provides SearchProcess request handlers for [RollerworksSearch][1].
You need a PSR-7 compatible library to provide Request information to the processor.

The processors can be used with all supported input processors, condition
exporters, and is usable for REST-API endpoints.

Installation
------------

To install this package, add `rollerworks/search-processor` to your composer.json

```bash
$ php composer.phar require rollerworks/search-processor
```

Now, Composer will automatically download all required files, and install them
for you.

Basic usage
-----------

Each SearchProcessor works a `SearchPayload` that contains READ-ONLY
information about the processing result.

A SearchProcessor is re-usable and lazily loads related dependencies.
The loaders shown here don't allow custom input/condition-exporters,
use a PSR-11 compatible implementation for this.

```php
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Loader;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\Psr7SearchProcessor;

// The factory is reusable, you create it only once.
$searchFactory = Searches::createSearchFactory();

// Create a fieldset to inform the system about your configuration.
// Usually you will have a FieldSet for each data structure (users, invoices, etc).
$userFieldSet = $searchFactory->createFieldSetBuilder()
    ->add('firstName', TextType::class)
    ->add('lastName', TextType::class)
    ->add('age', IntegerType::class)
    ->add('gender', ChoiceType::class, [
        'choices' => ['Female' => 'f', 'Male' => 'm'],
    ])
    ->getFieldSet('users');

$inputProcessorLoader = Loader\InputProcessorLoader::create();
$conditionExporterLoader = Loader\ConditionExporterLoader::create();    
$processor = new Psr7SearchProcessor($searchFactory, $inputProcessorLoader, $conditionExporterLoader);

$request = ...; // A PSR-7 ServerRequestInterface object instance

$processorConfig = new ProcessorConfig($userFieldSet);
$searchPayload = $processor->processRequest($request, $processorConfig);

// When a POST is provided the processor will validate the input
// and export it. Note that an empty result is also valid.
// 
// The searchCode depends on the implementation of the SearchProcessor,
// and in this case contains a JSON exported SearchCondition encoded for URI usage.
if ($searchPayload->isChanged() && $searchPayload->isValid()) {
    // Redirect to this page with the search-code provided.
    header('Location: /search?search='.$searchPayload->searchCode);
    exit();
}

// Always do this check because searchCode could be malformed resulting in
// an invalid SearchCondition.
if (!$payload->isValid()) {
    // Each error message can be easily transformed to a localized version.
    // Read the documentation for more details.
    foreach ($payload->messages as $error) {
       echo (string) $error.PHP_EOL;
    }
}

// Notice: This is null when there are errors, when the condition is valid but has
// no fields/values this is an empty SearchCondition object.
$condition = $payload->searchCondition;
```

For better performance in a paginated search result the produced payload can be
cached using the `CachedSearchProcessor` (which requires a PSR-16 SimpleCache
implementation).

Versioning
----------

For transparency and insight into the release cycle, and for striving
to maintain backward compatibility, this package is maintained under
the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit <http://semver.org/>.

License
-------

The source of this package is subject to the MIT license that is bundled
with this source code in the file [LICENSE](LICENSE).

Contributing
------------

This is an open source project. If you'd like to contribute,
please read the [Contributing Code][2] part of Symfony for the basics. If you're submitting
a pull request, please follow the guidelines in the [Submitting a Patch][3] section.

[1]: https://github.com/rollerworks/search
[2]: http://symfony.com/doc/current/contributing/code/index.html
[3]: http://symfony.com/doc/current/contributing/code/patches.html#check-list
