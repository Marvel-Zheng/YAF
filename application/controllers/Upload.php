<?php
/**
* @name IndexController
* @author marvel-zheng
* @desc 默认控制器
* @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
*/
class UploadController extends BaseController {
	public function init(){
		parent::init();
		if ($this->is_unlogin)$this->redirect('/user/login');
		$this->article = new ArticleModel();
		$this->util = new utils();
	}
	//文章列表
	public function uploadImgAction(){
		$file = $_FILES['file'];//得到传输的数据
		//得到文件名称
		$name = $file['name'];
		$type = strtolower(substr($name,strrpos($name,'.')+1)); //得到文件类型，并且都转化成小写
		$allow_type = array('jpg','jpeg','gif','png'); //定义允许上传的类型
		//判断文件类型是否被允许上传
		if(!in_array($type, $allow_type)){
		//如果不被允许，则直接停止程序运行
		return ;
		}
		//判断是否是通过HTTP POST上传的
		if(!is_uploaded_file($file['tmp_name'])){
		//如果不是通过HTTP POST上传的
		return ;
		}
		$upload_path = "./images/upload/"; //上传文件的存放路径
		//开始移动文件到相应的文件夹
		if(move_uploaded_file($file['tmp_name'],$upload_path.$file['name'])){
			$file_path = "/images/upload/".$file['name'];
			$data = array();
			$data['code'] = 0;
			$data['msg'] = '图片上传成功';
			$data['data'] = array('src'=>$file_path,'title'=>$file['name']);
			exit(json_encode($data));
		}else{
			exit($this->util->return_json(1,"图片上传失败"));
		}
	}

	//文章添加编辑
	public function uploadFileAction(){
		if ($this->getRequest()->isPost()) {
			// $content = $this->getRequest()->getPost('content');
			// test($content);
			upload_img();
		}
	}
}
