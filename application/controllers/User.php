<?php
   //require "../yaf_classes.php";
class UserController extends BaseController{
	public function init(){
		$this->model = new UserModel();
		$this->utils = new utils();
	}

	public function indexAction(){
		$this->getView()->assign("name",'yantze');
		$this->getView()->assign("content",'game,');
		$userData = $this->model->selectAll();
		$this->getView()->assign("userData", $userData );
	}

	public function loginAction(){
		if($this->getRequest()->isPost()){
			$username = $this->getRequest()->getPost('username');
			$pwd      = $this->getRequest()->getPost('password');
			$user_id  = $this->model->loginUser($username, sha1(trim($pwd)));
			if($user_id){
				Yaf_Session::getInstance()->set("username",$username);
				Yaf_Session::getInstance()->set("user_role",$user_id['user_role']);
				Yaf_Session::getInstance()->set("user_id",$user_id['id']);
				exit($this->utils->return_json(0,"登陆成功"));
			}
			else{
				exit($this->utils->return_json(1,"登陆失败"));
			}
		}
		return true;
	}
	//注册用户
	public function addAction(){
		if($this->getRequest()->isPost()){
			$posts = $this->getRequest()->getPost();
			$posts['password'] = sha1($posts['password']);
			$posts['repassword'] = sha1($posts['repassword']);
			foreach($posts as $v){
				if(empty($v)){
					exit("不能为空");
				}
			}
			if($posts['password'] != $posts['repassword']){
				exit("两次密码不一致");
			}
			unset($posts['repassword']);
			$posts['status'] = 0;
			$posts['addtime'] = time();
			$posts['email'] = 0;
			$posts['phone'] = 0;
			$user_id = $this->model->insert($posts);
			if($user_id){
				$_SESSION['username'] = $posts['username'];
				$_SESSION['user_id'] = $user_id;
				$_SESSION['user_role'] = 0;
				exit($this->utils->return_json(0,"注册成功"));
			}else{
				exit($this->utils->return_json(1,"注册失败"));
			}
		}
		return true;
	}
	//编辑用户
	public function editAction(){
		if($this->getRequest()->isPost()){
			$posts = $this->getRequest()->getPost();
			$posts['password'] = sha1($posts['password']);
			$posts['repassword'] = sha1($posts['repassword']);
			foreach($posts as $v){
				if(empty($v)){
					exit("不能为空");
				}
			}
			if($posts['password'] != $posts['repassword']){
				exit("两次密码不一致");
			}
			$username = $posts['username'];
			unset($posts['repassword']);
			unset($posts['submit']);
			unset($posts['username']);
			$posts['is_del'] = 0;
			if($this->model->update($username, $posts)){
				exit("修改成功");
			}else{
				exit("修改失败");
			}
		}
	}

	//删除用户
	public function delAction(){
		if($this->getRequest()->isPost()){
			$username = $this->getRequest()->getPost('username');
			$password = $this->getRequest()->getPost('password');
			$password = sha1($password);
			if($this->model->loginUser($username,$password)){
				if($this->model->del($username)){
					exit("删除成功");
				}else{
					exit("删除失败");
				}
			}
			exit("删除失败");
		}
		return false;
	}
	//退出登录
	public function LogoutAction(){
		unset($_SESSION['username']);
		unset($_SESSION['user_id']);
		unset($_SESSION['user_role']);
		$this->redirect('login');
	}
	//验证用户名是否重复
	public function ValidationAction(){
		$username = $_GET['username'];
		if ($this->model->validate($username)) {
			exit(return_json(1,"用户名重复，不允许注册"));
		}else{
			exit(return_json(0,"允许注册"));
		}
		
	}
}
