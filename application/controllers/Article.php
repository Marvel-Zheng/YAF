<?php
/**
* @name IndexController
* @author marvel-zheng
* @desc 默认控制器
* @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
*/
class ArticleController extends BaseController {
	public function init(){
		parent::init();
		$this->article = new ArticleModel();
	}
	//文章列表
	public function indexAction(){
		$page = $this->getRequest()->getQuery("page");
		$size = $this->getRequest()->getQuery("size");
		if(!($page&&$size)){
			$page=1;
			$size=10;
		}
		$result = $this->article->articleList($page,$size);
		// test($result);
		$this->getView()->assign("lists",$result['data']);
		$this->getView()->assign("maxNum",intval($result['sum']));
		$this->getView()->assign("curPage",intval($page));
		$this->getView()->assign("curSize",intval($size));
		return true;
	}

	//文章添加编辑
	public function addAction(){
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost();
			if ($this->article->add($data)) {
				exit(json_encode(array('code'=>0,'msg'=>'操作成功')));
			}else{
				exit(json_encode(array('code'=>1,'msg'=>'操作失败')));
			}
		}
	}

	//文章添加编辑
	public function deleteAction(){
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost();
			if ($this->article->deleteById($data['article_id'])) {
				exit(json_encode(array('code'=>0,'msg'=>'操作成功')));
			}else{
				exit(json_encode(array('code'=>1,'msg'=>'操作失败')));
			}
		}
	}
}
