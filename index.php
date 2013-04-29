<?php

include_once( "phpsdk/config.php" );
include_once( "phpsdk/saetv2.ex.class.php" );

define("MASTER_UID", "1848746107");  //主人新浪微博的uid
define("DOWNLOAD_DIR", "/tmp ");  //存储目录
define("SUCCESS_DOWNLOAD_MSG", "主人，女仆已帮您下载好了");
define("FAIL_DOWNLOAD_MSG", "主人，您的下载地址有误，小女子无能为力啊");
define("DOWNLOAD_CMD", "下载"); //每次获取用户微博的最新微博的个数
define("NUM_OF_WEIBO", 10); //每次获取用户微博的最新微博的个数

set_time_limit(0); 

class Maid{

    var $o;
    var $v;
    /*
     * new sina weibo api class
     */
    function __construct() {
        $this->o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
        $this->v = new SaeTClientV2(WB_AKEY, WB_SKEY, ACCESS_TOKEN);
    }

    /*
     * 启动女仆的接口
     */
    function start_work() {
        $timeline = $this->v->user_timeline_by_id(MASTER_UID, 1, NUM_OF_WEIBO); //每次获取主人最新NUM_OF_WEIBO条微博

        foreach($timeline['statuses'] as $w) {
            $tweet = $w['text']; //获取微博内容
            $id = $w['id']; //获取微博ID
            if(strpos($tweet, DOWNLOAD_CMD)) {
                preg_match("/http:\/\/t.cn\/[a-z0-9A-Z]*/",$tweet, $matches);
                $url = $matches[0];
                if($this->is_download($url) === FALSE) {
                    $success = $this->start_download($url);
                    $this->finish_download($id, $success);
                }
            }
        }
    }

    /*
     * 判断$url地址是否已下载
     * 如果已下载返回TRUE 没有下载返回FALSE
     *
     * @param string $url 文件的下载地址 
     * @return bool
     */
    function is_download($url) {
        $contents = file_get_contents(DOWNLOAD_LOG_FILE);
        $download_url_lists = explode("\n", $contents);
        if(array_search($url, $download_url_lists) === FALSE) {
            file_put_contents(DOWNLOAD_LOG_FILE, $url."\n", FILE_APPEND);
            return FALSE;
        }
        return TRUE;
    }

    /*
     * 调用linux的wget命令进行下载
     * @param string $url 下载地址
     */
    function start_download($url) {
        exec("wget -P ".DOWNLOAD_DIR.$url, $tmp, $return_code);
        if($return_code == 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /*
     * 完成下载后，评论给主人且转发
     */
    function finish_download($id, $success) {
        if($success) {
            $this->v->send_comment($id, SUCCESS_DOWNLOAD_MSG);
            $this->v->repost($id, SUCCESS_DOWNLOAD_MSG);
        } else {
            $this->v->send_comment($id, FAIL_DOWNLOAD_MSG);
            $this->v->repost($id, FAIL_DOWNLOAD_MSG);
        }
    }

}

    $my_maid = new Maid();
    $my_maid->start_work();

?>
