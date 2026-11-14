# Security policy

SearchProtect is maintained by Tecnoacquisti.com®.

## Private reporting

Report suspected vulnerabilities to helpdesk@tecnoacquisti.com with the module,
PrestaShop and PHP versions, reproduction steps and impact. Do not publish
unpatched vulnerabilities, credentials or customer data in public issues.
If credentials are necessary, follow https://www.tecnoacquisti.com/it/content/19-come-inviare-le-tue-password.

## Updates and support status

Use the latest release in the 1.0 line. No separate backport policy is currently documented for older patches. Declared requirements remain PrestaShop 1.7 / 8.x and PHP 7.4+;
compatibility does not imply upstream security support for those platforms.
A fixed security support end date has not been established for this legacy
module; contact the maintainer for current support information. This document
does not establish a statutory support period or a CRA conformity claim.

Download updates and read release notes at
https://github.com/ArteInfoRM/searchprotect/releases.
Back up the shop, test the package in staging, then upgrade through the module
manager. Commercial assistance and applicable security update obligations are
separate matters.

## Deployment and data

Protect PrestaShop's var directory from public access at the web server layer,
including Nginx. The block file contains IP addresses and expiry timestamps.
Optional database logs contain IP addresses, time, reason and up to 500 search
characters; invalid non-string inputs use a fixed placeholder. Logging is on
by default and has no automatic retention purge. Merchants must configure
appropriate retention and restrict database/back-office access, or disable logging.

Configure trusted proxies correctly: client IP resolution depends on PrestaShop.
Shared IP addresses can cause legitimate customers to share a temporary block.
This module filters recognized search routes; it is not a site-wide rate limiter
or a guarantee against denial of service. Disable the module in the back office
if it causes false positives while investigating; this removes its protection.
Uninstalling deletes its configuration and database log table. The block file
is not removed automatically and can retain blocks until expiry after reinstall.
