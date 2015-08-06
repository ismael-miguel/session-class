<?
//removes the "Strict Standards" warning error_reporting(E_ALL^E_STRICT);
final class session implements ArrayAccess {
	//session data
	private static $s=array();
	
	//function (setters and getters)
	private static $f=array('s'=>array(),'g'=>array());//general setter/getter
	private $c=array('s'=>array(),'g'=>array());//private setter/getter
	
	//options
	private static $o=array(
		//'filename_type'=>'uuid5',//uuid3,uuid4,uuid5,md5,sha1|add _salt at the end for better safety
		'handler'=>'file',//file -> writes to file, cookie -> sends json code in the cookie
		'auto_save'=>true//when destroying, saves automatically
	);
	//initialised?
	private static $i=false;
	//names for ini_get('session.'.$n);
	private static $n='save_path,name,gc_probability,gc_divisor,gc_maxlifetime,cookie_lifetime,cookie_path,cookie_domain,cookie_secure,cookie_httponly,use_strict_mode,auto_start';
	//destroyed?
	private static $d=false;
	
	static function setter($k,$f=null)
	{
		if(($a=func_num_args())<=1)return!$a?false:((@$this)?(@$this->c['s'][$k]):(@self::$f['s'][$k]));
		else if($f==null){if(@$this)unset($this->c['s'][$k]);else unset(self::$f['s'][$k]);return true;}
		else return is_callable($f)&&(@$this?$this->c['s'][$k]=$f:self::$f['s'][$k]=$f);
	}
	
	static function getter($k,$f=null)
	{
		if(($a=func_num_args())<=1)return!$a?false:((@$this)?(@$this->c['g'][$k]):(@self::$f['g'][$k]));
		else if($f==null){if(@$this)unset($this->c['g'][$k]);else unset(self::$f['g'][$k]);return true;}
		else return is_callable($f)&&(@$this?$this->c['g'][$k]=$f:self::$f['g'][$k]=$f);
	}
	
	//==== arrayaccess ====
	//extra: setters and getters implemented when accessing $session['offset']
	//		 setter: gives the value, expects a value in return
	//		 getter: gives the value, returns the value returned by the getter
	function offsetSet($o,$v){isset($this)?$this->set($o,$v):self::set($o,$v);}
	function offsetExists($o){return isset($this)?$this->exist($o):self::exist($o);}
	function offsetUnset($o){isset($this)?$this->delete($o):self::delete($o);}
	function offsetGet($o){return isset($this)?$this->get($o):self::get($o);}
	//==== arrayaccess ====
	
	//==== magic method mapped to arrayaccess ====
	static function set($o,$v){!self::$i&&@self::$o['auto_start']&&self::start();self::$s[$o]=(isset($this)&&$this->c['s'][$o])?$this->c['s'][$o]($v,$o):(@self::$f['s'][$o]?call_user_func(self::$f['s'][$o],$v,$o):$v);}
	static function get($o){!self::$i&&@self::$o['auto_start']&&self::start();return(isset($this)&&$this->c['g'][$o])?$this->c['g'][$o](@self::$s[$o]):(@self::$f['g'][$o]?call_user_func(self::$f['g'][$o],@self::$s[$o]):@self::$s[$o]);}
	static function exist($o){return isset(self::$s[$o]);}
	static function delete($o){unset(self::$s[$o]);}
	function __set($o,$v){$this->set($o,$v);}
	function __get($o){return$this->get($o);}
	function __isset($o){return$this->exist($o);}
	function __unset($o){$this->delete($o);}
	//==== magic method mapped to arrayaccess ====
	
	//==== more magic methods ====
	function __toString(){return'object(session)';}
	//==== more magic methods ====
	
