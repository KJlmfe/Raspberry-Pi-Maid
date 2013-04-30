<?php

include_once( "phpsdk/config.php" );
include_once( "phpsdk/saetv2.ex.class.php" );

define("DOWNLOAD_CMD", "下载"); 
define("POST_GIRL_PIC_CMD", "妹纸图"); 

define("MASTER_UID", "1848746107");  //主人新浪微博的uid
define("MASTER_NAME", "@KJlmfe");  //主人新浪微博的name
define("MY_NAME", "@KJlmfe的树莓派女仆");
define("DOWNLOAD_DIR", "/tmp ");  //存储目录
define("SUCCESS_DOWNLOAD_MSG", " 女仆已把东东下载好了");
define("FAIL_DOWNLOAD_MSG", " 下载地址有误，小女子无能为力啊");
define("NUM_OF_WEIBO", 10); //每次获取用户微博的最新微博的个数
define("DOWNLOAD_LOG_FILE", "log/download.log"); //保存所有已下载过的下载地址
define("CMD_LOG_FILE", "log/weibo_id.log"); //保存所有已处理过的微博

$img_urls = array(
    "http://www.tupian.fm/", 
    "http://www.tupian.fm/?cat=22",
    "http://www.tupian.fm/?cat=23",
    "http://www.tupian.fm/?cat=24",
    "http://www.tupian.fm/?cat=25",
    "http://www.tupian.fm/?cat=26",
    "http://www.tupian.fm/?cat=27",
    "http://www.tupian.fm/?cat=28");


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
            if(strpos($tweet, "//@") != FALSE) {
                $tweet = substr($tweet, 0, strpos($tweet, "//@")); //对于转发的微博 只获取主人的内容
            }
            $id = (string)$w['id']; //获取微博ID
            if(!$this->is_new_cmd($id)){ //避免重复执行主人的命令
                continue;
            } 

            if(strpos($tweet, DOWNLOAD_CMD)) {
                $this->download_file($tweet, $id);
            } else if(strpos($tweet, POST_GIRL_PIC_CMD)) {
                $this->post_girl_pic($tweet);
            }
        }
    }

    function download_file($tweet, $id) {
        preg_match("/http:\/\/t.cn\/[a-z0-9A-Z]*/", $tweet, $matches);
        $url = $matches[0];

        $success = TRUE;
        if($this->is_download($url) === FALSE) { //没有被下载过
            $success = $this->start_download($url);
        }
        $this->finish_download($tweet, $id, $success);
    }

    /*
     * 从微薄中提取出@的所有用户 但不包含自己和主人
     * @param $tweet string 微博内容
     * @return string
     */
    function get_revicers($tweet) {
        $all_revicers = "";

        for($i=0; $i<strlen($tweet); $i++) {
            if($tweet[$i] == '@') {
                $name = '';
                while($tweet[$i] != ' ' && $tweet[$i] != ':' &&$i<strlen($tweet)) {
                    $name .= $tweet[$i++];
                }
                if(strpos($all_revicers, $name) === FALSE && strcmp($name, MY_NAME) != 0  && strcmp($name, MASTER_NAME) != 0) {
                    $all_revicers .= $name." ";
                }
            }
        }
        return $all_revicers;
    }

    /*
     * 判断id 为$weibo_id 的微博是否被处理过 没有被处理过将自动存到log里
     * @param $weibo_id 微博id
     * @return bool
     */
    function is_new_cmd($weibo_id) {
        $contents = file_get_contents(CMD_LOG_FILE);
        $cmd_lists = explode("\n", $contents);
        if(array_search($weibo_id, $cmd_lists) === FALSE) {
            file_put_contents(CMD_LOG_FILE, $weibo_id."\n", FILE_APPEND);
            return TRUE;
        }
        return FALSE;
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
    function finish_download($tweet, $id, $success) {
        $all_revicers = $this->get_revicers($tweet);
        if($all_revicers == "") {
            $status = "主人".MASTER_NAME;
        } else {
            $status = "主人".MASTER_NAME." 的朋友 ".$all_revicers;
        }

        if($success) {
            $status .= SUCCESS_DOWNLOAD_MSG;
        } else {
            $status .= FAIL_DOWNLOAD_MSG;
        }

        $this->v->send_comment($id, $status); 
        $this->v->repost($id, $status);
    }

    function post_girl_pic($tweet) {
        global $img_urls;
        $img_host = $img_urls[array_rand($img_urls)];
        $cmd = "wget -O /tmp/tupian.html $img_host && grep 'retina=\"http:\/\/.*jpg' /tmp/tupian.html | sed 's/^.*retina=\"//g' | sed 's/\".*$//g' | awk '{a[NR]=$0}END{srand();i=int(rand()*NR+1);print a[i]}'";
        exec($cmd, $output, $tmp);
        $img_url = $output[0];

        $all_revicers = $this->get_revicers($tweet);
        if($all_revicers == "") {
            $status = "请主人".MASTER_NAME." 验图";
        } else {
            $status = "请主人".MASTER_NAME." 的朋友 ".$all_revicers." 验图";
        }
        $this->v->upload($status, $img_url);
    }

}

    $my_maid = new Maid();
    $my_maid->start_work();

?>
