#TIME=5 表示为每隔5秒中执行一下脚本
TIME=10

while [ true ] 
do
    sleep $TIME
    {
        php index.php 
    }&
done

