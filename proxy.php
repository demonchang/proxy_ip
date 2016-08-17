<?php 
/**
*抓取代理ip是否过期
**/
require_once('user_agent.php');

$agent = new Agent();
$redis = new Redis(); 
$redis->connect('127.0.0.1',6379); //配置成远程的
$redis->select(1);
$start = 1;
$end = 3;//前三页为当天验证过的IP

for ($i=$start; $i < ($end+1); $i++) { 

	$user_agent = $agent->getOneAgent();  //换个ua
	$get_proxy_url = 'http://www.kuaidaili.com/free/inha/'.$i.'/';

	$html = request($user_agent, 'GET', $get_proxy_url);
	preg_match_all('#<td data-title="IP">(.*?)</td>[\s]*?<td data-title="PORT">(.*?)</td>#', $html, $proxys);

	if ($proxys) {
		$count_proxys = count($proxys[0]);
		for ($j=0; $j < $count_proxys; $j++) { 
			//ip:port
			$proxy = $proxys[1][$j].':'.$proxys[2][$j]; 
			//$proxy = 'http://47.88.104.219:80';
			$user_agent = $agent->getOneAgent();  //勤换ua
			$result = request($user_agent,'GET','http://www.baidu.com', $proxy); //请求百度结果作为验证代理是否有效
			if ($result) {
				$redis->zincrby('proxy', 1, $proxy);
			}

		}
	}
}

function request($user_agent, $method, $url, $fields='', $proxy='')
{
	$ch = curl_init($url);
	if ($proxy) {
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
	
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	if ($method === 'POST')
	{
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	}
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

 ?>