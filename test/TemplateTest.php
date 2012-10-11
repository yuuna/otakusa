<?php
use \otakusa\Template;

class TemplateTest extends PHPUnit_Framework_TestCase{
	/**
	 * ヒアドキュメントのようなテキストを生成する
	 * １行目のインデントに合わせてインデントが消去される
	 * @param string $text 対象の文字列
	 * @return string
	 */
	private function pre($text){
		if(!empty($text)){
			$text = str_replace("\r\n",PHP_EOL,$text);
			$lines = explode(PHP_EOL,$text);
			if(sizeof($lines) > 2){
				if(trim($lines[0]) == '') array_shift($lines);
				if(trim($lines[sizeof($lines)-1]) == '') array_pop($lines);
				return preg_match("/^([\040\t]+)/",$lines[0],$match) ? preg_replace("/^".$match[1]."/m","",implode(PHP_EOL,$lines)) : implode(PHP_EOL,$lines);
			}
		}
		return $text;
	}

	public function testComment(){
		$src = '123<rt:comment>aaaaaaaa</rt:comment>456';
		$t = new Template();
		$this->assertEquals('123456',$t->get($src));
	}
	public function testRead(){
		$src = $this->pre('
						abc {$abc}
						def {$def}
						ghi {$ghi}
				');
		$result = $this->pre('
						abc 123
						def 456
						ghi 789
					');
		$t = new Template();
		$t->vars("abc",123);
		$t->vars("def",456);
		$t->vars("ghi",789);

		$this->assertEquals($result,$t->get($src));
	}
	public function testReadSingle(){
		$src = trim('abc {$abc} def {$def} ghi {$ghi} ');
		$result = trim('abc 123 def 456 ghi 789 ');
		$t = new Template();
		$t->vars("abc",123);
		$t->vars("def",456);
		$t->vars("ghi",789);

		$this->assertEquals($result,$t->get($src));
	}

	public function testUnit(){
		$src = $this->pre('
				<rt:unit param="abc" var="unit_list" cols="3" offset="2" counter="counter">
				<rt:first>FIRST</rt:first>{$counter}{
				<rt:loop param="unit_list" var="a"><rt:first>first</rt:first>{$a}<rt:last>last</rt:last></rt:loop>
				}
				<rt:last>LAST</rt:last>
				</rt:unit>
				');
		$result = $this->pre('
				FIRST1{
				first234last}
				2{
				first567last}
				3{
				first8910last}
				LAST
				');
		$t = new Template();
		$t->vars("abc",array(1,2,3,4,5,6,7,8,9,10));
		$this->assertEquals($result,$t->get($src));
	}
	public function testUnitRowFill(){
		$src = $this->pre('<rt:unit param="abc" var="abc_var" cols="3" rows="3">[<rt:loop param="abc_var" var="a" limit="3"><rt:fill>0<rt:else />{$a}</rt:fill></rt:loop>]</rt:unit>');
		$result = '[123][400][000]';
		$t = new Template();
		$t->vars("abc",array(1,2,3,4));
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:unit param="abc" var="abc_var" offset="3" cols="3" rows="3">[<rt:loop param="abc_var" var="a" limit="3"><rt:fill>0<rt:else />{$a}</rt:fill></rt:loop>]</rt:unit>');
		$result = '[340][000][000]';
		$t = new Template();
		$t->vars("abc",array(1,2,3,4));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoop(){
		 $src = $this->pre('
			 		<rt:loop param="abc" loop_counter="loop_counter" key="loop_key" var="loop_var">
			 		{$loop_counter}: {$loop_key} => {$loop_var}
			 		</rt:loop>
			 		hoge
		 		');
		$result = $this->pre('
					1: A => 456
					2: B => 789
					3: C => 010
					hoge
				');
		$t = new Template();
		$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010"));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFor(){
		$t = new Template();
		$src = $this->pre('
					<rt:loop param="abc" offset="2" limit="2" loop_counter="loop_counter" key="loop_key" var="loop_var">
					{$loop_counter}: {$loop_key} => {$loop_var}
					</rt:loop>
					hoge
				');
		$result = $this->pre('
						2: B => 789
						3: C => 010
						hoge
					');
		$t = new Template();
		$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999"));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopLimit(){
		$t = new Template();
		$src = $this->pre('
					<rt:loop param="abc" offset="{$offset}" limit="{$limit}" loop_counter="loop_counter" key="loop_key" var="loop_var">
					{$loop_counter}: {$loop_key} => {$loop_var}
					</rt:loop>
					hoge
				');
		$result = $this->pre('
					2: B => 789
					3: C => 010
					4: D => 999
					hoge
				');
		$t = new Template();
		$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999","E"=>"111"));
		$t->vars("offset",2);
		$t->vars("limit",3);
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopRange(){
		$t = new Template();
		$src = $this->pre('<rt:loop range="0,5" var="var">{$var}</rt:loop>');
		$result = $this->pre('012345');
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:loop range="0,6" range_step="2" var="var">{$var}</rt:loop>');
		$result = $this->pre('0246');
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:loop range="A,F" var="var">{$var}</rt:loop>');
		$result = $this->pre('ABCDEF');
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopMulti(){
		$t = new Template();
		$src = $this->pre('<rt:loop range="1,2" var="a"><rt:loop range="1,2" var="b">{$a}{$b}</rt:loop>-</rt:loop>');
		$result = $this->pre('1112-2122-');
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopVarEmpty(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc">aaa</rt:loop>');
		$result = $this->pre('');
		$t->vars("abc",array());
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopTotal(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" total="total">{$total}</rt:loop>');
		$result = $this->pre('4444');
		$t->vars("abc",array(1,2,3,4));
		$this->assertEquals($result,$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" total="total" offset="2" limit="2">{$total}</rt:loop>');
		$result = $this->pre('44');
		$t->vars("abc",array(1,2,3,4));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopEvenodd(){
		$t = new Template();
		$src = $this->pre('<rt:loop range="0,5" evenodd="evenodd" counter="counter">{$counter}[{$evenodd}]</rt:loop>');
		$result = $this->pre('1[odd]2[even]3[odd]4[even]5[odd]6[even]');
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFirst_last(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var" first="first" last="last">{$first}{$var}{$last}</rt:loop>');
		$result = $this->pre('first12345last');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var" first="first" last="last" offset="2" limit="2">{$first}{$var}{$last}</rt:loop>');
		$result = $this->pre('first23last');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var" offset="2" limit="3"><rt:first>F</rt:first>[<rt:middle>{$var}</rt:middle>]<rt:last>L</rt:last></rt:loop>');
		$result = $this->pre('F[][3][]L');
		$t->vars("abc",array(1,2,3,4,5,6));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFirst_last_block(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var" offset="2" limit="3"><rt:first>F<rt:if param="var" value="1">I<rt:else />E</rt:if><rt:else />nf</rt:first>[<rt:middle>{$var}<rt:else />nm</rt:middle>]<rt:last>L<rt:else />nl</rt:last></rt:loop>');

		$result = $this->pre('FE[nm]nlnf[3]nlnf[nm]L');
		$t->vars("abc",array(1,2,3,4,5,6));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFirst_in_last(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var"><rt:last>L</rt:last></rt:loop>');
		$t->vars("abc",array(1));
		$this->assertEquals("L",$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var"><rt:last first="false">L</rt:last></rt:loop>');
		$t->vars("abc",array(1));
		$this->assertEquals("",$t->get($src));
	}
	public function testLoopLast_in_first(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var"><rt:first>F</rt:first></rt:loop>');
		$t->vars("abc",array(1));
		$this->assertEquals("F",$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="var"><rt:first last="false">F</rt:first></rt:loop>');
		$t->vars("abc",array(1));
		$this->assertEquals("",$t->get($src));
	}
	public function testLoopDifi(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" limit="10" shortfall="difi" var="var">{$var}{$difi}</rt:loop>');
		$result = $this->pre('102030405064');
		$t->vars("abc",array(1,2,3,4,5,6));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopEmpty(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc">aaaaaa<rt:else />EMPTY</rt:loop>');
		$result = $this->pre('EMPTY');
		$t->vars("abc",array());
		$this->assertEquals($result,$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc">aaaaaa<rt:else>EMPTY</rt:loop>');
		$result = $this->pre('EMPTY');
		$t->vars("abc",array());
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFill(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a" offset="4" limit="4"><rt:fill>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill></rt:loop>');
		$result = $this->pre('F45hogehogeL');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a" offset="4" limit="4"><rt:fill><rt:first>f</rt:first>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill><rt:else />empty</rt:loop>');
		$result = $this->pre('fhogehogehogehogeL');
		$t->vars("abc",array());
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFill_no_limit(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a"><rt:fill>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill></rt:loop>');
		$result = $this->pre('F12345');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFill_last(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a" limit="3" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
		$result = $this->pre('45hogeLast');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a" limit="3"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
		$result = $this->pre('123Last');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));

		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a" offset="6" limit="3"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
		$result = $this->pre('hogehogehogeLast');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFill_first(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a" limit="3" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:first>First</rt:first></rt:loop>');
		$result = $this->pre('4First5hoge');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopFill_middle(){
		$t = new Template();
		$src = $this->pre('<rt:loop param="abc" var="a" limit="4" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:middle>M</rt:middle></rt:loop>');
		$result = $this->pre('45MhogeMhoge');
		$t->vars("abc",array(1,2,3,4,5));
		$this->assertEquals($result,$t->get($src));
	}
	public function testIf(){
		 $src = $this->pre('<rt:if param="abc">hoge</rt:if>');
		$result = $this->pre('hoge');
		$t = new Template();
		$t->vars("abc",true);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc" value="xyz">hoge</rt:if>');
		$result = $this->pre('hoge');
		$t = new Template();
		$t->vars("abc","xyz");
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc" value="1">hoge</rt:if>');
		$result = $this->pre('hoge');
		$t = new Template();
		$t->vars("abc",1);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc" value="1">bb<rt:else />aa</rt:if>');
		$result = $this->pre('bb');
		$t = new Template();
		$t->vars("abc",1);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc" value="1">bb<rt:else />aa</rt:if>');
		$result = $this->pre('aa');
		$t = new Template();
		$t->vars("abc",2);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc" value="{$a}">bb<rt:else />aa</rt:if>');
		$result = $this->pre('bb');
		$t = new Template();
		$t->vars("abc",2);
		$t->vars("a",2);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:loop range="1,5" var="c"><rt:if param="{$c}" value="{$a}">A<rt:else />{$c}</rt:if></rt:loop>');
		$result = $this->pre('1A345');
		$t = new Template();
		$t->vars("abc",2);
		$t->vars("a",2);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc">aa<rt:else />bb</rt:if>');
		$result = $this->pre('aa');
		$t = new Template();
		$t->vars("abc",array(1));
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc">aa<rt:else />bb</rt:if>');
		$result = $this->pre('bb');
		$t = new Template();
		$t->vars("abc",array());
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc">aa<rt:else />bb</rt:if>');
		$result = $this->pre('aa');
		$t = new Template();
		$t->vars("abc",true);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc">aa<rt:else />bb</rt:if>');
		$result = $this->pre('bb');
		$t = new Template();
		$t->vars("abc",false);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc">aa<rt:else />bb</rt:if>');
		$result = $this->pre('aa');
		$t = new Template();
		$t->vars("abc","a");
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('<rt:if param="abc">aa<rt:else />bb</rt:if>');
		$result = $this->pre('bb');
		$t = new Template();
		$t->vars("abc","");
		$this->assertEquals($result,$t->get($src));
	}

	public function testHtmlInputInput(){
		$src = $this->pre('
				<form rt:ref="true">
				<input type="text" name="aaa" />
				<input type="checkbox" name="bbb" value="hoge" />hoge
				<input type="checkbox" name="bbb" value="fuga" checked="checked" />fuga
				<input type="checkbox" name="eee" value="true" checked />foo
				<input type="checkbox" name="fff" value="false" />foo
				<input type="submit" />
				<textarea name="aaa"></textarea>

				<select name="ddd" size="5" multiple>
				<option value="123" selected="selected">123</option>
				<option value="456">456</option>
				<option value="789" selected>789</option>
				</select>
				<select name="XYZ" rt:param="xyz"></select>
				</form>
				');
		$result = $this->pre('
				<form>
				<input type="text" name="aaa" value="hogehoge" />
				<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge
				<input type="checkbox" name="bbb[]" value="fuga" />fuga
				<input type="checkbox" name="eee[]" value="true" checked="checked" />foo
				<input type="checkbox" name="fff[]" value="false" checked="checked" />foo
				<input type="submit" />
				<textarea name="aaa">hogehoge</textarea>

				<select name="ddd[]" size="5" multiple="multiple">
				<option value="123">123</option>
				<option value="456" selected="selected">456</option>
				<option value="789" selected="selected">789</option>
				</select>
				<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>
				</form>
				');
		$t = new Template();
		$t->vars("aaa","hogehoge");
		$t->vars("bbb","hoge");
		$t->vars("XYZ","B");
		$t->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
		$t->vars("ddd",array("456","789"));
		$t->vars("eee",true);
		$t->vars("fff",false);
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('
				<form rt:ref="true">
				<select name="ddd" rt:param="abc">
				</select>
				</form>
				');
		$result = $this->pre('
				<form>
				<select name="ddd"><option value="123">123</option><option value="456" selected="selected">456</option><option value="789">789</option></select>
				</form>
				');
		$t = new Template();
		$t->vars("abc",array(123=>123,456=>456,789=>789));
		$t->vars("ddd","456");
		$this->assertEquals($result,$t->get($src));

		$src = $this->pre('
				<form rt:ref="true">
				<rt:loop param="abc" var="v">
				<input type="checkbox" name="ddd" value="{$v}" />
				</rt:loop>
				</form>
				');
		$result = $this->pre('
				<form>
				<input type="checkbox" name="ddd[]" value="123" />
				<input type="checkbox" name="ddd[]" value="456" checked="checked" />
				<input type="checkbox" name="ddd[]" value="789" />
				</form>
				');
		$t = new Template();
		$t->vars("abc",array(123=>123,456=>456,789=>789));
		$t->vars("ddd","456");
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlInputReform(){
		$src = $this->pre('
				<form rt:aref="true">
				<input type="text" name="{$aaa_name}" />
				<input type="checkbox" name="{$bbb_name}" value="hoge" />hoge
				<input type="checkbox" name="{$bbb_name}" value="fuga" checked="checked" />fuga
				<input type="checkbox" name="{$eee_name}" value="true" checked />foo
				<input type="checkbox" name="{$fff_name}" value="false" />foo
				<input type="submit" />
				<textarea name="{$aaa_name}"></textarea>

				<select name="{$ddd_name}" size="5" multiple>
				<option value="123" selected="selected">123</option>
				<option value="456">456</option>
				<option value="789" selected>789</option>
				</select>
				<select name="{$XYZ_name}" rt:param="xyz"></select>
				</form>
				');
		$result = $this->pre('
				<form>
				<input type="text" name="aaa" value="hogehoge" />
				<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge
				<input type="checkbox" name="bbb[]" value="fuga" />fuga
				<input type="checkbox" name="eee[]" value="true" checked="checked" />foo
				<input type="checkbox" name="fff[]" value="false" checked="checked" />foo
				<input type="submit" />
				<textarea name="aaa">hogehoge</textarea>

				<select name="ddd[]" size="5" multiple="multiple">
				<option value="123">123</option>
				<option value="456" selected="selected">456</option>
				<option value="789" selected="selected">789</option>
				</select>
				<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>
				</form>
				');
		$t = new Template();
		$t->vars("aaa_name","aaa");
		$t->vars("bbb_name","bbb");
		$t->vars("XYZ_name","XYZ");
		$t->vars("xyz_name","xyz");
		$t->vars("ddd_name","ddd");
		$t->vars("eee_name","eee");
		$t->vars("fff_name","fff");

		$t->vars("aaa","hogehoge");
		$t->vars("bbb","hoge");
		$t->vars("XYZ","B");
		$t->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
		$t->vars("ddd",array("456","789"));
		$t->vars("eee",true);
		$t->vars("fff",false);
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlInputTextarea(){
		$src = $this->pre('
				<form>
				<textarea name="hoge"></textarea>
				</form>
				');
		$t = new Template();
		$this->assertEquals($src,$t->get($src));
	}
	public function testHtmlInputSelect(){
		$src = '<form><select name="abc" rt:param="abc"></select></form>';
		$t = new Template();
		$t->vars("abc",array(123=>123,456=>456));
		$this->assertEquals('<form><select name="abc"><option value="123">123</option><option value="456">456</option></select></form>',$t->get($src));
	}
	public function testHtmlInputMultiple(){
		$src = '<form><input name="abc" type="checkbox" /></form>';
		$t = new Template();
		$this->assertEquals('<form><input name="abc[]" type="checkbox" /></form>',$t->get($src));

		$src = '<form><input name="abc" type="checkbox" rt:multiple="false" /></form>';
		$t = new Template();
		$this->assertEquals('<form><input name="abc" type="checkbox" /></form>',$t->get($src));
	}
	public function testHtmlInputInput_exception(){
		$src = $this->pre('<form rt:ref="true"><input type="text" name="hoge" /></form>');
		$t = new Template();
		$this->assertEquals('<form><input type="text" name="hoge" value="" /></form>',$t->get($src));

		$src = $this->pre('<form rt:ref="true"><input type="password" name="hoge" /></form>');
		$t = new Template();
		$this->assertEquals('<form><input type="password" name="hoge" value="" /></form>',$t->get($src));

		$src = $this->pre('<form rt:ref="true"><input type="hidden" name="hoge" /></form>');
		$t = new Template();
		$this->assertEquals('<form><input type="hidden" name="hoge" value="" /></form>',$t->get($src));

		$src = $this->pre('<form rt:ref="true"><input type="checkbox" name="hoge" /></form>');
		$t = new Template();
		$this->assertEquals('<form><input type="checkbox" name="hoge[]" /></form>',$t->get($src));

		$src = $this->pre('<form rt:ref="true"><input type="radio" name="hoge" /></form>');
		$t = new Template();
		$this->assertEquals('<form><input type="radio" name="hoge" /></form>',$t->get($src));

		$src = $this->pre('<form rt:ref="true"><textarea name="hoge"></textarea></form>');
		$t = new Template();
		$this->assertEquals('<form><textarea name="hoge"></textarea></form>',$t->get($src));

		$src = $this->pre('<form rt:ref="true"><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>');
		$t = new Template();
		$this->assertEquals('<form><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>',$t->get($src));
	}
	public function testHtmlInputHtml5(){
		$src = $this->pre('
				<form rt:ref="true">
				<input type="search" name="search" />
				<input type="tel" name="tel" />
				<input type="url" name="url" />
				<input type="email" name="email" />
				<input type="datetime" name="datetime" />
				<input type="datetime-local" name="datetime_local" />
				<input type="date" name="date" />
				<input type="month" name="month" />
				<input type="week" name="week" />
				<input type="time" name="time" />
				<input type="number" name="number" />
				<input type="range" name="range" />
				<input type="color" name="color" />
				</form>
				');
		$rslt = $this->pre('
				<form>
				<input type="search" name="search" value="hoge" />
				<input type="tel" name="tel" value="000-000-0000" />
				<input type="url" name="url" value="http://rhaco.org" />
				<input type="email" name="email" value="hoge@hoge.hoge" />
				<input type="datetime" name="datetime" value="1970-01-01T00:00:00.0Z" />
				<input type="datetime-local" name="datetime_local" value="1970-01-01T00:00:00.0Z" />
				<input type="date" name="date" value="1970-01-01" />
				<input type="month" name="month" value="1970-01" />
				<input type="week" name="week" value="1970-W15" />
				<input type="time" name="time" value="12:30" />
				<input type="number" name="number" value="1234" />
				<input type="range" name="range" value="7" />
				<input type="color" name="color" value="#ff0000" />
				</form>
				');
		$t = new Template();
		$t->vars("search","hoge");
		$t->vars("tel","000-000-0000");
		$t->vars("url","http://rhaco.org");
		$t->vars("email","hoge@hoge.hoge");
		$t->vars("datetime","1970-01-01T00:00:00.0Z");
		$t->vars("datetime_local","1970-01-01T00:00:00.0Z");
		$t->vars("date","1970-01-01");
		$t->vars("month","1970-01");
		$t->vars("week","1970-W15");
		$t->vars("time","12:30");
		$t->vars("number","1234");
		$t->vars("range","7");
		$t->vars("color","#ff0000");

		$this->assertEquals($rslt,$t->get($src));
	}
	public function testHtmlListTable(){
		 $src = $this->pre('
		 		<table><tr><td><table rt:param="xyz" rt:var="o">
		 		<tr class="odd"><td>{$o["B"]}</td></tr>
		 		</table></td></tr></table>
		 		');
		$result = $this->pre('
				<table><tr><td><table><tr class="odd"><td>222</td></tr>
				<tr class="even"><td>444</td></tr>
				<tr class="odd"><td>666</td></tr>
				</table></td></tr></table>
				');
		$t = new Template();
		$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListTableLine(){
		 $src = $this->pre('
		 		<table rt:param="abc" rt:var="a"><tr><td><table rt:param="a" rt:var="x"><tr><td>{$x}</td></tr></table></td></td></table>
		 		');
		$result = $this->pre('
				<table><tr><td><table><tr><td>A</td></tr><tr><td>B</td></tr></table></td></td><tr><td><table><tr><td>C</td></tr><tr><td>D</td></tr></table></td></td></table>
				');
		$t = new Template();
		$t->vars("abc",array(array("A","B"),array("C","D")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListUl(){
		 $src = $this->pre('
		 		<ul rt:param="abc" rt:var="a"><li><ul rt:param="a" rt:var="x"><li>{$x}</li></ul></li></ul>
		 		');
		$result = $this->pre('
				<ul><li><ul><li>A</li><li>B</li></ul></li><li><ul><li>C</li><li>D</li></ul></li></ul>
				');
		$t = new Template();
		$t->vars("abc",array(array("A","B"),array("C","D")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListTableOdd(){
		 $src = $this->pre('
		 		<table rt:param="xyz" rt:var="o">
		 		<tr class="odd"><td>{$o["B"]}</td></tr>
		 		</table>
		 		');
		$result = $this->pre('
				<table><tr class="odd"><td>222</td></tr>
				<tr class="even"><td>444</td></tr>
				<tr class="odd"><td>666</td></tr>
				</table>
				');
		$t = new Template();
		$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListTableHash(){
		 $src = $this->pre('
		 		<table rt:param="xyz" rt:var="o">
		 		<tr><td>{$o["B"]}</td></tr>
		 		</table>
		 		');
		$result = $this->pre('
				<table><tr><td>222</td></tr>
				<tr><td>444</td></tr>
				<tr><td>666</td></tr>
				</table>
				');
		$t = new Template();
		$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListTableHashLimit(){
		 $src = $this->pre('
		 		<table rt:param="xyz" rt:var="o" rt:offset="1" rt:limit="1">
		 		<tr><td>{$o["B"]}</td></tr>
		 		</table>
		 		');
		$result = $this->pre('
				<table><tr><td>222</td></tr>
				</table>
				');
		$t = new Template();
		$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListTableHashLimitTbody(){
		 $src = $this->pre('
			 		<table rt:param="xyz" rt:var="o" rt:offset="1" rt:limit="1">
			 		<thead>
			 		<tr><th>hoge</th></tr>
			 		</thead>
			 		<tbody>
			 		<tr><td>{$o["B"]}</td></tr>
			 		</tbody>
			 		</table>
		 		');
		$result = $this->pre('
						<table>
						<thead>
						<tr><th>hoge</th></tr>
						</thead>
						<tbody><tr><td>222</td></tr>
						</tbody>
						</table>
					');
		$t = new Template();
		$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListTableHashNull(){
		 $src = $this->pre('
		 		<table rt:param="xyz" rt:null="true">
		 		<tr><td>{$o["B"]}</td></tr>
		 		</table>
		 		');
		$t = new Template();
		$t->vars("xyz",array());
		$this->assertEquals("",$t->get($src));
	}
	public function testHtmlListUlHashOdd(){
		 $src = $this->pre('
			 		<ul rt:param="xyz" rt:var="o">
			 		<li class="odd">{$o["B"]}</li>
			 		</ul>
		 		');
		$result = $this->pre('
						<ul><li class="odd">222</li>
						<li class="even">444</li>
						<li class="odd">666</li>
						</ul>
					');
		$t = new Template();
		$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
		$this->assertEquals($result,$t->get($src));
	}
	public function testLoopInLoop(){
		$src = $this->pre('
				<rt:loop param="abc" var="a">
				<ul rt:param="{$a}" rt:var="b">
				<li>
				<ul rt:param="{$b}" rt:var="c">
				<li>{$c}<rt:loop param="xyz" var="z">{$z}</rt:loop></li>
				</ul>
				</li>
				</ul>
				</rt:loop>
				');
		$result = $this->pre('
				<ul><li>
				<ul><li>A12</li>
				<li>B12</li>
				</ul>
				</li>
				</ul>
				<ul><li>
				<ul><li>C12</li>
				<li>D12</li>
				</ul>
				</li>
				</ul>

				');
		$t = new Template();
		$t->vars("abc",array(array(array("A","B")),array(array("C","D"))));
		$t->vars("xyz",array(1,2));
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListUlRange(){
		$src = $this->pre('<ul rt:range="1,3" rt:var="o"><li>{$o}</li></ul>');
		$result = $this->pre('<ul><li>1</li><li>2</li><li>3</li></ul>');
		$t = new Template();
		$this->assertEquals($result,$t->get($src));
	}
	public function testHtmlListTableNest(){
		$src = $this->pre('<table rt:param="object_list" rt:var="obj"><tr><td><table rt:param="obj" rt:var="o"><tr><td>{$o}</td></tr></table></td></tr></table>');
		$t = new Template();
		$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
		$this->assertEquals('<table><tr><td><table><tr><td>A1</td></tr><tr><td>A2</td></tr><tr><td>A3</td></tr></table></td></tr><tr><td><table><tr><td>B1</td></tr><tr><td>B2</td></tr><tr><td>B3</td></tr></table></td></tr></table>',$t->get($src));
	}
	public function testHtmlListUlNestUl(){
		$src = $this->pre('<ul rt:param="object_list" rt:var="obj"><li><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></li></ul>');
		$t = new Template();
		$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
		$this->assertEquals('<ul><li><ul><li>A1</li><li>A2</li><li>A3</li></ul></li><li><ul><li>B1</li><li>B2</li><li>B3</li></ul></li></ul>',$t->get($src));
	}
	public function testHtmlListUlNestOl(){
		$src = $this->pre('<ol rt:param="object_list" rt:var="obj"><li><ol rt:param="obj" rt:var="o"><li>{$o}</li></ol></li></ol>');
		$t = new Template();
		$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
		$this->assertEquals('<ol><li><ol><li>A1</li><li>A2</li><li>A3</li></ol></li><li><ol><li>B1</li><li>B2</li><li>B3</li></ol></li></ol>',$t->get($src));
	}
	public function testHtmlListNestOlUl(){
		$src = $this->pre('<ol rt:param="object_list" rt:var="obj"><li><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></li></ol>');
		$t = new Template();
		$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
		$this->assertEquals('<ol><li><ul><li>A1</li><li>A2</li><li>A3</li></ul></li><li><ul><li>B1</li><li>B2</li><li>B3</li></ul></li></ol>',$t->get($src));
	}
	public function testHtmlListNestTableUl(){
		$src = $this->pre('<table rt:param="object_list" rt:var="obj"><tr><td><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></td></tr></table>');
		$t = new Template();
		$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
		$this->assertEquals('<table><tr><td><ul><li>A1</li><li>A2</li><li>A3</li></ul></td></tr><tr><td><ul><li>B1</li><li>B2</li><li>B3</li></ul></td></tr></table>',$t->get($src));
	}
}
