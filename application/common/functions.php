<?php  
	function test(){
		$arguments = func_get_args() ;
		$args_count = count($arguments) ;
		$method = 0 ; $i = 0 ;
		if($args_count > 1 && is_string($arguments[0]) && preg_match('/^;\d+$/', $arguments[0]))$method = substr(array_shift($arguments), 1) ;
		if(($method & 8) && !dreq('dtest', 1))return;

		foreach($arguments as $k=>$v){
			// 输出json数据
			if($method & 1){
				$v = json_encode($v) ;
			}elseif($method & 16){
				$v = var_export($v);
			}else{
				ob_start();
				var_dump($v) ;
				$v = ob_get_clean() ;
			}

			// 是否以源码形式展示
			if(!($method & 2))$v = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $v) ;
			$arguments[$k] = '[' .($i ++). ']=>    ' . $v ;
		}
		$output = implode($method & 1 ? "\n\n" : "\n", $arguments) ;
		echo $method & 2 ? $output : '<pre style="line-height:20px;word-wrap:break-word;word-break:normal;" contenteditable="true">' . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
		if(!($method & 4))die ;
	}

	
	function return_json($code=-1, $msg='未知错误'){
		$ret['code'] = $code;
		$ret['msg'] =  $msg;
		return json_encode($ret);
	}
?>