# Third-party notices

The SearchProtect 1.0.5 source and release file inventory were reviewed for
bundled libraries, manifests, lockfiles, scripts, fonts and remote services.
No separately identifiable third-party library is bundled. No Composer/npm
installation is required. Original module code remains under the MIT license
in LICENSE; brand assets belong to Tecnoacquisti.com®.

## Environment dependencies (not redistributed)

- PrestaShop: https://github.com/PrestaShop/PrestaShop, core OSL-3.0;
  provides Module, Tools, Link, Cache, database access and legacy forms.
- Smarty: https://github.com/smarty-php/smarty, LGPL-3.0;
  supplied by PrestaShop, exact version depends on the shop.
- PHP with mbstring and JSON support: https://www.php.net/;
  supplied by the hosting environment, PHP License 3.01 for the interpreter.
- MySQL/MariaDB: supplied and licensed by the hosting environment.

These components are not copied into this ZIP and their licenses are not
replaced by the module license. Installed versions must be inventoried and
maintained separately for each shop. No outbound API calls, CDN scripts or
telemetry were identified; documentation/support links are normal hyperlinks.
The inventory is based on source review, not a proof of complete provenance.
