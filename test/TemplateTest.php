<?php
use \otakusa\Template;

class TemplateTest extends PHPUnit_Framework_TestCase{
	private function replace_crln($str){
		return str_replace("\r\n",PHP_EOL,$str);
	}

	public function testComment(){
		$src = '123<rt:comment>aaaaaaaa</rt:comment>456';
		$t = new Template();
		$this->assertEquals('123456',$t->get($src));
	}
	public function testRead(){
		$src = $this->replace_crln(trim('
abc {$abc}
def {$def}
ghi {$ghi}
'));
		$result = $this->replace_crln(trim('
abc 123
def 456
ghi 789
'));
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
}
