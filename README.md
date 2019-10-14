# Storage

![license](https://img.shields.io/badge/license-MIT-brightGreen.svg)
[![build](https://travis-ci.org/originphp/storage.svg?branch=master)](https://travis-ci.org/originphp/storage)
[![coverage](https://coveralls.io/repos/github/originphp/storage/badge.svg?branch=master)](https://coveralls.io/github/originphp/storage?branch=master)

The Storage library provides an easy way to access different types of storages from local disk, FTP and SFTP. Its a unified approach for working with different storages.

## Installation

To install this package

```linux
$ composer require originphp/storage
```

## Configuration

You need to configure the default storage engine, you can use multiple engines, but there must be a default one.

In your bootstrap/configuration files add

```php
use Origin\Storage\Storage;

Storage::config('default', [
    'engine' => 'Local'
    'root' => '/var/www/storage'
]);
```


## Using Storage


### Writing To Storage

```php
use Origin\Storage\Storage;
Storage::write('test.txt','hello world!');
```

You can also write to folders directly. Folders in the tree that do not exist will be created automatically.

```php
Storage::write('my_folder/test.txt','hello world!');
```

### Reading From Storage

```php
use Origin\Storage\Storage;
$contents = Storage::read('my_folder/test.txt');
```

### Deleting From Storage

To delete files or folders

```php
Storage::delete('my_folder/test.txt');
Storage::delete('my_folder');
```

Folders are deleted recursively automatically, when using delete.

### Listing Storage Contents

To list the files on the storage

```php
use Origin\Storage\Storage;
$allFiles = Storage::list();
```

Storage contents are listed recursively and it will provide you with an array of arrays. Each file has its own array.

```php

// Will look like this
[
    'name' => 'my_folder/test.txt',
    'size' => 1024,
    'timestamp' => 1559692998
];

```

If you just want the files of particular folder, then it will list all files recursively under that folder.

```php
use Origin\Storage\Storage;
$files = Storage::list('my_folder');
```

### Working with Multiple Storages


Whether you are using multiple storage engines, or you multiple configurations for a single storage engine, the Storage utility is flexible.

You can get the configured Storage volume

```php
$volume = Storage::volume('sftp-backup');
$data = $volume->read('transactions.csv');
```

Or you can pass an options array telling the Storage object which configuration to use

```php
$data = Storage::read('transactions.csv',[
     'config'=>'sftp-backup'
     ]);
```

## Storage Engines

### Local

The local storage simply works with data from the drive.

```php
Storage::config('default', [
    'engine' => 'Local',
    'root' => '/var/www/storage'
 ]);
```

### FTP

Then you need to configure this

```php
Storage::config('default', [
    'engine' => 'Ftp',
    'host' => 'example.com',
    'port' => 21,
    'username' => 'james',
    'password' => 'secret',
    'ssl' => false
 ]);
```

options for configuring FTP include:

- host: the hostname or ip address
- port: the port number. default 21
- username: the ftp username
- password: the ftp password
- timeout: default 10 seconds
- passive: deafult false
- root: the root folder of the storage within your ftp account
- ssl: default: false

### SFTP

To configure SFTP

```php
Storage::config('default', [
    'engine' => 'Sftp',
    'host' => 'example.com',
    'port' => 22,
    'username' => 'james',
    'password' => 'secret'
 ]);
```

If you use want to use a private key to login, you can either provide the filename with the full path or the contents of the private key itself.


```php
Storage::config('default', [
    'engine' => 'Sftp',
    'host' => 'example.com',
    'port' => 22,
    'username' => 'james',
    'privateKey' => '/var/www/config/id_rsa'
     ]);
```

If your private key requires a password then you can provide that as well. See the [How to setup SSH keys ](https://linuxize.com/post/how-to-set-up-ssh-keys-on-ubuntu-1804/) tutorial for more information.

options for configuring SFTP include:

- host: the hostname or ip address
- port: the port number. default 22
- username: the ssh account username
- password: the ssh account password
- timeout: default 10 seconds
- root: the root folder of the storage. e.g. /home/user/sub_folder
- privateKey: either the private key for the account or the filename where the private key can be loaded from
