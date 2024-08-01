<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `xima_typo3_mailcatcher`

![Latest version](https://typo3-badges.dev/badge/xima_typo3_mailcatcher/version/shields.svg)
[![Supported TYPO3 versions](https://typo3-badges.dev/badge/xima_typo3_mailcatcher/typo3/shields.svg)](https://extensions.typo3.org/extension/xima_typo3_mailcatcher)
![Total downloads](https://typo3-badges.dev/badge/xima_typo3_mailcatcher/downloads/shields.svg)
[![Tests](https://github.com/xima-media/xima-typo3-mailcatcher/actions/workflows/tests.yml/badge.svg)](https://github.com/xima-media/xima-typo3-mailcatcher/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/xima-media/xima-typo3-mailcatcher/graph/badge.svg?token=VUMQ5EUG02)](https://codecov.io/gh/xima-media/xima-typo3-mailcatcher)
![Composer](https://typo3-badges.dev/badge/xima_typo3_mailcatcher/composer/shields.svg)


</div>

A TYPO3 extension that adds a backend module to view emails that were send to
file.

![backend_module](Documentation/example_backend_module.png)

## Installation

```
composer require xima/xima-typo3-mailcatcher
```

## Configuration

No extension configuration needed!

To prevent TYPO3 from sending emails, change the mail transport to `mbox` (see
official [TYPO3 Mail-API](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Mail/Index.html#mbox)).
This way TYPO3 writes the outgoing emails to a log file that you can specify
via `transport_mbox_file`. The path musst be absolute.

```php
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'mbox';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/mail.log';
```

## License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).
