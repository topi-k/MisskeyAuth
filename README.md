MisskeyAuth
====
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](https://makeapullrequest.com)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

This script is PHP Library for Misskey Instance.

## Requirement
upper than PHP 8.0

## Usage

### Authenticate the user
#### Generate authentication URL
```php
use Topi\MisskeyAuth\MiAuth;

$mi = new MiAuth;
echo $mi->GenerateAuthURI("TestApp", "https://localhost/callback", ["write:notes"]);
```
#### Get user token
```php
use Topi\MisskeyAuth\MiAuth;

$mi = new MiAuth;
$mi->setUUID("aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa");
echo $mi->getAccessToken();
```
### Post a note
```php
use Topi\MisskeyAuth\MiAuth;

$auth = new MiAuth();
$auth->SetUserToken("TOKEN");
$auth->post("notes/create",["text" => "APIからテスト！", "visibility" => "followers"]);
```

### Get user information
```php
use Topi\MisskeyAuth\MiAuth;

$auth = new MiAuth;
$auth->SetUserToken("TOKEN");

echo $auth->get("i",[]);
```


## Install
### Use composer (Recommended)
```bash
composer install topi-k/misskeyauth
```

## Other
It is not complete. Sorry.Pull requests are welcome.

## Licence

[MIT](https://github.com/tcnksm/tool/blob/master/LICENCE)

## Author

[topi-k](https://github.com/topi-k)
