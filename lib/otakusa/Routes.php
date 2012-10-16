<?php
namespace otakusa;

class Routes{
	public function out($patterns){
		$result = array();
		$pathinfo = preg_replace("/(.*?)\?.*/","\\1",(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null));

		foreach($patterns as $url => $conf){
			$preg_str = (empty($url) ? '' : '\/').str_replace(array('\/','/','@#S'),array('@#S','\/','\/'),$url);

			if(preg_match('/^'.$preg_str.'[\/]{0,1}$/',$pathinfo,$p)){
				var_dump($conf);

				if(isset($conf['action'])){
					list($package,$method) = explode('::',$conf['action']);

					try{
						$obj = $this->str_reflection($package);
						$result = call_user_func_array(array($obj,$method),$p);
					}catch(\Exception $e){

					}
					var_dump($result);
				}
				return;
			}
		}
		throw new \InvalidArgumentException('not map');
	}
	private function str_reflection($package){
		if(is_object($package)) return $package;
		$class_name = substr($package,strrpos($package,'.')+1);
		try{
			$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
			return $r->newInstance();
		}catch(\ReflectionException $e){
			if(!empty($class_name)){
				try{
					$r = new \ReflectionClass($class_name);
					return $r->newInstance();
				}catch(\ReflectionException $f){
				}
			}
			throw $e;
		}
	}
}
