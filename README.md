# Convert PHP 5.3 to PHP 7.10

## This is a script that convert php version from 5.3 to 7.10

### Installation

```bash
#PHP must be installed on your computer
git clone https://github.com/Thomas-Goillot/PHP-Convert-php-5.3-to-7.10
cd PHP-Convert-php-5.3-to-7.10
```

### Usage

```bash
php index.php /path/to/your/project
```

### What it does

* Replace old method constructor with __construct
* Check for __autoload method and replace it with spl_autoload_register
* Check for Quote UNDEFINED constant and correct them (lx-utils)
* Check in a list of deprecated function and inform the user
* Detect if xajax is used and inform the user
* Add (y/n) xajax modified for PHP 7.2
* Replace (y/n) xajax path 

