# Laravel ZKTeco Integration

## Overview
The Laravel ZKTeco package offers a seamless way to integrate ZKTeco biometric devices with your Laravel application.
It simplifies tasks such as connecting to devices, managing users, and retrieving attendance logs. 
With its intuitive API, developers can easily extract data like registered users, real-time logs, and device details, 
as well as add new users or clear attendance records. Utilizing a reliable socket connection, the package ensures 
fast and efficient data exchange, making it an essential tool for building attendance systems or time management applications.

## Features

-   **Effortless Integration:** Quickly connect ZKTeco biometric devices to your Laravel application with minimal setup.
-   **Comprehensive Attendance Management:** Fetch, view, and manage attendance logs efficiently.
-   **User Management Made Easy:** Add, update, or remove users directly from your application with full control.
-   **Real-Time Data Synchronization:** Always access up-to-date attendance and user data without delays.
-   **Advanced Device Control:** Manage device settings such as enabling/disabling devices, clearing logs, or restarting devices effortlessly.
-   **Enhanced Fingerprint Handling:** Retrieve, add, or remove fingerprint data with robust support.
-   **Device Information Access:** Obtain crucial details like version, OS, platform, serial number, and more.
-   **Custom Time Management:** Sync or update the device’s internal clock for precise record-keeping.

--- 

## Installation

You can install the package via composer:

```bash
composer require fsuuaas/zkteco
```
The package is designed to automatically register itself upon installation.

Please ensure that the PHP sockets extension is enabled on your server. If it is not enabled, you will need to activate it.

## Activate PHP Socket
Ensure that the PHP sockets extension is enabled on your server. If it is not enabled, follow these steps to activate it:

Locate the php.ini File:
The php.ini file's location depends on your PHP installation. Common locations include:

-   /etc/php/8.x/cli/php.ini (for CLI)
-   /etc/php/8.x/apache2/php.ini (for Apache)
-   /etc/php/8.x/fpm/php.ini (for PHP-FPM)

Edit the php.ini File:
Open the php.ini file in a text editor with superuser privileges:

```
sudo nano /etc/php/7.x/apache2/php.ini
```
Uncomment the Sockets Extension:
Find the following line:

```
;extension=sockets
```

Remove the semicolon (;) to uncomment the line:
```
extension=sockets
```

Save and Exit:
Save the changes and exit the editor (Ctrl + X, Y, Enter).

Restart the Web Server:
Restart the web server to apply the changes:

```
sudo systemctl restart apache2
```

## Usage

Instantiate the Zkteco Object.
```php
use Fsuuaas\Zkteco\Zkteco;
$zk = new LaravelZkteco('ipaddress', 'port');
```


Call ZKTeco methods

* __Connect__
```php
//    connect device
//    this return bool
    $zk->connect();   
```

* __Disconnect__
```php
//    disconnect device
//    this return bool

    $zk->disconnect();   
```

* __Enable Device__
```php
//    enable devices
//    this return bool/mixed

    $zk->enableDevice();   
```
> **NOTE**: You have to call after read/write any info of Device.

* __Disable Device__
```php
//    disable  device
//    this return bool/mixed

    $zk->disableDevice(); 
```
> **NOTE**: You have to call before read/write any info of Device.


* __Device Version__
```php
//    get device version 
//    this return bool/mixed

    $zk->version(); 
```


* __Device Os Version__
```php
//    get device os version 
//    this return bool/mixed

    $zk->osVersion(); 
```

* __Power Off__
```php
//    turn off the device 
//    this return bool/mixed

    $zk->shutdown(); 
```

* __Restart__
```php
//    restart the device 
//    this return bool/mixed

    $zk->restart(); 
```

* __Sleep__
```php
//    sleep the device 
//    this return bool/mixed

    $zk->sleep(); 
```

* __Resume__
```php
//    resume the device from sleep 
//    this return bool/mixed

    $zk->resume(); 
```

* __Voice Test__
```php
//    voice test of the device "Thank you" 
//    this return bool/mixed

    $zk->testVoice(); 
```

* __Platform__
```php
//    get platform 
//    this return bool/mixed

    $zk->platform(); 
```

* __Firmware Version__
```php
//    get firmware version
//    this return bool/mixed

    $zk->fmVersion(); 
```

* __Work Code__
```php
//    get work code
//    this return bool/mixed

    $zk->workCode(); 
```

* __SSR__
```php
//    get SSR
//    this return bool/mixed

    $zk->ssr(); 
```

* __Pin Width__
```php
//    get  Pin Width
//    this return bool/mixed

    $zk->pinWidth(); 
```

* __Serial Number__
```php
//    get device serial number
//    this return bool/mixed

    $zk->serialNumber(); 
```

* __Device Name__
```php
//    get device name
//    this return bool/mixed

    $zk->deviceName(); 
```

* __Get Device Time__
```php
//    get device time

//    return bool/mixed bool|mixed Format: "Y-m-d H:i:s"

    $zk->getTime(); 
```

* __Set Device Time__
```php
//    set device time
//    parameter string $t Format: "Y-m-d H:i:s"
//    return bool/mixed

    $zk->setTime(); 
```

* __Get Users__
```php
//    get User
//    this return array[]

    $zk->getUser(); 
```

* __Set Users__
```php
//    set user

//    1 s't parameter int $uid Unique ID (max 65535)
//    2 nd parameter int|string $userid ID in DB (same like $uid, max length = 9, only numbers - depends device setting)
//    3 rd parameter string $name (max length = 24)
//    4 th parameter int|string $password (max length = 8, only numbers - depends device setting)
//    5 th parameter int $role Default Util::LEVEL_USER
//    6 th parameter int $cardno Default 0 (max length = 10, only numbers

//    return bool|mixed

    $zk->setUser(); 
```

* __Clear All Admin__
```php
//    remove all admin
//    return bool|mixed

    $zk->clearAdmin(); 
```

* __Clear All Users__
```php
//    remove all users
//    return bool|mixed

    $zk->clearAdmin(); 
```

* __Remove A User__
```php
//    remove a user by $uid
//    parameter integer $uid
//    return bool|mixed

    $zk->removeUser(); 
```

* __Get Attendance Log__
```php
//    get attendance log

//    return array[]

//    like as 0 => array:5 [▼
//              "uid" => 1      /* serial number of the attendance */
//              "id" => "1"     /* user id of the application */
//              "state" => 1    /* the authentication type, 1 for Fingerprint, 4 for RF Card etc */
//              "timestamp" => "2020-05-27 21:21:06" /* time of attendance */
//              "type" => 255   /* attendance type, like check-in, check-out, overtime-in, overtime-out, break-in & break-out etc. if attendance type is none of them, it gives  255. */
//              ]
//      Pass parameter of record size for latest devices like as: Speedface V5L using 49 bytes.
// Most of the old device using 40 bytes of record size, Example: iClock 680. 
// For Old Device parameter is optional
    $zk->getAttendance(49); 
```

* __Clear Attendance Log__
```php
//    clear attendance log

//    return bool/mixed

    $zk->clearAttendance(); 
```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Raihan_Afroz](https://github.com/raihanafroz)
- [Coding_Libs](https://github.com/coding-libs)
- [adrobinoga](https://github.com/adrobinoga)
- [Mehedi Jaman](https://github.com/mehedijaman)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.