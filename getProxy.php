<?php 
/**
* 获取代理ip
**/

//接收id字符串
$count = $_REQUEST['count'];

$redis = new Redis(); #实例化redis类
$redis->connect('127.0.0.1',6379); #连接服务器
$redis->select(1);
$proxy_arr = $redis->zrevrange('proxy', 0, $count-1);

echo json_encode($proxy_arr);exit();





?>