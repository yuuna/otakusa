<?php
namespace otakusa;

class Routes{
	public function out($patterns){
		$pathinfo = preg_replace("/(.*?)\?.*/","\\1",(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null));

		foreach($patterns as $k => $v){
			$preg_str = (empty($k) ? '' : '\/').str_replace(array('\/','/','@#S'),array('@#S','\/','\/'),$k);

			if(preg_match('/^'.$preg_str.'[\/]{0,1}$/',$pathinfo,$p)){
				var_dump($patterns[$k]);
			}
		}
	}
}
