<?php
namespace Feng\HasiBot;

use Irazasyed\Telegram\Telegram;
use Irazasyed\Telegram\Objects\Update;

class Bot {
	protected $api = null;
	protected $apiOffset = 0;
	protected $config = array();
	public $stats = array(
		"group" => 0,
		"pm" => 0,
		"total" => 0
	);
	public $keywords = array( "蛤", "蟆", "泽民", "江民", "长者", "续命", "一起哈啤", "华莱士", "资瓷", "基本法", "闷声发大财", "大新闻", "naive", "🐸" );

	const PROLONG_NORMAL = 1; // 续一秒
	const PROLONG_OFFICIAL = 31557600; // 官方蟆蛤，续一年！

	public function __construct( $config ) {
		$this->config = $config;
		$this->api = new Telegram( $this->getConfig( "key" ) );
	}

	public function getConfig( $key ) {
		return $this->config[$key];
	}

	public function saveStats() { // TODO: 咱们以后用Redis，大家资瓷不资瓷啊？
		$json = json_encode( $this->stats );
		$ret = file_put_contents( $this->getConfig( "savefile" ), $json );
	}

	public function loadStats() {
		$file = file_get_contents( $this->getConfig( "savefile" ) );
		$this->stats = json_decode( $file, true );
	}

	public function sanityCheck() {
		try {
			$this->api->getMe();
		} catch ( \Exception $e ) {
			return false;
		}
		return true;
	}

	public function prolongLife( $second = 1, $chatId = null, $fromId = null ) {
		$this->stats['total'] += $second;
		                          // 咱们做个登记，好不好啊？
		if ( $chatId !== null ) { // 吼啊！
			if ( $chatId == $fromId ) {
				$type = "pm";
			} else {
				$type = "group";
			}
			if ( !isset( $this->stats[$chatId] ) ) {
				$this->stats[$chatId] = 0;
			}
			if ( !isset( $this->stats[$fromId] ) ) {
				$this->stats[$fromId] = 0;
			}
			$this->stats[$chatId] += $second;
			if ( $type == "group" ) {
				$this->stats[$fromId] += $second;
			}
			$this->stats[$type] += $second;
		}
	}

	public function getLife( $chatId = "total" ) {
		if ( isset( $this->stats[$chatId] ) ) {
			return $this->stats[$chatId];
		} else {
			return 0;
		}
	}

	public function sayStats( $chatId, $isPm ) {
		$result = "";
		if ( $isPm ) {
			$a = "你";
		} else {
			$a = "你们";
		}
		$life = $this->getLife( $chatId );
		$others = $this->getLife() - $life;
		if ( $life == $others ) {
			if ( $life == 0 ) {
				$result .= "一秒都没续啊";
			} else {
				$result .= "续了 $life 秒，别人也续了 $others 秒";
			}
			$result .= "，为长者续命{$a}资瓷不资瓷啊？";
		} elseif ( $life < $others ) {
			if ( $life == 0 ) {
				$result .= "一秒都没续啊，";
			} else {
				$result .= "才续了 $life 秒，";
			}
			if ( $others != 0 ) {
				$result .= "看别人已经续了 $others 秒了，";
			}
			$result .= "为长者续命{$a}资瓷不资瓷啊？";
		} else {
			$result .= "{$a}续了 $life 秒，别人";
			if ( $others == 0 ) {
				$result .= "一秒都没续啊";
			} else {
				$result .= "才续了 $others 秒";
			}
			$result .= "，吼啊！";
		}
		return $result;
	}

	public function processUpdate( Update $update ) {
		foreach ( $update as $u ) {
			if ( $u['update_id'] < $this->apiOffset ) { // 续过再续是不对的！Naive!
				continue;
			}
			$this->apiOffset = $u['update_id'] + 1;
			if ( !empty( $u['message']['text'] ) ) {
				$chatId = $u['message']['chat']['id'];
				$fromId = $u['message']['from']['id'];
				$isPm = isset( $u['message']['chat']['username'] );
				$text = $u['message']['text'];
				foreach ( $this->keywords as $keyword ) { // 先续命
					if ( false !== strpos( strtolower( $text ), strtolower( $keyword ) ) ) {
						$this->prolongLife( self::PROLONG_NORMAL, $chatId, $fromId ); // 为长者续命
						break; // 一条一秒，续命也要按照基本法啊
					}
				}
				if ( 0 === strpos( $text, "/start" ) || 0 === strpos( $text, "/life" ) ) {
					$this->api->sendMessage( $chatId, $this->sayStats( $chatId, $isPm ) );
				}
				if ( 0 === strpos( $text, "/save" ) ) {
					if ( in_array( $fromId, $this->getConfig( "admin" ) ) ) {
						$this->saveStats();
						$this->api->sendMessage( $chatId, "你们要保存，吼啊！" );
					} else {
						$this->api->sendMessage( $chatId, "Naive! 保存也要按照基本法啊！" );
					}
				}
			}
		}
	}

	public function run() {
		for ( ; ; ) {
			$update = $this->api->getUpdates( $this->apiOffset, 100, 30 );
			$this->processUpdate( $update );
		}
	}
}
