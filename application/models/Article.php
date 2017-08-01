<?php

class ArticleModel extends Model{

	public function articleList($page,$size){
		$count = $this->where(array('status'=>0))->count();
		//默认的起始页是第一页
		$page = intval($page);
		$size = intval($size);
		$limit_start = ($page-1)*$size;
		$list = $this->field('id,mid,title,update_time,addtime,content')->where(array('status'=>0))->limit($limit_start,$size)->select();
		$userModel = new UserModel();
		foreach ($list as $key => $value){
			if (($_SESSION['user_id'] == $value['mid']) || ($_SESSION['user_role'] == 2)) {
				$list[$key]['self'] = true;
			}else{
				$list[$key]['self'] = false;
			}
			$list[$key]['update_time'] = date("Y-m-d H:m:s",$value['update_time']);
			$list[$key]['author'] = $userModel->field('username')->where(array('id'=>$value['mid']))->find()['username'];
		}
		$result['data'] = $list;
		$result['sum'] = $count;
		return $result==null?false:$result;
	}

	public function add($info){
		$data = array();
		if (!empty($info['article_id'])) {
			$params = array( 'title'=>$info['title']);
			$params['content'] = $info['content'];
			$params['update_time'] = time();
			$result = $this->where(array('id'=>$info['article_id']))->update($params);
			return $result<1?false:true;
		}else{
			$data['title'] = $info['title'];
			$data['content'] = $info['content'];
			$data['addtime'] = time();
			$data['update_time'] = $data['addtime'];
			$data['status'] = 0;
			$data['mid'] = $_SESSION['user_id'];
			$result = $this->insert($data);
			return $result<1?false:true;
		}
	}
	//删除
	public function deleteById($id){
		$params = array( 'status'=>1024);
		$whereis = array('id'=>$id );
		$result = $this->where($whereis)->update($params);
		return $result==null?false:true;
	}
}
