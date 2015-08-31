<?php
// 把自己的配置放到config.php吼不吼啊？
$config = array(
	"key" => "API key放在这里你们资瓷不资瓷啊？",
	"admin" => array( "管理猿的ID放在这里吼不吼啊？" ),
	"savefile" => __DIR__ . "/life.json"
);

@include( __DIR__ . "/config.php" );
