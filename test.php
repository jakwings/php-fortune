<?php

require 'fortune.php';

$fortune = new Fortune();
$filepath = 'fortunes/data.txt';

$fortune->CreateIndexFile($filepath);

assert($fortune->GetNumberOfQuotes($filepath) === 6);
assert($fortune->GetExactQuote($filepath, 0) === "Hello, world.\r\n");
assert($fortune->GetExactQuote($filepath, 1) === "The second quote.\n");
assert($fortune->GetExactQuote($filepath, 2) === "The third quote.\n");
assert($fortune->GetExactQuote($filepath, 3) === "The fourth quote.\n");
assert($fortune->GetExactQuote($filepath, 4) === "[V] need your money!\n");
assert($fortune->GetExactQuote($filepath, 5) === "Goodbye.");

echo $fortune->GetRandomQuote($filepath);

?>