	//==== fun start here ====
	static function start(){
		if(self::$i)return false;
		$f=array(
			array('f'=>'ini_get','v'=>''),
			array('f'=>'fileatime','v'=>__FILE__),
			array('f'=>'filectime','v'=>__FILE__),
			array('f'=>'file_get_contents','v'=>''),
			array('f'=>'unlink','v'=>''),
			array('f'=>'dirname','v'=>__FILE__),
			array('f'=>'scandir','v'=>''),
			array('f'=>'json_encode','v'=>array(1)),
			array('f'=>'json_decode','v'=>'{"a":1}')
		);
		foreach($f as$v)
		{
			if(@$v['f']($v['v'])===null)
				die('<b>Fatal Error</b>: The function <font color="#0000FF"><b>'.$v['f'].'</b></font>() must be enabled in <b>'.__FILE__.'</b> on line <b>'.__LINE__.'</b>.');
		}
		unset($f);
		
		foreach(explode(',',self::$n)as$v)!isset(self::$o[$v])&&self::$o[$v]=ini_get('session.'.$v);
		
		if(self::$o['handler']=='file')
		{
			if(!@$_COOKIE[self::$o['name']])self::id(1);
			self::$s=json_decode(@file_get_contents(self::$o['save_path'].DIRECTORY_SEPARATOR.'session_'.$_COOKIE[self::$o['name']].'.json'),true);
			
			/*cleans files*/
			$v=mt_rand(self::$o['gc_probability'],self::$o['gc_probability']*self::$o['gc_divisor']);
			if($v&&$v==self::$o['gc_probability'])
			{
				$f=glob(self::$o['save_path'].DIRECTORY_SEPARATOR.'session_*.json');
				foreach($f as $v)
				{
					$a=@fileatime($x=self::$o['save_path'].DIRECTORY_SEPARATOR.$v);
					if(($a>self::$o['gc_maxlifetime'])||(!$a&&@filectime($x)>self::$o['gc_maxlifetime']))
						@unlink($x);
				}
			}
		}
		else
		{
			self::$s=json_decode(@$_COOKIE[self::$o['name'].'.json'],true);
		}
		self::$i=true;
		self::$o['auto_save']&&@register_shutdown_function(function(){session::save();});
	}
	
	static function save($d=false)
	{
		if(!self::$i)return false;
		if(self::$o['handler']=='file')
		{
			@file_put_contents(self::$o['save_path'].DIRECTORY_SEPARATOR.'session_'.$_COOKIE[self::$o['name']].'.json',json_encode(self::$s));
		}
		else
		{
			@setcookie(self::$o['name'].'.json',json_encode(self::$s,true),self::$o['cookie_lifetime'],self::$o['cookie_domain'],self::$o['cookie_secure'],self::$o['cookie_httponly']);
		}
		if($d)self::$s=array();
		return true;
	}
	
	static function id($v=null)
	{
		if(self::$o['handler']=='file')
		{
			if($v===null)return$_COOKIE[self::$o['name']];
			do{}while(is_file(self::$o['save_path'].DIRECTORY_SEPARATOR.'session_'.($_COOKIE[self::$o['name']]=md5(uniqid().str_shuffle(implode('',range('!','~'))))).'.json'));
			@setcookie(self::$o['name'],$_COOKIE[self::$o['name']],self::$o['cookie_lifetime'],self::$o['cookie_domain'],self::$o['cookie_secure'],self::$o['cookie_httponly']);
		}
	}
	
	static function settings($k,$v=null)
	{
		if($v===null)return@self::$o[$k];
		else self::$o[$k]=$v;
	}
	
	static function destroy()
	{
		self::$o['auto_save']&&self::save();
		self::$f=array('s'=>self::$s=array(),'g'=>array());
		if(isset($this))$this->c=array('s'=>array(),'g'=>array());
		return self::$d=true;
	}
	
	/*private static function uuid($v=4,$d=null,$s=false)//$v-> version|$data-> data for version 3 and 5|$s-> add salt and pepper
	{
		switch($v.($x=''))
		{
			case 3:
				$x=md5($d.($s?md5(microtime(true).uniqid($d,true)):''));break;
			case 4:default:
				$v=4;for($i=0;$i<=30;++$i)$x.=substr('1234567890abcdef',mt_rand(0,15),1);break;
			case 5:
				$x=sha1($d.($s?sha1(microtime(true).uniqid($d,true)):''));break;
		}
		return preg_replace('@^(.{8})(.{4})(.{3})(.{3})(.{12}).*@','$1-$2-'.$v.'$3-'.substr('89ab',rand(0,3),1).'$4-$5',$x);
	}*/
	//==== fun ends here ====
	
	//==== more magic methods ====
	function __constructor(){self::$o['auto_start']&&!self::$i&&self::start();}
	function __invoke(){!self::$i&&self::start();}
	
}
?>
