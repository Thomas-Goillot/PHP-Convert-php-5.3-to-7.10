# Convert PHP 5.3 to PHP 7.2


### What it does

* Replace old method constructor with __construct
* Check for __autoload method and replace it with spl_autoload_register
* Check for Quote UNDEFINED constant and correct them (lx-utils)
* Check in a list of deprecated function and inform the user
* Detect if xajax is used and inform the user
* Add (y/n) xajax modified for PHP 7.2
* Replace (y/n) xajax path 
* Change DBLIB to sqlsrv (Connexion to mssql server)
* Change of the connexion string for sqlsrv 

> For DBLIB to SQLSRV string the script is looking for host and dbname in every file. Be aware that it's able to change some variables name but it will be change everywere.


### Installation

```bash
#PHP must be installed on your computer
git clone https://github.com/Thomas-Goillot/PHP-Convert-php-5.3-to-7.2
cd PHP-Convert-php-5.3-to-7.2
```

### Usage

```bash
php index.php /path/to/your/project
```

or

```bash
php index.php 
```

### Todo 

- Update list of deprecated function or add a way to update it
- Add CLI option to skip some step
- Improve constant detection
- Improve xjax path detection and modification


