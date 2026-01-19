# MyEconomy

MyEconomy is a high quality economy plugin for PocketMine-MP API 5. It ships with SQLite/MySQL storage, a form-based UI,
and a stable API for other plugins.

## Features

- SQLite or MySQL backend
- EconomyAPI-compatible commands
- Integer-only balances (no decimals)
- Form UI via `/money`
- English and Japanese messages
- Public API and events for integrations

## Requirements

- PocketMine-MP API 5
- PHP 8.1 or newer

## Installation

1. Copy the `MyEconomy` folder into your server `plugins` directory.
2. Start the server to generate the default config.
3. Edit `plugins/MyEconomy/config.yml` as needed.

## Configuration (config.yml)

```yaml
monetary-unit: "$"
add-op-at-rank: false
default-money: 1000
max-money: 9999999999
allow-pay-offline: true
default-lang: en
provider: sqlite
provider-settings:
  sqlite-file: economy.sqlite
  mysql:
    host: 127.0.0.1
    port: 3306
    user: root
    password: ""
    database: economy
```

## Commands

- `/mymoney`
- `/topmoney [page]`
- `/setmoney <player> <amount>`
- `/seemoney <player>`
- `/givemoney <player> <amount>`
- `/takemoney <player> <amount>`
- `/pay <player> <amount>`
- `/setlang <language>`
- `/mystatus`
- `/money` (menu)

## Permissions

- `economyapi.command.*` for all economy commands
- `economyapi.*` for full admin access

## API Usage

```php
use net\mydeacy\myeconomy\MyEconomyPlugin;

$plugin = $server->getPluginManager()->getPlugin("MyEconomy");
if ($plugin instanceof MyEconomyPlugin) {
    $api = $plugin->getApi();
    $api->addMoney("Steve", 100);
}
```

## Events

- `net\mydeacy\myeconomy\event\account\AccountCreateEvent`
- `net\mydeacy\myeconomy\event\money\AddMoneyEvent`
- `net\mydeacy\myeconomy\event\money\ReduceMoneyEvent`
- `net\mydeacy\myeconomy\event\money\SetMoneyEvent`
- `net\mydeacy\myeconomy\event\money\PayMoneyEvent`
- `net\mydeacy\myeconomy\event\money\MoneyChangedEvent`

## EconomyAPI Adapter

If you need compatibility with plugins that depend on EconomyAPI, install the `EconomyAPI` adapter plugin that ships
alongside this project.
