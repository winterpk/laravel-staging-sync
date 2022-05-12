# Description

This package is used to sync a staging database with a source (production) database. It contains 3 commands: `laravel-staging-sync:sync` `laravel-staging-sync:clear` and `laravel-staging-sync:rollback`. 

## Installation

First add this to composer.json

```json
    "require": {
        "winterpk/laravel-staging-sync": "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:winterpk/laravel-staging-sync.git"
        }
    ],
```

Composer update to get it

```bash
composer update winterpk/laravel-staging-sync
```

## Usage

With Artisan on command line:

```bash
php artisan laravel-staging-sync:sync [source_database_name] [mysql_admin_username] [mysql_admin_password] [host] [port];
```

Using a scheduled command with ENV values:

```php
$schedule->command('laravel-staging-sync:sync ' .
                env('STAGING_SYNC_DB_NAME', false) .
                ' ' . env('STAGING_SYNC_DB_USER', false) .
                ' ' . env('STAGING_SYNC_DB_PASS', false) .
                ' ' . env('STAGING_SYNC_DB_HOST', false) .
                ' ' . env('STAGING_SYNC_DB_PORT', false) .
                ' --force')
            ->daily()->at('5:00');
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email winterpk@gmail.com instead of using the issue tracker.

## Credits

-   [Winter King](https://github.com/winterpk)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
