UPGRADE
=======

## 2013-10-02

This project was formally called the RollerworksRecordFilterBundle.

If you like to switch to this new project please bare in mind
this library is completely rewritten, you should read the new documentation
to get started with the upgrade.

## 23-07-2013

The directory structure has changed to fix some issues with Composer autoloading.

> These changes are fully backward compatible, but might cause some confusion at first.

* All Bundle related source files have moved to src/
* Test have moved to tests/Rollerworks/Bundle/RecordFilterBundle/Tests
* Documentation has moved to docs/
* And the LICENSE file is now in the root directory

## 29-11-2012

The public API has changed, if you cant upgrade yet, please use the 0.0.1 version.

* FilterTypeInterface no longer uses the $message parameter but uses the $messageBag parameter (MessageBag instance).
  Based on the existence of error messages the validation state is determined.

* MessageBag::addError() and addInfo() is now always translated.
  The $addTranslatorPrefix parameter is removed, and support for plural messages is added.

  And last, the default domain for addError() is changed to 'validators'.
