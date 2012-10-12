<?php
use \otakusa\Paginator;

class PaginatorTest extends PHPUnit_Framework_TestCase{
	public function testIterator(){
		$p = new Paginator(10,3);
		$p->total(100);
		$re = array();
		foreach($p as $k => $v) $re[$k] = $v;
		$this->assertEquals(array('current'=>3,'limit'=>10,'offset'=>20,'total'=>100,'order'=>null),$re);
	}
	public function testDynamic(){
		$p = Paginator::dynamic_contents(2,'C');
		$p->add('A');
		$p->add('B');
		$p->add('C');
		$p->add('D');
		$p->add('E');
		$p->add('F');
		$p->add('G');
		$this->assertEquals('A',$p->prev());
		$this->assertEquals('E',$p->next());
		$this->assertEquals('page=A',$p->query_prev());
		$this->assertEquals(array('C','D'),$p->contents());
		$this->assertEquals(null,$p->first());
		$this->assertEquals(null,$p->last());
	}
	public function testConstruct(){
		$p = new Paginator(10);
		$this->assertEquals(10,$p->limit());
		$this->assertEquals(1,$p->first());
		$p->total(100);
		$this->assertEquals(100,$p->total());
		$this->assertEquals(10,$p->last());
		$this->assertEquals(1,$p->which_first(3));
		$this->assertEquals(3,$p->which_last(3));

		$p->current(3);
		$this->assertEquals(20,$p->offset());
		$this->assertEquals(true,$p->is_next());
		$this->assertEquals(true,$p->is_prev());
		$this->assertEquals(4,$p->next());
		$this->assertEquals(2,$p->prev());
		$this->assertEquals(1,$p->first());
		$this->assertEquals(10,$p->last());
		$this->assertEquals(2,$p->which_first(3));
		$this->assertEquals(4,$p->which_last(3));

		$p->current(1);
		$this->assertEquals(0,$p->offset());
		$this->assertEquals(true,$p->is_next());
		$this->assertEquals(false,$p->is_prev());

		$p->current(6);
		$this->assertEquals(5,$p->which_first(3));
		$this->assertEquals(7,$p->which_last(3));

		$p->current(10);
		$this->assertEquals(90,$p->offset());
		$this->assertEquals(false,$p->is_next());
		$this->assertEquals(true,$p->is_prev());
		$this->assertEquals(8,$p->which_first(3));
		$this->assertEquals(10,$p->which_last(3));
	}
	public function testAddContents(){
		$p = new Paginator(3,2);
		$list = array(1,2,3,4,5,6,7,8,9);
		foreach($list as $v){
			$p->add($v);
		}
		$this->assertEquals(array(4,5,6),$p->contents());
		$this->assertEquals(2,$p->current());
		$this->assertEquals(1,$p->first());
		$this->assertEquals(3,$p->last());
		$this->assertEquals(9,$p->total());


		$p = new Paginator(3,2);
		$list = array(1,2,3,4,5);
		foreach($list as $v){
			$p->add($v);
		}
		$this->assertEquals(array(4,5),$p->contents());
		$this->assertEquals(2,$p->current());
		$this->assertEquals(1,$p->first());
		$this->assertEquals(2,$p->last());
		$this->assertEquals(5,$p->total());


		$p = new Paginator(3);
		$list = array(1,2);
		foreach($list as $v){
			$p->add($v);
		}
		$this->assertEquals(array(1,2),$p->contents());
		$this->assertEquals(1,$p->current());
		$this->assertEquals(1,$p->first());
		$this->assertEquals(1,$p->last());
		$this->assertEquals(2,$p->total());
	}
	public function testNext(){
		$p = new Paginator(10,1,100);
		$this->assertEquals(2,$p->next());
	}
	public function testPrev(){
		$p = new Paginator(10,2,100);
		$this->assertEquals(1,$p->prev());
	}
	public function testIsNext(){
		$p = new Paginator(10,1,100);
		$this->assertEquals(true,$p->is_next());
		$p = new Paginator(10,9,100);
		$this->assertEquals(true,$p->is_next());
		$p = new Paginator(10,10,100);
		$this->assertEquals(false,$p->is_next());
	}
	public function testIsPrev(){
		$p = new Paginator(10,1,100);
		$this->assertEquals(false,$p->is_prev());
		$p = new Paginator(10,9,100);
		$this->assertEquals(true,$p->is_prev());
		$p = new Paginator(10,10,100);
		$this->assertEquals(true,$p->is_prev());
	}
	public function testQueryPrev(){
		$p = new Paginator(10,3,100);
		$p->query_name("page");
		$p->vars("abc","DEF");
		$this->assertEquals("abc=DEF&page=2",$p->query_prev());
	}
	public function testQueryNext(){
		$p = new Paginator(10,3,100);
		$p->query_name("page");
		$p->vars("abc","DEF");
		$this->assertEquals("abc=DEF&page=4",$p->query_next());
	}
	public function testQueryOrder(){
		$p = new Paginator(10,3,100);
		$p->query_name("page");
		$p->vars("abc","DEF");
		$p->order("bbb");
		$this->assertEquals("abc=DEF&order=aaa&porder=bbb",$p->query_order("aaa"));

		$p = new Paginator(10,3,100);
		$p->query_name("page");
		$p->vars("abc","DEF");
		$p->vars("order","bbb");
		$this->assertEquals("abc=DEF&order=aaa&porder=bbb",$p->query_order("aaa"));
	}
	public function testQuery(){
		$p = new Paginator(10,1,100);
		$this->assertEquals("page=3",$p->query(3));
	}
	public function testHAsRange(){
		$p = new Paginator(4,1,3);
		$this->assertEquals(1,$p->first());
		$this->assertEquals(1,$p->last());
		$this->assertEquals(false,$p->has_range());

		$p = new Paginator(4,2,3);
		$this->assertEquals(1,$p->first());
		$this->assertEquals(1,$p->last());
		$this->assertEquals(false,$p->has_range());

		$p = new Paginator(4,1,10);
		$this->assertEquals(1,$p->first());
		$this->assertEquals(3,$p->last());
		$this->assertEquals(true,$p->has_range());

		$p = new Paginator(4,2,10);
		$this->assertEquals(1,$p->first());
		$this->assertEquals(3,$p->last());
		$this->assertEquals(true,$p->has_range());
	}
}