<?php
   /**
   * @name Bootstrap
   * @author marvel-zheng
   * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
   * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
   * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
   * 调用的次序, 和申明的次序相同
   print_r(Yaf_Application::app());
   //require yaf_classes.php
   */
   class Bootstrap extends Yaf_Bootstrap_Abstract{

      private $_config;
	public function _initCommonFunctions(){
		//引入公共函数库
		Yaf_Loader::import(Yaf_Application::app()->getConfig()->application->directory . '/common/functions.php');  
	} 
      public function _initConfig() {
         //把配置保存起来
         $this->_config = Yaf_Application::app()->getConfig();
         Yaf_Registry::set('config', $this->_config);

      }

      // public function _initPlugin(Yaf_Dispatcher $dispatcher) {
      //    $userPlugin = new UserPlugin();
      //    $dispatcher->registerPlugin($userPlugin);

      // }

      // public function _initRoute(Yaf_Dispatcher $dispatcher) {
      //    Yaf_Dispatcher::getInstance()->getRouter()->addRoute(
      //       "supervar",new Yaf_Route_Supervar("r")
      //    );
      //    Yaf_Dispatcher::getInstance()->getRouter()->addRoute(
      //       "simple", new Yaf_Route_simple('m', 'c', 'a')
      //    );

      //    $route  = new Yaf_Route_Rewrite(
      //       "/index/get",
      //       array(
      //          "controller" => "item",
      //          "action"     => "get",
      //       )
      //    );
      //    Yaf_Dispatcher::getInstance()->getRouter()->addRoute(
      //       "product", $route
      //    );
      // }

      public function _initView(Yaf_Dispatcher $dispatcher){
         //在这里注册自己的view控制器，例如smarty,firekylin
         Yaf_Registry::set('dispatcher', $dispatcher);
         // $dispatcher->getInstance()->disableView();
      }

	// public function _initDb(Yaf_Dispatcher $dispatcher){
	// 	$this->_db = new Db($this->_config->mysql->read->toArray());
	// 	Yaf_Registry::set('_db', $this->_db);
	// }

      // public function _initMemcached(Yaf_Dispatcher $dispatcher){
      //    $mc_server = $this->_config->memcached;
      //    if ($mc_server['isopen']!=0) {
      //        $this->_mc = new memcached();
      //        $this->_mc->addServer($mc_server['host'], $mc_server['port']);
      //        Yaf_Registry::set('_mc', $this->_mc);
      //    }
      // }
      public function _initSession($dispatcher)
      {
         Yaf_Session::getInstance()->start();
      }
   }
