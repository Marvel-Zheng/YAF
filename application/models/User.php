<?php
   Class UserModel extends Model
   {
      public function loginUser($username, $password){
         $params = array("id","password","user_role");
         $result = $this->where(array('username'=>$username,'status'=>0))->find();
         if($result==null){
            return false;
         }
         else if ($result['password'] == ($password)){
            return $result;
         }
         else{
            return false;
         }
      }
      //删除用户
	public function del($username){
		$params = array( 'is_del'=>'1' );
		$whereis = array( $this->_index=>$username );
		$result = $this->_db->update($this->_table, $params, $whereis );
		return $result==null?false:true;
	}
	//用户名验证重复
	function validate($username){
		$result = $this->where(array('username'=>$username))->count();
		return $result;
	}
}
