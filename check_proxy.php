<?php 
/**
*验证代理ip是否过期
**/
$redis = new Redis(); 
$redis->connect('127.0.0.1',6379); 
$redis->select(1);
$proxy_arr = $redis->zrevrange('proxy', 0, -1);//all proxy .order desc

$count = count($proxy_arr);
$rem_count = 0;  //remove count
$thrend = 20; //thread num
$_counts = ceil($count/$thrend); //loop count 
for ($i=0; $i < $_counts; $i++) { 
	$end_position = ($i+1)*$thrend;  //pointer position
	$part_arr = array_slice($proxy_arr, $end_position-$thrend, $thrend);
	$data = curl_multi($part_arr);
	//var_dump($data);
	for ($j=0; $j < count($data); $j++) { 
		$res = $redis->zrem('proxy', $data[$j]);
		if ($res) {
			$rem_count += 1; 
		}
	}
	
	
}


var_dump($rem_count);

function getUA(){
	$user_agent_arr = [
	    'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.10',
	    'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.10',
	    'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.1',
	    'Mozilla/5.0 (Linux; U; Android 2.3.3; en-au; GT-I9100 Build/GINGERBREAD) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
	    'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; InfoPath.2; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 1.1.4322)',
	    'Mozilla/5.0 (Windows NT 6.1; rv:5.0) Gecko/20100101 Firefox/5.0',
	    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1',
	    'Mozilla/5.0 (BlackBerry; U; BlackBerry 9800; en) AppleWebKit/534.1+ (KHTML, like Gecko) Version/6.0.0.337 Mobile Safari/534.1+',
	    'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
	    'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.1',
	    'Mozilla/5.0 (X11; Linux i686) AppleWebKit/534.34 (KHTML, like Gecko) rekonq Safari/534.34',
	    'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB6; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; OfficeLiveConnector.1.4; OfficeLivePatch.1.3)',
	    'BlackBerry8300/4.2.2 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/107 UP.Link/6.2.3.15.0',
	    'IE 7 ? Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)',
	    'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.23) Gecko/20110920 Firefox/3.6.23 SearchToolbar/1.2',
	    'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1',
	    'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
	    'Mozilla/5.0 (Windows NT 5.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1'
	];

	$count = mt_rand(0, 17);
	$user_agent = $user_agent_arr[$count];

	return $user_agent;
}

function curl_multi($arr){

	$handle = array();
	$data = array();
	$mh = curl_multi_init();
	$i = 0;
	foreach ($arr as $proxy) {
			$user_agent = getUA();
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://www.baidu.com');
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return don't print
        	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        	curl_multi_add_handle($mh, $ch); 
        	$handle[$i++] = $ch;		
		}

	$active = null;
	do {
	    $mrc = curl_multi_exec($mh, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	 
	 
	while ($active and $mrc == CURLM_OK) {
	    
	    if(curl_multi_select($mh) === -1){
	        usleep(100);
	    }
	    do {
	        $mrc = curl_multi_exec($mh, $active);
	    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
	 
	}

    foreach($handle as $j=>$ch) {
        $content  = curl_multi_getcontent($ch);
        if (curl_errno($ch) != 0 || empty($content)) {
        	$data[] = $arr[$j];
        }
        //$data[$i] = (curl_errno($ch) == 0)? $content:false;
    }

	foreach ($handle as $ch) {
			curl_multi_remove_handle($mh, $ch);
		}

	curl_multi_close($mh);
	
	return $data;
}


?>