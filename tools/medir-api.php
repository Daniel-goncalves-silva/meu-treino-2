<?php
// Mede endpoint público csrf; mede overhead HTTP/PHP/sessão, não consulta MySQL.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$url=$argv[1]??'http://localhost/Meu-Treino-2/public/api.php?resource=csrf';
if(!in_array(parse_url($url,PHP_URL_HOST),['localhost','127.0.0.1'],true))die("Use localhost.\n");
if(!extension_loaded('curl'))die("Ative curl no PHP.\n");
$times=[];
for($i=0;$i<31;$i++){
 $h=curl_init($url);curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10]);$r=curl_exec($h);
 if($r===false || curl_getinfo($h,CURLINFO_HTTP_CODE)!==200)die("Falha na requisição.\n");
 if($i>0)$times[]=curl_getinfo($h,CURLINFO_TOTAL_TIME)*1000;curl_close($h);
}
sort($times);printf("30 amostras; aquecimento descartado\nMédia %.2f ms; mediana %.2f ms; p95 %.2f ms\n",array_sum($times)/count($times),($times[14]+$times[15])/2,$times[28]);
