# Changelog

All notable changes to **SearchProtect** are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.3] - 2026-06-26

### Fixed

- Fixed client IP resolution so SearchProtect no longer trusts spoofable
  `CF-Connecting-IP` or `X-Real-IP` request headers directly.
- SearchProtect now delegates client IP detection to PrestaShop
  `Tools::getRemoteAddr()` and falls back to `REMOTE_ADDR` only when needed.
  Shops behind a reverse proxy or CDN should verify their PrestaShop reverse
  proxy configuration and core version; otherwise the blocked/logged IP may be
  the proxy address, or may follow the core fallback for forwarded headers.

## [1.0.2] - 2026-06-26

### Added

- Added English, Italian, Spanish, French, German, Portuguese, Polish, Romanian, and Dutch translations.
- Updated module logo asset.

### Fixed

- Fixed module file comment spacing for PrestaShop license validation.

## [1.0.1] - 2026-06-26

### Fixed

- Fixed PrestaShop validator coding standard warnings in module guard files.
- Added explicit visibility to module constants.

## [1.0.0] — 2026-03-25

### Added

- Initial release.
- Hook `actionFrontControllerInitBefore` to intercept search requests before any DB query.
- Six protection rules: `query_too_long`, `amp_flood`, `page_injection`,
  `querystring_too_long`, `encoding_abuse`, `iqit_amp_page_combo`.
- Detection of native PrestaShop search controller and localised search slugs
  (`/busqueda`, `/recherche`, `/ricerca`).
- Detection of third-party search modules: IQITSearch, PS Searchbar,
  ElasticSearch, nrtSearch, SphinxSearch.
- IP blocking via PrestaShop cache (expiry timestamp stored as value) with
  file-based JSON fallback in `var/logs/searchprotect_blocks.json`.
- `HTTP 429 Too Many Requests` response with `Retry-After` header for blocked requests.
- DB logging of blocked attempts in `ps_searchprotect_log`.
- Back-office configuration page with `HelperForm` (all thresholds configurable).
- Smarty template `views/templates/admin/logs.tpl` for the last 100 blocked requests.
- Italian translation (`translations/it.php`).
- Security files: `index.php` in all directories, `.htaccess` at module root.
- `config.xml`, `LICENSE` (MIT), `README.md`, `CHANGELOG.md`.

---

<!-- [Unreleased]: https://github.com/ArteInfoRM/searchprotect/compare/v1.0.3...HEAD -->
<!-- [1.0.3]: https://github.com/ArteInfoRM/searchprotect/compare/v1.0.2...v1.0.3 -->
<!-- [1.0.2]: https://github.com/ArteInfoRM/searchprotect/compare/v1.0.1...v1.0.2 -->
<!-- [1.0.1]: https://github.com/ArteInfoRM/searchprotect/compare/v1.0.0...v1.0.1 -->
<!-- [1.0.0]: https://github.com/ArteInfoRM/searchprotect/releases/tag/v1.0.0 -->
