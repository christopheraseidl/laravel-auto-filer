# Changelog

All notable changes to `laravel-auto-filer` will be documented in this file.

## v0.4.0 - 2026-09-08

Laravel 13 binds `image` in `DefaultProviders`, colliding with
`intervention/image-laravel`'s facade accessor. Fixed by moving to
`intervention/image-laravel` ^4.1 and resolving `ImageManagerInterface`
from the container instead of the facade.

Laravel 10, 11, and 12 remain supported. CI now covers Laravel 10-13 on
PHP 8.3 and 8.4.

## Breaking

- Requires `intervention/image` v4. If your app calls that library
  directly, check its v3 to v4 upgrade guide.
- Dropped the `FileService` alias, which named a nonexistent class.

## Maintenance

- CI action bumps: checkout v7, composer-install v4, fetch-metadata
  3.1.0, git-auto-commit v7, pint-action 2.6.
- Dev dependencies now allow Pest 4 and Testbench 11.