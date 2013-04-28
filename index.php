<?php

include_once( "phpsdk/config.php" );
include_once( "phpsdk/saetv2.ex.class.php" );

define("MASTER_UID", "1848746107");  //主人新浪微博的uid
define("DOWNLOAD_DIR", "/tmp ");  //存储目录
define("DOWNLOAD_LOG_FILE", "log/download.log"); //保存所有已下载过的下载地址
define("NUM_OF_WEIBO", 5); //每次获取用户微博的最新微博的个数
define("DOWNLOAD_CMD", "下载"); //每次获取用户微博的最新微博的个数

/*
 * 判断$url地址是否已下载
 * 如果已下载返回TRUE 没有下载返回FALSE
 *
 * @param string $url 文件的下载地址 
 * @return bool
 */
function is_downloaded($url) {
    $contents = file_get_contents(DOWNLOAD_LOG_FILE);
    $download_url_lists = explode("\n", $contents);
    if(array_search($url, $download_url_lists) === FALSE) {
        file_put_contents(DOWNLOAD_LOG_FILE, $url."\n", FILE_APPEND);
        return FALSE;
    }
    return TRUE;
}

function download($url) {
    exec("wget -P ".DOWNLOAD_DIR.$url);
}

$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
$v = new SaeTClientV2(WB_AKEY, WB_SKEY, ACCESS_TOKEN);

$timeline = $v->user_timeline_by_id(MASTER_UID, 1, NUM_OF_WEIBO); //每次获取用户5条最新微博 

foreach($timeline['statuses'] as $w) {
    $tweet = $w['text']; //获取微博内容
    $id = $w['id']; //获取微博ID
    echo $tweet;
    if(strpos($tweet, DOWNLOAD_CMD)) {
        preg_match("/http:\/\/t.cn\/[a-z0-9A-Z]*/",$tweet, $matches);
        $url = $matches[0];
        if(is_downloaded($url) === FALSE) {
            download($url);
            $v->send_comment($id, "主人，女仆帮您下载好了");
        }
    }
}

?>
