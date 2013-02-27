UPGRADE
=======

* 29-11-2012

    The public API has changed, if cant upgrade yet, please use the 0.0.1 version.

  * FilterTypeInterface no longer uses the $message parameter but uses the $messageBag parameter (MessageBag instance).
    Based on the existence of error messages the validation state is determined.

   * MessageBag addError() and addInfo() is now always translated.
     The $addTranslatorPrefix parameter is removed, and support for plural messages is added.

     And last, the default domain for addError() is changed to 'validators'.
