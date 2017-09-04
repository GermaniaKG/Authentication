# Germania KG · Authentication

**This package was destilled from legacy code!**   
You better do not want it to use this in production.


## Installation

```bash
$ composer require germania-kg/authentication
```


## Overview

### AuthUserInterface

```php
namespace Germania\Authentication;

// Setter and Getter for User ID.
public function getId();
public function setId( $id );
```

### AuthUserMiddleware

**Constructor accepts:**
 
- *AuthUserInterface* instance
- *Aura\Session\SegmentInterface* instance.
- User ID session field name
- Optional: PSR-3 Logger

**Middleware does:**

- Stores the user with a  `user` attribute in the *Psr\Http\Message\ServerRequestInterface* request object. It will be available within the `$next` middleware.
- After running `$next` middleware, stores the User ID with the *SegmentInterface* instance, if the `user` attribute still is *AuthUserInterface* instance.

_____

### LoginController

TBD.

### LogoutController

TBD.

_____

## Development

```bash
$ git clone git@github.com:GermaniaKG/Authentication.git germania-authentication
$ cd germania-authentication
$ composer install
```

## Unit tests

Either copy `phpunit.xml.dist` to `phpunit.xml` and adapt to your needs, or leave as is. 
Run [PhpUnit](https://phpunit.de/) like this:

```bash
$ vendor/bin/phpunit
```
