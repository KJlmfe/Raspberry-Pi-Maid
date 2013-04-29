#TIME=5 表示为每隔5秒中执行一下脚本
TIME=300

while [ true ] 
do
    {
        php index.php 
    }&
    sleep $TIME
done

