<? include ("fortune.php");
$fortune = new Fortune();
$f = $fortune->QuoteFromDir("./samples/");
echo $f;
?>