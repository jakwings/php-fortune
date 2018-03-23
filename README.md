php-fortune
===========

A simple script for fortune cookie files.

It is not fully compatible with strfile data format v1.x. Only `%` is allowed
as quote delimiters. Lines started with `%%` are never treated as comments.
ROT13 transformation for text and reordering for indexes are not supported.
Both LF and CRLF are valid line terminators, which are included in the quotes.

## Basic Usage

```php
<?php
// ...

require 'fortune.php';
$fortune = new Fortune();

// Read a random quote from a directory which contains the source files.
// The index files *.dat will be auto-generated if not found in the directory.
$fortune->QuoteFromDir($dir);

// ...
?>
```
