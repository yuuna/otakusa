<?php
use \otakusa\File;

class FileTest extends PHPUnit_Framework_TestCase{
	public function testInstString(){
		$f = new File('/abc/dev/def','hogehoge');
		$this->assertEquals('/abc/dev/',$f->directory());
		$this->assertEquals('hogehoge',$f->value());
		$this->assertEquals('/abc/dev/def',$f->path());
		$this->assertEquals(false,$f->exist());
		$this->assertEquals('/abc/dev/def',(string)$f);
		$this->assertEquals(false,$f->is_ext('php'));
		$this->assertEquals(time(),$f->update());
	}
	public function testInstFile(){
		$f = new File(__FILE__);
		$this->assertEquals(__DIR__.'/',$f->directory());
		$this->assertEquals(file_get_contents(__FILE__),$f->value());
		$this->assertEquals(__FILE__,$f->path());
		$this->assertEquals(true,$f->exist());
		$this->assertEquals(__FILE__,(string)$f);
		$this->assertEquals(true,$f->is_ext('php'));
		$this->assertEquals(filemtime(__FILE__),$f->update());
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidArgumentException(){
		$f = new File('abc');
		$f->value();
	}
}