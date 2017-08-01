<?php
class BaseController extends Yaf_Controller_Abstract{
	public function init(){
		if(empty(Yaf_Session::getInstance()->get('user_id'))){
			$this->redirect('/user/login/');
		}
	}
}