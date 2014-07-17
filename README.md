php-fortune
===========

A simple script for fortune cookie files. It is not compatible with strfile data format v1.x.

### Basic Usage

~~~php
<?php
// ...

require 'fortune.php';
$fortune = new Fortune();

// Get random quote from a directory which contains source files.
// If the index files *.dat doesn't exist, it will try to generate them.
$fortune->QuoteFromDir($dir);

// ...
?>
~~~
