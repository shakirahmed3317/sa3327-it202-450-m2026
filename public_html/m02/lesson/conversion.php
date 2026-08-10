<?php
$rawCount = "5";
$priceText = "12.75";
$ready = true;
$tagsText = "php,js,sql";

$count = (int) $rawCount;
$price = (float) $priceText;
$readyText = (string) $ready;
$tags = explode(",", $tagsText);

var_dump($count);
var_dump($price);
var_dump($readyText);
var_dump($tags);
