<?php
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.default.php";

$bot = new Feng\HasiBot\Bot( $config );
if ( !$bot->sanityCheck() ) {
	echo "Naive! API key都搞不对怎么续命啊！\n";
	exit;
}
$bot->loadStats();
$bot->run();
