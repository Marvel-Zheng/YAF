<?php
   /**
   * @name IndexController
   * @author marvel-zheng
   * @desc 默认控制器
   * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
   */
class IndexController extends BaseController {
/*	public function init(){
		parent::init();
		if ($this->is_unlogin) $this->redirect('/user/login/');
	}*/
      /** 
      * 默认动作
      * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
      * 对于如下的例子, 当访问http://yourhost/y/index/index/index/name/yantze 的时候, 你就会发现不同
      */
	public function indexAction(){
		test($this->getRequest()->getEnv());
		$this->redirect('/article/index');
	}
}
