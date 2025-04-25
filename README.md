# Populate
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/populate.svg)](https://packagist.org/packages/laragear/populate)
[![Latest stable test run](https://github.com/Laragear/Populate/actions/workflows/php.yml/badge.svg)](https://github.com/Laragear/Populate/actions/workflows/php.yml)
[![Codecov coverage](https://codecov.io/gh/Laragear/Populate/graph/badge.svg?token=ck17pBP6VZ)](https://codecov.io/gh/Laragear/Populate)
[![Maintainability](https://qlty.sh/badges/34e3d55f-06cd-4a6c-b8d8-9d21ccbbcfb7/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/Populate)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Populate&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Populate)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/12.x/octane#introduction)

Populate your database with a supercharged, continuable seeder.

```php
use Laragear\Populate\Seeder;

class UserSeeder extends Seeder
{
    public function seedNormalUsers()
    {
        //
    }
    
    public function seedBannedUsers()
    {
        
    }
}
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable.

## Requirements

* PHP 8.2
* Laravel 11 or later

## Installation

You can install the package via Composer. 

```bash
composer require laragear/populate
```

## How does this work?

Laravel's Seeding system is very antique and basic. This library supercharges the seeding system of Laravel to make it more friendly to develop and run.

By _hijacking_ the default Seeder with a better one, we can supercharge the seed system to allow per-step seeding, skipping and _continuable_ seeding, without sacrificing on its normal features, and keeping compatibility with classic seeding classes.

## Set up

You may create a super-seeder using `make:super-seeder` Artisan command, and the name of the seeder.

```shell
php artisan make:super-seeder UserSeeder
```

You will receive a _Super Seeder_ with single `seed()` method, and two others one that will execute [before and after](#before--after).

```php
namespace Database\Seeders:

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Laragear\Populate\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run logic before executing the seed steps.
     */
    public function before(): void
    {
        // if (false) {
        //     $this->skip();
        // }
    }

    /**
     * Populate the database with records.
     */
    public function seed(): void
    {
        //
    }

    /**
     * Run logic after executing the seed steps.
     */
    public function after(): void
    {
        //
    }
}
```

> [!TIP]
> 
> If you already created your application seeders, replace the `Illuminate\Database\Seeder` import to `Laragear\Populate\Seeder`.
> 
> ```php
> namespace Database\Seeders;
> 
> // use Illuminate\Database\Seeder;
> use Laragear\Populate\Seeder;
> 
> class UserSeeder extends Seeder
> {
>     //..
> }
> ```

## Usage 

Instead of using the `run()` method in your seeder, this librarty seeders use the concept of **Seed Steps**. A Seed Step is just a public method that starts with `seed`, or uses the `Laragear\Populate\Attributes\SeedStep` attribute, named to briefly describe the records that are being inserted.

The container instantiates the Seeder and also calls each Seed Step, so you can use Dependency Injection as arguments anywhere you require.

```php
namespace Database\Seeders;

use Database\Factories\UserFactory;
use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\Seeder;
use App\TicketGenerator;

class UserSeeder extends Seeder
{
    public function __construct(protected TicketGenerator $ticket)
    {
        // ...
    }

    public function seedNormalUsers(UserFactory $users)
    {
        $users->count(5)->create(['ticket' => $this->ticket->generate()]);
    }

    public function seedVipUsers(UserFactory $users)
    {
        $users->vip()->count(3)->create();
    }
    
    #[SeedStep]
    public function bannedUsers(UserFactory $users)
    {
        $users->banned()->count(2)->create(['ticket' => $this->ticket->generate()]);
    }
}
```

For convenience, a Seed Step will persist all records if these return a Model Factory, a Collection of Models or a single Model.

```php
use Database\Factories\UserFactory;

public function seedNormalUsers(UserFactory $users)
{
    return $users->count(5);
}
```

When the seeder is called, each Seed Step will be output to the console like this:

    php artisan db:seed

    INFO Seeding database.
    
    Database\Seeders\UserSeeder ...................................... RUNNING
    ~ Seed normal users ................................................. DONE
    ~ Seed banned users ................................................. DONE
    Database\Seeders\UserSeeder .................................. 107 ms DONE

### Custom Seed Step naming

Each Seed Step is described in the console output as _First word capitalized_, so a Seed Step function called `seedVipUsers` will be displayed as `Seed vip users`. If you total control, the `SeedStep` attribute accepts an argument to change the output description.

```php
use Laragear\Populate\Attributes\SeedStep;

#[SeedStep(as: 'Seed non-authorized users')]
public function bannedUsers(UserFactory $users)
{
    $users->banned()->count(2)->create(['ticket' => $this->ticket->generate()]);
}
```

That will output the step as named, verbatim:

    php artisan db:seed

    INFO Seeding database.
    
    Database\Seeders\UserSeeder ...................................... RUNNING
    ~ Seed normal users ................................................. DONE
    ~ Seed non-authorized users ......................................... DONE
    Database\Seeders\UserSeeder .................................. 107 ms DONE

### Calling with arguments

When calling other seeders inside a Seed Step, you may use the usual `$this->call()` method and its variants. Classic seeders will execute their `run()` method as always if the method exists.

When doing calling a Super Seeder, you may set an array of arguments for each seed task by issuing their name as a key and the arguments as an array. This can be great when a seeder contains a Seed Steps that would require parameters to properly populate records on the database.  

```php
use Database\Factories\UserFactory;
use Database\Factories\CommentFactory;
use Laragear\Populate\Seeder;

class UserSeeder extends Seeder
{
    // ...
  
    public function seedMutedUsers(UserFactory $factory)
    {
        $users = $factory->muted()->create(3);
        
        $this->call(CommentSeeder::class, [
            'seedModeratedComment' => ['users' => $users],
        ]);
    }
}

class CommentSeeder extends Seeder
{
    public function seedModeratedComment(CommentFactory $comments, $users)
    {
        foreach ($users as $user) {
            $comments->for($user)->moderated()->create();
        }
    }
}
```

### Before & After

When calling a seeder, you may implement the `before()` and `after()` methods to run logic before the Seed Steps are executed, and after all are done with, respectively. As with Seed Steps, these are called through the Service Container.

```php
use Illuminate\Contracts\Config\Repository;use Illuminate\Contracts\Routing\UrlGenerator;
use Laragear\Populate\Seeder;

class CommentSeeder extends Seeder
{
    public function before(UrlGenerator $url)
    {
        // ...
    }
    
    public function after(Repository $config)
    {
        // ...
    }
}
```

### On Error

For better control on Seed Steps that returns errors, you may implement the `onError()` method that receives the offending exception. It's great to use for cleaning artifacts before stopping the seeding operation.

```php
use Laragear\Populate\Seeder;

class CommentSeeder extends Seeder
{
    public function onError($exception)
    {
        // ...
    }
}
```

You may also return or throw another exception to replace the previous exception.

```php
public function onError($exception)
{
    return new RuntimeException('The seeder failed', previous: $exception);
}
```

### Skipping

Laragear's Seeders support skipping either a Seed Step or the whole Seeder. Both are done through the `skip()` method.

### Skipping a Seed Step

To skip a Seed Step, you only need to call the `skip()` method inside it. 

```php
use App\Models\User;
use Database\Factories\UserFactory;

public function seedBannedUsers(UserFactory $user)
{
    if (User::query()->banned()->exists()) {
        $this->skip();
    }
    
    // ...
}
```

It will output something like this: 

    php artisan db:seed

    INFO Seeding database.
    
    Database\Seeders\UserSeeder ...................................... RUNNING
    ~ Seed normal users ................................................. DONE
    ~ Seed non-authorized users ...................................... SKIPPED
    Database\Seeders\UserSeeder .................................. 107 ms DONE

The `skip()` method supports using a reason for skip which will be displayed in the console output.

```php
use App\Models\User;

if (User::query()->banned()->exists()) {
    $this->skip('There are already banned users in the database');
}
```

    Database\Seeders\UserSeeder ...................................... RUNNING
    ~ Seed normal users ................................................. DONE
    ~ Seed non-authorized users ...................................... SKIPPED
      There are already banned users in the database
    Database\Seeders\UserSeeder .................................. 107 ms DONE

> [!TIP]
> 
> If transactions are not disabled, the `skip()` method will trigger a rollback wherever is called inside a Seed Step.

#### Skipping a Seeder

To skip a seeder completely, you may use the `skip()` method on the [`before()`](#before--after) method, which runs before any Seed Step is executed. As with the Seed Steps, you may also include a skip reason.

```php
use App\Models\Comment;
use Laragear\Populate\Seeder;

class CommentSeeder extends Seeder
{
    public function before()
    {
        if (Comment::query()->exists()) {
            $this->skip('There are already comments in the database')
        }
    }
}
```

    Database\Seeders\CommentSeeder ................................... RUNNING    
    Database\Seeders\CommentSeeder ................................... SKIPPED
      There are already comments in the database
    Database\Seeders\CommentSeeder ................................. 0 ms DONE

## Continue Seeding

Sometimes your Seeding command may fail for hard errors, leaving orphaned or incomplete records in your database and forcing you to seed the whole database again. 

To avoid this, Laragear Populate allows a previous incomplete seeding to _continue_. Call the `db:seed` command with the `--continue` option, and if the seeding fails it will save the progress for the next attempt.

```shell
php artisa db:seed --continue
```

The seeding continuation is tied to the Seeder you call in the command, which by default is `Database\Seeders\DatabaseSeeder`.

> [!TIP]
> 
> When using the `--continue` options, [transactions](#disable-transactions) will be automatically turned on.

The console output will mark the seed step as `CONTINUE` if the step it already ran.

    php artisan db:seed
    
    INFO Seeding database.
    
    Database\Seeders\UserSeeder ...................................... RUNNING
    ~ Seed normal users ............................................. CONTINUE
    ~ Seed non-authorized users ......................................... DONE
    Database\Seeders\UserSeeder ................................... 32 ms DONE


### Recovering from Unique Constraints Violations

Sometimes a Seed Step may throw a _Unique Constraints Violation_ exception, which happens when trying to insert a value that already exists on _unique_ column, like primary keys. It's not too common, but it usually happens when a random generator mistakenly repeats a value, like emails or text. 

[If transactions are not disabled](#disable-transactions), the Populator will retry the Seed Step again, showing the retry on the console. If the error persists, it will be thrown. 

    php artisan db:seed
    
    INFO Seeding database.
    
    Database\Seeders\UserSeeder ...................................... RUNNING
    ~ Seed normal users ............................................. CONTINUE
    ~ Seed non-authorized users ................................. RETRY UNIQUE
    ~ Seed non-authorized users ......................................... DONE
    Database\Seeders\UserSeeder ................................... 32 ms DONE

You can set the `retryUnique` property of the `SeedStep` attribute to any number of retries from the default `1`. Alternatively, setting it to `false` or `0` will disable it.

```php
use Laragear\Populate\Attributes\SeedStep;

#[SeedStep(retryUnique: false)]
public function bannedUsers
{
    // ...
}
```

### Disable transactions

Laragear Populate's Seeders wraps each Seed Step into its own transaction using the default database connection. This means that, when a Seed Step fails, all database operations inside that method are rolled back.

If you want to disable transactions, you may use set the `$usesTransactions` property to `false`. In that case, if you require seed steps to be skipped, it's recommended to skip at the start of the method.

```php
use App\Models\Comment;
use Laragear\Populate\Seeder;

class CommentSeeder extends Seeder
{
    public bool $usesTransactions = false;

    public function seedGuestComments()
    {
        if (Comment::query()->guest()->exists()) {
            $this->skip('There is already guest comments in the database');
        }
        
        // ...
    }

    // ...
}
```

> [!TIP]
> 
> Transactions use the `db:seed` command declared connection.

## Laravel Octane compatibility

- There are no singletons using a stale app instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties written during a request.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security related issues, issue a [Security Advisor](https://github.com/Laragear/Populate/security/advisories/new)

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2025 Laravel LLC.
