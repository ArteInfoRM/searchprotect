# SearchProtect

> PrestaShop module — blocks malformed or oversized search queries to prevent DoS attacks on MariaDB.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7%20%7C%208.x-purple.svg)](https://www.prestashop.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892BF.svg)](https://php.net)

---

## The problem

Attackers flood the PrestaShop search endpoint (`/search`, `/busqueda`, and third-party modules
such as **IQITSearch**) with requests whose `s=` parameter contains hundreds of nested
`&amp;` entities and repeated `?page=N` injections. Each request triggers a full MariaDB
full-text query, causing CPU/load spikes that can take the site offline.

## How it works

The module hooks into `actionFrontControllerInitBefore` — the earliest possible point in the
PrestaShop lifecycle — and inspects the query **before** any controller or database code runs.
Offending IPs receive a `429 Too Many Requests` response and are temporarily blocked.

## Protection rules

| Code | What is checked | Default threshold |
|------|----------------|-------------------|
| `query_too_long` | Raw length of `s=` | 100 chars |
| `amp_flood` | Repeated `&amp;` entities after double URL-decode | 5 occurrences |
| `page_injection` | Repeated `?page=` inside the query value | 3 occurrences |
| `querystring_too_long` | Total length of `QUERY_STRING` | 2 000 chars |
| `encoding_abuse` | Chains of `%25` (recursive percent-encoding) | 5 in a row |
| `iqit_amp_page_combo` | Amp-flooded `s=` combined with a separate `&page=` param | — |
| `ip_blocked` | IP is already in the active block list | — |

All thresholds are configurable from the back-office without editing code.

## Detected endpoints

- `/search` (PrestaShop native controller)
- `/busqueda`, `/recherche`, `/ricerca` (localised search slugs)
- `/module/iqitsearch/` — IQITSearch
- `/module/searchbar/` — PS Searchbar
- `/module/elasticsearch/` — ElasticSearch bridge modules
- `/module/nrtSearch/` — nrtSearch
- `/module/sph_search/` — SphinxSearch

## Installation

1. Copy the `searchprotect/` folder into your PrestaShop `/modules/` directory.
2. Go to **Back Office → Modules → Module Manager**.
3. Search for **Search Protect** and click **Install**.
4. Click **Configure** to adjust thresholds and block duration.

## Requirements

| Requirement | Version |
|-------------|---------|
| PrestaShop | 1.7.x or 8.x |
| PHP | 7.4 or higher |

## IP blocking

Blocked IPs are stored in two places:

- **PrestaShop cache** (Redis / Memcached if configured) — expiry timestamp as the cached value.
- **File fallback** — `var/logs/searchprotect_blocks.json` for environments without a cache layer.

The default block duration is **1 hour** (3 600 seconds, configurable up to 24 hours).

SearchProtect uses PrestaShop `Tools::getRemoteAddr()` for client IP detection. If your shop runs behind a reverse proxy or CDN, configure PrestaShop's trusted proxy/reverse proxy settings where supported and verify the resolved IP on your PrestaShop version so SearchProtect logs and blocks the intended client address.

## Directory structure

```
searchprotect/
├── .htaccess
├── LICENSE
├── README.md
├── CHANGELOG.md
├── config.xml
├── index.php
├── searchprotect.php
├── translations/
│   ├── index.php
│   └── it.php
└── views/
    └── templates/
        ├── index.php
        ├── admin/
        │   ├── index.php
        │   └── logs.tpl
        └── hook/
            └── index.php
```

## Support

- **Website:** [tecnoacquisti.com](https://www.tecnoacquisti.com)
- **Email:** helpdesk@tecnoacquisti.com

## License

[MIT](LICENSE) © 2026 Tecnoacquisti.com - Arte e Informatica di Loris Modena e c. s.a.s.
