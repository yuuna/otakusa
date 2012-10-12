<?php
use \otakusa\net\http\Query;

class NetHttpQueryTest extends PHPUnit_Framework_TestCase{
	public function testGet(){
		$this->assertEquals("req=123",Query::get("123","req"));
		$this->assertEquals("req[0]=123",Query::get(array(123),"req"));
		$this->assertEquals("req[0]=123&req[1]=456&req[2]=789",Query::get(array(123,456,789),"req"));
		$this->assertEquals("",Query::get(array(123,456,789)));
		$this->assertEquals("abc=123&def=456&ghi=789",Query::get(array("abc"=>123,"def"=>456,"ghi"=>789)));
		$this->assertEquals("req[0]=123&req[1]=&req[2]=789",Query::get(array(123,null,789),"req"));
		$this->assertEquals("req[0]=123&req[2]=789",Query::get(array(123,null,789),"req",false));

		$this->assertEquals("req=123&req=789",Query::get(array(123,null,789),"req",false,false));
		$this->assertEquals("label=123&label=&label=789",Query::get(array("label"=>array(123,null,789)),null,true,false));

		$obj = (object)array('id'=>0,'value'=>'','test'=>'TEST');
		$obj->id = 100;
		$obj->value = "hogehoge";
		$this->assertEquals("req[id]=100&req[value]=hogehoge&req[test]=TEST",Query::get($obj,"req"));
		$this->assertEquals("id=100&value=hogehoge&test=TEST",Query::get($obj));
	}
	public function testExpand(){
		$array = array();
		$this->assertEquals(array(array("abc",123),array("def",456)),Query::expand_vars($array,array("abc"=>"123","def"=>456)));
		$this->assertEquals(array(array("abc",123),array("def",456)),$array);

		$array = array();
		$this->assertEquals(array(array("hoge[abc]",123),array("hoge[def]",456)),Query::expand_vars($array,array("abc"=>"123","def"=>456),'hoge'));
		$this->assertEquals(array(array("hoge[abc]",123),array("hoge[def]",456)),$array);

		$array = array();
		$this->assertEquals(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),Query::expand_vars($array,array("abc"=>"123","def"=>array("ABC"=>123,"DEF"=>456)),'hoge'));
		$this->assertEquals(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),$array);

		$obj = (object)array('id'=>0,'value'=>'','test'=>'TEST');
		$obj->id = 100;
		$obj->value = "hogehoge";

		$array = array();
		$this->assertEquals(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),Query::expand_vars($array,$obj,"req"));
		$this->assertEquals(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),$array);
	}
}