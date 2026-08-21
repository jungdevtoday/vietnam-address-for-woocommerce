# OneStudio Vietnam Address for WooCommerce

Vietnamese administrative address integration for WooCommerce — Province/City, District, and Ward fields for both the current (post 1 July 2025) and legacy address structures, with bundled data and an optional self-hosted sync server.

[![License: GPL v2+](https://img.shields.io/badge/license-GPL%20v2%2B-blue.svg)](LICENSE)

## Features

- **Bundled address data** — the full Province/City, District, and Ward dataset ships with the plugin. No API key, no external service required to work.
- **Both address structures** — the current 34-province, 2-level structure (Province → Ward) and the legacy 63-province, 3-level structure (Province → District → Ward), selectable per store.
- **Classic Checkout and Block Checkout** — full field support on both, including a searchable Ward autocomplete on Block Checkout and optional Select2-enhanced dropdowns on Classic Checkout.
- **Old-to-new order converter** — bulk-converts existing orders from the legacy structure to the current one, entirely locally, with ambiguous cases flagged for manual review instead of guessed.
- **Optional central data server** — point the plugin at a central sync server (self-hostable, see [onestudio-vietnam-address-api-server](https://github.com/onestudiovn/onestudio-vietnam-address-api-server)) to receive administrative boundary updates without a plugin update. Falls back to the bundled data automatically if unset or unreachable.
- **Multilingual** — Vietnamese, English, Français, Deutsch, 日本語.

## Installation

**From WordPress.org** (recommended once approved): search for "OneStudio Vietnam Address for WooCommerce" under Plugins → Add New.

**Manual install:**

1. Download the latest `onestudio-vietnam-address-for-woocommerce.zip` from [Releases](../../releases).
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin** and upload the zip, or extract it into `wp-content/plugins/`.
3. Activate the plugin, then configure it under **WooCommerce → Vietnam Address**.

## Requirements

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+

## Data source

Administrative data is sourced from [VietMap](https://github.com/vietmap-company/vietnam_administrative_address) under the VietMap Administrative Data License (see [`assets/data/LICENSE-vietmap-data.txt`](assets/data/LICENSE-vietmap-data.txt)).

## Related project

[onestudio-vietnam-address-api-server](https://github.com/onestudiovn/onestudio-vietnam-address-api-server) — the open-source central data server this plugin can optionally sync with. Self-hostable if you'd rather not depend on the default one.

## License

GPLv2 or later — see [LICENSE](LICENSE).

## Support

https://onestudio.vn
