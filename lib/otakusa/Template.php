<?php
namespace otakusa;
/**
 * テンプレートを処理する
 * @author tokushima
 * @var mixed{} $vars バインドされる変数
 * @var boolean $secure https://をhttp://に置換するか
 * @var string $put_block ブロックファイル
 * @var string $template_super 継承元テンプレート
 * @var string $media_url メディアファイルへのURLの基点
 * @conf boolean $display_exception 例外が発生した場合にメッセージを表示するか
 */
class Template{
	private $file;
	private $selected_template;
	private $selected_src;

	private $secure = false;
	private $vars = array();
	private $put_block;
	private $template_super;
	private $media_url;

	public function __construct($media_url=null){
		if($media_url !== null) $this->media_url($media_url);
	}
	/**
	 * メディアファイルへのURLの基点を設定
	 * @param string $url
	 * @return $this
	 */
	public function media_url($url){
		$this->media_url = str_replace("\\",'/',$url);
		if(!empty($this->media_url) && substr($this->media_url,-1) !== '/') $this->media_url = $this->media_url.'/';
	}
	public function template_super($path){
		$this->template_super = $path;
	}
	public function put_block($path){
		$this->put_block = $path;
	}
	public function secure($bool){
		$this->secure = (boolean)$bool;
	}
	public function vars($key,$value){
		$this->vars[$key] = $value;
	}
	/**
	 * 出力する
	 * @param string $file
	 * @param string $template_name
	 */
	final public function output($file,$template_name=null){
		print($this->read($file,$template_name));
		exit;
	}
	/**
	 * ファイルを読み込んで結果を返す
	 * @param string $file
	 * @param string $template_name
	 * @return string
	 */
	final public function read($file,$template_name=null){
		if(!is_file($file) && strpos($file,'://') === false) throw new \InvalidArgumentException($file.' not found');
		$this->file = $file;
		$cname = md5($this->template_super.$this->put_block.$this->file.$this->selected_template);

		if(!empty($this->put_block)){
			$src = $this->read_src($this->put_block);
			if(strpos($src,'rt:extends') !== false){
				Xml::set($x,'<:>'.$src.'</:>');
				foreach($x->in('rt:extends') as $ext) $src = str_replace($ext->plain(),'',$src);
			}
			$src = sprintf('<rt:extends href="%s" />\n',$file).$src;
			$this->file = $this->put_block;
		}else{
			$src = $this->read_src($this->file);
		}
		$src = $this->replace($src,$template_name);
		return $this->execute($src);
	}
	private function cname(){
		return md5($this->put_block.$this->file.$this->selected_template);
	}
	/**
	 * 文字列から結果を返す
	 * @param string $src
	 * @param string $template_name
	 * @return string
	 */
	final public function get($src,$template_name=null){
		return $this->execute($this->replace($src,$template_name));
	}
	private function execute($src){
		$src = $this->exec($src);
		$src = str_replace(array('#PS#','#PE#'),array('<?','?>'),$this->html_reform($src));
		return $src;
	}
	private function replace($src,$template_name){
		$this->selected_template = $template_name;
		$src = preg_replace("/([\w])\->/","\\1__PHP_ARROW__",$src);
		$src = str_replace(array("\\\\","\\\"","\\'"),array('__ESC_DESC__','__ESC_DQ__','__ESC_SQ__'),$src);
		$src = $this->replace_xtag($src);
		// FIXME init_template
		$src = $this->rtcomment($this->rtblock($this->rttemplate($src),$this->file));
		$this->selected_src = $src;
		// FIXME before_template
		$src = $this->rtif($this->rtloop($this->rtunit($this->html_form($this->html_list($src)))));
		// FIXME after_template
		$src = str_replace('__PHP_ARROW__','->',$src);
		$src = $this->parse_print_variable($src);
		$php = array(' ?>','<?php ','->');
		$str = array('__PHP_TAG_END__','__PHP_TAG_START__','__PHP_ARROW__');
		$src = str_replace($php,$str,$src);
		$src = $this->parse_url($src,$this->media_url);
		$src = str_replace($str,$php,$src);
		$src = str_replace(array('__ESC_DQ__','__ESC_SQ__','__ESC_DESC__'),array("\\\"","\\'","\\\\"),$src);
		return $src;
	}
	private function exec($_src_){
		// FIXME before_exec_template
		$this->vars('_t_',new static());
		ob_start();
			if(is_array($this->vars) && !empty($this->vars)) extract($this->vars);
			eval('?>'.$_src_);
		$_eval_src_ = ob_get_clean();

		if(strpos($_eval_src_,'Parse error: ') !== false){
			if(preg_match("/Parse error\:(.+?) in .+eval\(\)\'d code on line (\d+)/",$_eval_src_,$match)){
				list($msg,$line) = array(trim($match[1]),((int)$match[2]));
				$lines = explode("\n",$_src_);
				$plrp = substr_count(implode("\n",array_slice($lines,0,$line)),"<?php 'PLRP'; ?>\n");
				$this->error_msg($msg.' on line '.($line-$plrp).' [compile]: '.trim($lines[$line-1]));

				$lines = explode("\n",$this->selected_src);
				$this->error_msg($msg.' on line '.($line-$plrp).' [plain]: '.trim($lines[$line-1-$plrp]));
			}
		}
		$this->selected_src = null;
		// FIXME after_exec_template
		return $_eval_src_;
	}
	/**
	 * エラー時の処理
	 * @param string $str
	 */
	public function parse_error($str){
		// FIXME print($str);
	}
	/**
	 * 出力エラーの処理
	 * @param \Excpeption $e
	 */
	public function print_error(\Exception $e){
		// FIXME print($e->getMessage());
	}
	private function error_handler($errno,$errstr,$errfile,$errline){
		throw new \ErrorException($errstr,0,$errno,$errfile,$errline);
	}
	private function replace_xtag($src){
		if(preg_match_all("/<\?(?!php[\s\n])[\w]+ .*?\?>/s",$src,$null)){
			foreach($null[0] as $value) $src = str_replace($value,'#PS#'.substr($value,2,-2).'#PE#',$src);
		}
		return $src;
	}
	private function parse_url($src,$media){
		if(!empty($media) && substr($media,-1) !== '/') $media = $media.'/';
		$secure_base = ($this->secure) ? str_replace('http://','https://',$media) : null;
		if(preg_match_all("/<([^<\n]+?[\s])(src|href|background)[\s]*=[\s]*([\"\'])([^\\3\n]+?)\\3[^>]*?>/i",$src,$match)){
			foreach($match[2] as $k => $p){
				$t = null;
				if(strtolower($p) === 'href') list($t) = (preg_split("/[\s]/",strtolower($match[1][$k])));
				$src = $this->replace_parse_url($src,(($this->secure && $t !== 'a') ? $secure_base : $media),$match[0][$k],$match[4][$k]);
			}
		}
		if(preg_match_all("/[^:]:[\040]*url\(([^\n]+?)\)/",$src,$match)){
			if($this->secure) $media = $secure_base;
			foreach($match[1] as $key => $param) $src = $this->replace_parse_url($src,$media,$match[0][$key],$match[1][$key]);
		}
		return $src;
	}
	private function replace_parse_url($src,$base,$dep,$rep){
		if(!preg_match("/(^[\w]+:\/\/)|(^__PHP_TAG_START)|(^\{\\$)|(^\w+:)|(^[#\?])/",$rep)){
			$src = str_replace($dep,str_replace($rep,$this->ab_path($base,$rep),$dep),$src);
		}
		return $src;
	}
	private function ab_path($a,$b){
		if($b === '' || $b === null) return $a;
		if($a === '' || $a === null || preg_match("/^[a-zA-Z]+:/",$b)) return $b;
		if(preg_match("/^[\w]+\:\/\/[^\/]+/",$a,$h)){
			$a = preg_replace("/^(.+?)[".(($b[0] === '#') ? '#' : "#\?")."].*$/","\\1",$a);
			if($b[0] == '#' || $b[0] == '?') return $a.$b;
			if(substr($a,-1) != '/') $b = (substr($b,0,2) == './') ? '.'.$b : (($b[0] != '.' && $b[0] != '/') ? '../'.$b : $b);
			if($b[0] == '/' && isset($h[0])) return $h[0].$b;
		}else if($b[0] == '/'){
			return $b;
		}
		$p = array(array('://','/./','//'),array('#R#','/','/'),array("/^\/(.+)$/","/^(\w):\/(.+)$/"),array("#T#\\1","\\1#W#\\2",''),array('#R#','#T#','#W#'),array('://','/',':/'));
		$a = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$a));
		$b = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$b));
		$d = $t = $r = '';
		if(strpos($a,'#R#')){
			list($r) = explode('/',$a,2);
			$a = substr($a,strlen($r));
			$b = str_replace('#T#','',$b);
		}
		$al = preg_split("/\//",$a,-1,PREG_SPLIT_NO_EMPTY);
		$bl = preg_split("/\//",$b,-1,PREG_SPLIT_NO_EMPTY);

		for($i=0;$i<sizeof($al)-substr_count($b,'../');$i++){
			if($al[$i] != '.' && $al[$i] != '..') $d .= $al[$i].'/';
		}
		for($i=0;$i<sizeof($bl);$i++){
			if($bl[$i] != '.' && $bl[$i] != '..') $t .= '/'.$bl[$i];
		}
		$t = (!empty($d)) ? substr($t,1) : $t;
		$d = (!empty($d) && $d[0] != '/' && substr($d,0,3) != '#T#' && !strpos($d,'#W#')) ? '/'.$d : $d;
		return str_replace($p[4],$p[5],$r.$d.$t);
	}
	private function read_src($filename){
		$src = file_get_contents($filename);
		return (strpos($filename,'://') !== false) ? $this->parse_url($src,dirname($filename)) : $src;
	}
	private function rttemplate($src){
		$values = array();
		$bool = false;
		while(Xml::set($tag,$src,'rt:template')){
			$src = str_replace($tag->plain(),'',$src);
			$values[$tag->in_attr('name')] = $tag->value();
			$src = str_replace($tag->plain(),'',$src);
			$bool = true;
		}
		if(!empty($this->selected_template)){
			if(!array_key_exists($this->selected_template,$values)) throw new \LogicException('undef rt:template '.$this->selected_template);
			return $values[$this->selected_template];
		}
		return ($bool) ? implode($values) : $src;
	}
	private function rtblock($src,$filename){
		if(strpos($src,'rt:block') !== false || strpos($src,'rt:extends') !== false){
			$base_filename = $filename;
			$blocks = $paths = array();
			while(Xml::set($e,'<:>'.$this->rtcomment($src).'</:>','rt:extends') !== false){
				$href = $this->ab_path(str_replace("\\",'/',dirname($filename)),$e->in_attr('href'));
				if(!$e->is_attr('href') || !is_file($href)) throw new \LogicException('href not found '.$filename);
				if($filename === $href) throw new \LogicException('Infinite Recursion Error'.$filename);
				Xml::set($bx,'<:>'.$this->rtcomment($src).'</:>',':');
				foreach($bx->in('rt:block') as $b){
					$n = $b->in_attr('name');
					if(!empty($n) && !array_key_exists($n,$blocks)){
						$blocks[$n] = $b->value();
						$paths[$n] = $filename;
					}
				}
				$src = $this->rttemplate($this->replace_xtag($this->read_src($filename = $href)));
				$this->selected_template = $e->in_attr('name');
			}
			// FIXME before_block_template
			if(empty($blocks)){
				if(Xml::set($bx,'<:>'.$src.'</:>')){
					foreach($bx->in('rt:block') as $b) $src = str_replace($b->plain(),$b->value(),$src);
				}
			}else{
				if(!empty($this->template_super)) $src = $this->read_src($this->ab_path(str_replace("\\",'/',dirname($base_filename)),$this->template_super));
				while(Xml::set($b,$src,'rt:block')){
					$n = $b->in_attr('name');
					$src = str_replace($b->plain(),(array_key_exists($n,$blocks) ? $blocks[$n] : $b->value()),$src);
				}
			}
			$this->file = $filename;
		}
		return $src;
	}
	private function rtcomment($src){
		while(Xml::set($tag,$src,'rt:comment')) $src = str_replace($tag->plain(),'',$src);
		return $src;
	}
	private function rtunit($src){
		if(strpos($src,'rt:unit') !== false){
			while(Xml::set($tag,$src,'rt:unit')){
				$tag->escape(false);
				$uniq = uniqid('');
				$param = $tag->in_attr('param');
				$var = '$'.$tag->in_attr('var','_var_'.$uniq);
				$offset = $tag->in_attr('offset',1);
				$total = $tag->in_attr('total','_total_'.$uniq);
				$cols = ($tag->is_attr('cols')) ? (ctype_digit($tag->in_attr('cols')) ? $tag->in_attr('cols') : $this->variable_string($this->parse_plain_variable($tag->in_attr('cols')))) : 1;
				$rows = ($tag->is_attr('rows')) ? (ctype_digit($tag->in_attr('rows')) ? $tag->in_attr('rows') : $this->variable_string($this->parse_plain_variable($tag->in_attr('rows')))) : 0;
				$value = $tag->value();

				$cols_count = '$_ucount_'.$uniq;
				$cols_total = '$'.$tag->in_attr('cols_total','_cols_total_'.$uniq);
				$rows_count = '$'.$tag->in_attr('counter','_counter_'.$uniq);
				$rows_total = '$'.$tag->in_attr('rows_total','_rows_total_'.$uniq);
				$ucols = '$_ucols_'.$uniq;
				$urows = '$_urows_'.$uniq;
				$ulimit = '$_ulimit_'.$uniq;
				$ufirst = '$_ufirst_'.$uniq;
				$ufirstnm = '_ufirstnm_'.$uniq;

				$ukey = '_ukey_'.$uniq;
				$uvar = '_uvar_'.$uniq;

				$src = str_replace(
							$tag->plain(),
							sprintf('<?php %s=%s; %s=%s; %s=%s=1; %s=null; %s=%s*%s; %s=array(); ?>'
									.'<rt:loop param="%s" var="%s" key="%s" total="%s" offset="%s" first="%s">'
										.'<?php if(%s <= %s){ %s[$%s]=$%s; } ?>'
										.'<rt:first><?php %s=$%s; ?></rt:first>'
										.'<rt:last><?php %s=%s; ?></rt:last>'
										.'<?php if(%s===%s){ ?>'
											.'<?php if(isset(%s)){ $%s=""; } ?>'
											.'<?php %s=sizeof(%s); ?>'
											.'<?php %s=ceil($%s/%s); ?>'
											.'%s'
											.'<?php %s=array(); %s=null; %s=1; %s++; ?>'
										.'<?php }else{ %s++; } ?>'
									.'</rt:loop>'
									,$ucols,$cols,$urows,$rows,$cols_count,$rows_count,$ufirst,$ulimit,$ucols,$urows,$var
									,$param,$uvar,$ukey,$total,$offset,$ufirstnm
										,$cols_count,$ucols,$var,$ukey,$uvar
										,$ufirst,$ufirstnm
										,$cols_count,$ucols
										,$cols_count,$ucols
											,$ufirst,$ufirstnm
											,$cols_total,$var
											,$rows_total,$total,$ucols
											,$value
											,$var,$ufirst,$cols_count,$rows_count
										,$cols_count
							)
							.($tag->is_attr('rows') ?
								sprintf('<?php for(;%s<=%s;%s++){ %s=array(); ?>%s<?php } ?>',$rows_count,$rows,$rows_count,$var,$value) : ''
							)
							,$src
						);
			}
		}
		return $src;
	}
	private function rtloop($src){
		if(strpos($src,'rt:loop') !== false){
			while(Xml::set($tag,$src,'rt:loop')){
				$tag->escape(false);
				$param = ($tag->is_attr('param')) ? $this->variable_string($this->parse_plain_variable($tag->in_attr('param'))) : null;
				$offset = ($tag->is_attr('offset')) ? (ctype_digit($tag->in_attr('offset')) ? $tag->in_attr('offset') : $this->variable_string($this->parse_plain_variable($tag->in_attr('offset')))) : 1;
				$limit = ($tag->is_attr('limit')) ? (ctype_digit($tag->in_attr('limit')) ? $tag->in_attr('limit') : $this->variable_string($this->parse_plain_variable($tag->in_attr('limit')))) : 0;
				if(empty($param) && $tag->is_attr('range')){
					list($range_start,$range_end) = explode(',',$tag->in_attr('range'),2);
					$range = ($tag->is_attr('range_step')) ? sprintf('range(%d,%d,%d)',$range_start,$range_end,$tag->in_attr('range_step')) :
																sprintf('range("%s","%s")',$range_start,$range_end);
					$param = sprintf('array_combine(%s,%s)',$range,$range);
				}
				$is_fill = false;
				$uniq = uniqid('');
				$even = $tag->in_attr('even_value','even');
				$odd = $tag->in_attr('odd_value','odd');
				$evenodd = '$'.$tag->in_attr('evenodd','loop_evenodd');

				$first_value = $tag->in_attr('first_value','first');
				$first = '$'.$tag->in_attr('first','_first_'.$uniq);
				$first_flg = '$__isfirst__'.$uniq;
				$last_value = $tag->in_attr('last_value','last');
				$last = '$'.$tag->in_attr('last','_last_'.$uniq);
				$last_flg = '$__islast__'.$uniq;
				$shortfall = '$'.$tag->in_attr('shortfall','_DEFI_'.$uniq);

				$var = '$'.$tag->in_attr('var','_var_'.$uniq);
				$key = '$'.$tag->in_attr('key','_key_'.$uniq);
				$total = '$'.$tag->in_attr('total','_total_'.$uniq);
				$vtotal = '$__vtotal__'.$uniq;
				$counter = '$'.$tag->in_attr('counter','_counter_'.$uniq);
				$loop_counter = '$'.$tag->in_attr('loop_counter','_loop_counter_'.$uniq);
				$reverse = (strtolower($tag->in_attr('reverse') === 'true'));

				$varname = '$_'.$uniq;
				$countname = '$__count__'.$uniq;
				$lcountname = '$__vcount__'.$uniq;
				$offsetname	= '$__offset__'.$uniq;
				$limitname = '$__limit__'.$uniq;

				$value = $tag->value();
				$empty_value = null;
				while(Xml::set($subtag,$value,'rt:loop')){
					$value = $this->rtloop($value);
				}
				while(Xml::set($subtag,$value,'rt:first')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$first
					,(($subtag->in_attr('last') === 'false') ? sprintf(' && (%s !== 1) ',$total) : '')
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(Xml::set($subtag,$value,'rt:middle')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(!isset(%s) && !isset(%s)){ ?>%s<?php } ?>',$first,$last
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(Xml::set($subtag,$value,'rt:last')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$last
					,(($subtag->in_attr('first') === 'false') ? sprintf(' && (%s !== 1) ',$vtotal) : '')
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(Xml::set($subtag,$value,'rt:fill')){
					$is_fill = true;
					$value = str_replace($subtag->plain(),sprintf('<?php if(%s > %s){ ?>%s<?php } ?>',$lcountname,$total
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				$value = $this->rtif($value);
				if(preg_match("/^(.+)<rt\:else[\s]*.*?>(.+)$/ims",$value,$match)){
					list(,$value,$empty_value) = $match;
				}
				$src = str_replace(
							$tag->plain(),
							sprintf("<?php try{ ?>"
									."<?php "
										." %s=%s;"
										." if(is_array(%s)){"
											." if(%s){ krsort(%s); }"
											." %s=%s=sizeof(%s); %s=%s=1; %s=%s; %s=((%s>0) ? (%s + %s) : 0); "
											." %s=%s=false; %s=0; %s=%s=null;"
											." if(%s){ for(\$i=0;\$i<(%s+%s-%s);\$i++){ %s[] = null; } %s=sizeof(%s); }"
											." foreach(%s as %s => %s){"
												." if(%s <= %s){"
													." if(!%s){ %s=true; %s='%s'; }"
													." if((%s > 0 && (%s+1) == %s) || %s===%s){ %s=true; %s='%s'; %s=(%s-%s+1) * -1;}"
													." %s=((%s %% 2) === 0) ? '%s' : '%s';"
													." %s=%s; %s=%s;"
													." ?>%s<?php "
													." %s=%s=null;"
													." %s++;"
												." }"
												." %s++;"
												." if(%s > 0 && %s >= %s){ break; }"
											." }"
											." if(!%s){ ?>%s<?php } "
											." unset(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s);"
										." }"
									." ?>"
									."<?php }catch(\\Exception \$e){ \$_t_->print_error(\$e); } ?>"
									,$varname,$param
									,$varname
										,(($reverse) ? 'true' : 'false'),$varname
										,$vtotal,$total,$varname,$countname,$lcountname,$offsetname,$offset,$limitname,$limit,$offset,$limit
										,$first_flg,$last_flg,$shortfall,$first,$last
										,($is_fill ? 'true' : 'false'),$offsetname,$limitname,$total,$varname,$vtotal,$varname
										,$varname,$key,$var
											,$offsetname,$lcountname
												,$first_flg,$first_flg,$first,str_replace("'","\\'",$first_value)
												,$limitname,$lcountname,$limitname,$lcountname,$vtotal,$last_flg,$last,str_replace("'","\\'",$last_value),$shortfall,$lcountname,$limitname
												,$evenodd,$countname,$even,$odd
												,$counter,$countname,$loop_counter,$lcountname
												,$value
												,$first,$last
												,$countname
											,$lcountname
											,$limitname,$lcountname,$limitname
									,$first_flg,$empty_value
									,$var,$counter,$key,$countname,$lcountname,$offsetname,$limitname,$varname,$first,$first_flg,$last,$last_flg
							)
							,$src
						);
			}
		}
		return $src;
	}
	private function rtif($src){
		if(strpos($src,'rt:if') !== false){
			while(Xml::set($tag,$src,'rt:if')){
				$tag->escape(false);
				if(!$tag->is_attr('param')) throw new \LogicException('if');
				$arg1 = $this->variable_string($this->parse_plain_variable($tag->in_attr('param')));

				if($tag->is_attr('value')){
					$arg2 = $this->parse_plain_variable($tag->in_attr('value'));
					if($arg2 == 'true' || $arg2 == 'false' || ctype_digit((string)$arg2)){
						$cond = sprintf('<?php if(%s === %s || %s === "%s"){ ?>',$arg1,$arg2,$arg1,$arg2);
					}else{
						if($arg2 === '' || $arg2[0] != '$') $arg2 = '"'.$arg2.'"';
						$cond = sprintf('<?php if(%s === %s){ ?>',$arg1,$arg2);
					}
				}else{
					$uniq = uniqid('$I');
					$cond = sprintf("<?php try{ %s=%s; }catch(\\Exception \$e){ %s=null; } ?>",$uniq,$arg1,$uniq)
								.sprintf('<?php if(%s !== null && %s !== false && ( (!is_string(%s) && !is_array(%s)) || (is_string(%s) && %s !== "") || (is_array(%s) && !empty(%s)) ) ){ ?>',$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq);
				}
				$src = str_replace(
							$tag->plain()
							,'<?php try{ ?>'.$cond
								.preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$tag->value())
							."<?php } ?>"
							."<?php }catch(\\Exception \$e){ \$_t_->print_error(\$e); } ?>"
							,$src
						);
			}
		}
		return $src;
	}
	private function parse_print_variable($src){
		foreach($this->match_variable($src) as $variable){
			$name = $this->parse_plain_variable($variable);
			$value = '<?php try{ @print('.$name.'); ?>'
						."<?php }catch(\\Exception \$e){ \$_t_->print_error(\$e); } ?>";
			$src = str_replace(array($variable.PHP_EOL,$variable),array($value."<?php 'PLRP'; ?>\n\n",$value),$src);
			$src = str_replace($variable,$value,$src);
		}
		return $src;
	}
	private function match_variable($src){
		$hash = array();
		while(preg_match("/({(\\$[\$\w][^\t]*)})/s",$src,$vars,PREG_OFFSET_CAPTURE)){
			list($value,$pos) = $vars[1];
			if($value == "") break;
			if(substr_count($value,'}') > 1){
				for($i=0,$start=0,$end=0;$i<strlen($value);$i++){
					if($value[$i] == '{'){
						$start++;
					}else if($value[$i] == '}'){
						if($start == ++$end){
							$value = substr($value,0,$i+1);
							break;
						}
					}
				}
			}
			$length	= strlen($value);
			$src = substr($src,$pos + $length);
			$hash[sprintf('%03d_%s',$length,$value)] = $value;
		}
		krsort($hash,SORT_STRING);
		return $hash;
	}
	private function parse_plain_variable($src){
		while(true){
			$array = $this->match_variable($src);
			if(sizeof($array) <= 0)	break;
			foreach($array as $v){
				$tmp = $v;
				if(preg_match_all("/([\"\'])([^\\1]+?)\\1/",$v,$match)){
					foreach($match[2] as $value) $tmp = str_replace($value,str_replace('.','__PERIOD__',$value),$tmp);
				}
				$src = str_replace($v,preg_replace('/([\w\)\]])\./','\\1->',substr($tmp,1,-1)),$src);
			}
		}
		return str_replace('[]','',str_replace('__PERIOD__','.',$src));
	}
	private function variable_string($src){
		return (empty($src) || isset($src[0]) && $src[0] == '$') ? $src : '$'.$src;
	}
	private function html_reform($src){
		if(strpos($src,'rt:aref') !== false){
			Xml::set($tag,'<:>'.$src.'</:>');
			foreach($tag->in('form') as $obj){
				if($obj->is_attr('rt:aref')){
					$bool = ($obj->in_attr('rt:aref') === 'true');
					$obj->rm_attr('rt:aref');
					$obj->escape(false);
					$value = $obj->get();

					if($bool){
						foreach($obj->in(array('input','select','textarea')) as $tag){
							if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
								switch(strtolower($tag->in_attr('type','text'))){
									case 'button':
									case 'submit':
									case 'file':
										break;
									default:
										$tag->attr('rt:ref','true');
										$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
								}
							}
						}
						$value = $this->exec($this->parse_print_variable($this->html_input($obj->get())));
					}
					$src = str_replace($obj->plain(),$value,$src);
				}
			}
		}
		return $src;
	}
	private function html_form($src){
		Xml::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in('form') as $obj){
			if($this->is_reference($obj)){
				$obj->escape(false);
				foreach($obj->in(array('input','select','textarea')) as $tag){
					if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
						switch(strtolower($tag->in_attr('type','text'))){
							case 'button':
							case 'submit':
								break;
							case 'file':
								$obj->attr('enctype','multipart/form-data');
								$obj->attr('method','post');
								break;
							default:
								$tag->attr('rt:ref','true');
								$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
						}
					}
				}
				$src = str_replace($obj->plain(),$obj->get(),$src);
			}
		}
		return $this->html_input($src);
	}
	private function no_exception_str($value){
		return $value;
	}
	private function html_input($src){
		Xml::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in(array('input','textarea','select')) as $obj){
			if('' != ($originalName = $obj->in_attr('name',$obj->in_attr('id','')))){
				$obj->escape(false);
				$type = strtolower($obj->in_attr('type','text'));
				$name = $this->parse_plain_variable($this->form_variable_name($originalName));
				$lname = strtolower($obj->name());
				$change = false;
				$uid = uniqid();

				if(substr($originalName,-2) !== '[]'){
					if($type == 'checkbox'){
						if($obj->in_attr('rt:multiple','true') === 'true') $obj->attr('name',$originalName.'[]');
						$obj->rm_attr('rt:multiple');
						$change = true;
					}else if($obj->is_attr('multiple') || $obj->in_attr('multiple') === 'multiple'){
						$obj->attr('name',$originalName.'[]');
						$obj->rm_attr('multiple');
						$obj->attr('multiple','multiple');
						$change = true;
					}
				}else if($obj->in_attr('name') !== $originalName){
					$obj->attr('name',$originalName);
					$change = true;
				}
				if($obj->is_attr('rt:param') || $obj->is_attr('rt:range')){
					switch($lname){
						case 'select':
							$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" key="%s" offset="%s" limit="%s" reverse="%s" evenodd="%s" even_value="%s" odd_value="%s" range="%s" range_step="%s">'
											.'<option value="{$%s}">{$%s}</option>'
											.'</rt:loop>'
											,$obj->in_attr('rt:param'),$obj->in_attr('rt:var','loop_var'.$uid),$obj->in_attr('rt:counter','loop_counter'.$uid)
											,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:offset','0'),$obj->in_attr('rt:limit','0')
											,$obj->in_attr('rt:reverse','false')
											,$obj->in_attr('rt:evenodd','loop_evenodd'.$uid),$obj->in_attr('rt:even_value','even'),$obj->in_attr('rt:odd_value','odd')
											,$obj->in_attr('rt:range'),$obj->in_attr('rt:range_step',1)
											,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:var','loop_var'.$uid)
							);
							$obj->value($this->rtloop($value));
							if($obj->is_attr('rt:null')) $obj->value('<option value="">'.$obj->in_attr('rt:null').'</option>'.$obj->value());
					}
					$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter','rt:offset','rt:limit','rt:null','rt:evenodd'
									,'rt:range','rt:range_step','rt:even_value','rt:odd_value');
					$change = true;
				}
				if($this->is_reference($obj)){
					switch($lname){
						case 'textarea':
							$obj->value($this->no_exception_str(sprintf('{$_t_.htmlencode(%s)}',((preg_match("/^{\$(.+)}$/",$originalName,$match)) ? '{$$'.$match[1].'}' : '{$'.$originalName.'}'))));
							break;
						case 'select':
							$select = $obj->value();
							foreach($obj->in('option') as $option){
								$option->escape(false);
								$value = $this->parse_plain_variable($option->in_attr('value'));
								if(empty($value) || $value[0] != '$') $value = sprintf("'%s'",$value);
								$option->rm_attr('selected');
								$option->plain_attr($this->check_selected($name,$value,'selected'));
								$select = str_replace($option->plain(),$option->get(),$select);
							}
							$obj->value($select);
							break;
						case 'input':
							switch($type){
								case 'checkbox':
								case 'radio':
									$value = $this->parse_plain_variable($obj->in_attr('value','true'));
									$value = (substr($value,0,1) != '$') ? sprintf("'%s'",$value) : $value;
									$obj->rm_attr('checked');
									$obj->plain_attr($this->check_selected($name,$value,'checked'));
									break;
								case 'text':
								case 'hidden':
								case 'password':
								case 'search':
								case 'url':
								case 'email':
								case 'tel':
								case 'datetime':
								case 'date':
								case 'month':
								case 'week':
								case 'time':
								case 'datetime-local':
								case 'number':
								case 'range':
								case 'color':
									$obj->attr('value',$this->no_exception_str(sprintf('{$_t_.htmlencode(%s)}',
																((preg_match("/^\{\$(.+)\}$/",$originalName,$match)) ?
																	'{$$'.$match[1].'}' :
																	'{$'.$originalName.'}'))));
									break;
							}
							break;
					}
					$change = true;
				}else if($obj->is_attr('rt:ref')){
					$obj->rm_attr('rt:ref');
					$change = true;
				}
				if($change){
					switch($lname){
						case 'textarea':
						case 'select':
							$obj->close_empty(false);
					}
					$src = str_replace($obj->plain(),$obj->get(),$src);
				}
			}
		}
		return $src;
	}
	private function check_selected($name,$value,$selected){
		return sprintf('<?php if('
					.'isset(%s) && (%s === %s '
										.' || (!is_array(%s) && ctype_digit((string)%s) && (string)%s === (string)%s)'
										.' || ((%s === "true" || %s === "false") ? (%s === (%s == "true")) : false)'
										.' || in_array(%s,((is_array(%s)) ? %s : (is_null(%s) ? array() : array(%s))),true) '
									.') '
					.'){print(" %s=\"%s\"");} ?>'
					,$name,$name,$value
					,$name,$name,$name,$value
					,$value,$value,$name,$value
					,$value,$name,$name,$name,$name
					,$selected,$selected
				);
	}
	private function html_list($src){
		if(preg_match_all('/<(table|ul|ol)\s[^>]*rt\:/i',$src,$m,PREG_OFFSET_CAPTURE)){
			$tags = array();
			foreach($m[1] as $k => $v){
				if(Xml::set($tag,substr($src,$v[1]-1),$v[0])) $tags[] = $tag;
			}
			foreach($tags as $obj){
				$obj->escape(false);
				$name = strtolower($obj->name());
				$param = $obj->in_attr('rt:param');
				$null = strtolower($obj->in_attr('rt:null'));
				$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" '
									.'key="%s" offset="%s" limit="%s" '
									.'reverse="%s" '
									.'evenodd="%s" even_value="%s" odd_value="%s" '
									.'range="%s" range_step="%s" '
									.'shortfall="%s">'
								,$param,$obj->in_attr('rt:var','loop_var'),$obj->in_attr('rt:counter','loop_counter')
								,$obj->in_attr('rt:key','loop_key'),$obj->in_attr('rt:offset','0'),$obj->in_attr('rt:limit','0')
								,$obj->in_attr('rt:reverse','false')
								,$obj->in_attr('rt:evenodd','loop_evenodd'),$obj->in_attr('rt:even_value','even'),$obj->in_attr('rt:odd_value','odd')
								,$obj->in_attr('rt:range'),$obj->in_attr('rt:range_step',1)
								,$tag->in_attr('rt:shortfall','_DEFI_'.uniqid())
							);
				$rawvalue = $obj->value();
				if($name == 'table' && Xml::set($t,$rawvalue,'tbody')){
					$t->escape(false);
					$t->value($value.$this->table_tr_even_odd($t->value(),(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>');
					$value = str_replace($t->plain(),$t->get(),$rawvalue);
				}else{
					$value = $value.$this->table_tr_even_odd($rawvalue,(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>';
				}
				$obj->value($this->html_list($value));
				$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter','rt:offset','rt:limit','rt:null','rt:evenodd','rt:range'
								,'rt:range_step','rt:even_value','rt:odd_value','rt:shortfall');
				$src = str_replace($obj->plain(),
						($null === 'true') ? $this->rtif(sprintf('<rt:if param="%s">',$param).$obj->get().'</rt:if>') : $obj->get(),
						$src);
			}
		}
		return $src;
	}
	private function table_tr_even_odd($src,$name,$even_odd){
		Xml::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in($name) as $tr){
			$tr->escape(false);
			$class = ' '.$tr->in_attr('class').' ';
			if(preg_match('/[\s](even|odd)[\s]/',$class,$match)){
				$tr->attr('class',trim(str_replace($match[0],' {$'.$even_odd.'} ',$class)));
				$src = str_replace($tr->plain(),$tr->get(),$src);
			}
		}
		return $src;
	}
	private function form_variable_name($name){
		return (strpos($name,'[') && preg_match("/^(.+)\[([^\"\']+)\]$/",$name,$match)) ?
			'{$'.$match[1].'["'.$match[2].'"]'.'}' : '{$'.$name.'}';
	}
	private function is_reference(&$tag){
		$bool = ($tag->in_attr('rt:ref') === 'true');
		$tag->rm_attr('rt:ref');
		return $bool;
	}
	public function htmlencode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,'UTF-8',mb_detect_encoding($value));
			return htmlentities($value,ENT_QUOTES,'UTF-8');
		}
		return $value;
	}
}
