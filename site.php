<?php

defined('IN_IA') or exit('Access Denied');

class Weixinmao_zpModuleSite extends WeModuleSite {
  
  public function Sendsmsbao($tel="", $content="")
   {
      global $_GPC, $_W;
      		$statusStr = array(
		"0" => 'success',
		"-1" => "参数不全",
		"-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
		"30" => "密码错误",
		"40" => "账号不存在",
		"41" => "余额不足",
		"42" => "帐户已过期",
		"43" => "IP地址限制",
		"50" => "内容含有敏感词"
	);	
        $intro = pdo_get('weixinmao_zp_intro',array('uniacid'=>$_W['uniacid']));
     	$smsapi = "http://www.smsbao.com/"; //短信网关
		$user = $intro['smsaccount']; //短信平台帐号
		$pass = md5($intro['smspwd']); //短信平台密码

	
		
		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$tel."&c=".urlencode($content);
		$result =file_get_contents($sendurl) ;

       if( $statusStr[$result]=='success')
       {
       
       
       }
     
   
   
   }
  
  public function dealMsglist()
  {
    global $_GPC, $_W;

    
   
    	 $msglist =   pdo_fetchall("SELECT id,createtime  FROM " . tablename('weixinmao_zp_msgidlist') ." WHERE  uniacid=:weid ",array(":weid" => $_W['uniacid']));
    	$time7 = 60*60*24*7;
    
    	foreach($msglist as $k=>$v)
        {
        	$currenttime = time()-$v['createtime'];

          	if($currenttime >=$time7)
            {
            	pdo_delete('weixinmao_zp_msgidlist',array('id'=>$v['id']));
            }
        }
    
    return;
    
  
  }
  
        public function Senduser(){
        	   global $_GPC, $_W;
          	
              $params = array(':uniacid' => $_W['uniacid']);
			
$condition = ' WHERE m.uniacid = :uniacid group by m.uid order by m.createtime ASC  ';

//          $condition = ' WHERE m.uniacid = :uniacid   ';
$sql = " FROM " . tablename('weixinmao_zp_msgidlist') . " AS  m  ";

$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_userinfo') . " AS  u ON m.uid = u.uid ";


$sql = 'SELECT m.id AS id, m.form_id AS formid, u.openid AS openid ,m.uid AS uid  '  .$sql . $condition ;
         
//echo $sql;
          
$list = pdo_fetchall($sql, $params);
          
   
          
          foreach($list as $k=>$v)
          {
            if($v['openid']!='')
            {
                $data['touser'] = $v['openid'];
               $data['template_id'] = '2cFk2nLeeY9Lc4T1sR53wEWXqg2pHpBSXRrTbEhfcqw';
               //  $data['page'] =  ''; //该字段不填则模板无跳转
               $data['form_id'] = $v['formid'];
               $data['data'] = array('keyword1' => array('value' =>'2019新年快乐！' ),
                                         'keyword2' => array('value' => '测试某企业招聘')
                                        );
               $data['emphasis_keyword'] = 'keyword5.DATA';
              $openid = $v['openid'];
              $msgid = $v['openid'];
              
               pdo_delete('weixinmao_zp_msgidlist',array('uniacid'=>$_W['uniacid'],'id'=>$v['id']));

               $this->Sendmessage($openid ,$msgid,$data);
              
            }
            
          }

          /*
          
          
               $userinfo = pdo_get('weixinmao_zp_userinfo',array('uniacid'=>$_W['uniacid'],'uid'=>678));
      
     		   $openid =  $userinfo['openid'];
               
          	   $msglist =  pdo_get('weixinmao_zp_msgidlist',array('uniacid'=>$_W['uniacid'],'uid'=>$userinfo['uid']));
          		 pdo_delete('weixinmao_zp_msgidlist',array('uniacid'=>$_W['uniacid'],'id'=>$msglist['id']));
          	   $formid = $msglist['form_id'];
               $data['touser'] = $openid;
               $data['template_id'] = '2cFk2nLeeY9Lc4T1sR53wEWXqg2pHpBSXRrTbEhfcqw';
               //  $data['page'] =  ''; //该字段不填则模板无跳转
               $data['form_id'] = $formid;
               $data['data'] = array('keyword1' => array('value' =>'不错' ),
                                         'keyword2' => array('value' => '哈哈')
                                        );
               $data['emphasis_keyword'] = 'keyword5.DATA';
          


               $this->Sendmessage($openid ,$msgid,$data);
                                     
        		*/
        
        
        }
   
        public function Sendmessage($openid,$msgid,$data)
          {
              global $_GPC, $_W;
			
              $appid = $_W['uniaccount']['key'];
              $appsecret = $_W['uniaccount']['secret'];
              $access_token = ''; 
              $openid=$openid;
              if(empty($openid)){
                  $ret['errMsg'] = '却少参数openid';
                  exit(json_encode($ret));
              }
              //表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id

              if(empty( $data['form_id'])){
                  $ret['errMsg'] = '却少参数form_id';
                  exit(json_encode($ret));
              }

              //消息模板id
              $temp_id = $msgid;

              //获取access_token, 做缓存，expires_in：7200

             $this-> generate_token($access_token, $appid, $appsecret);

          

              $send_url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
              $str =$this->request($send_url, 'post', $data);
              $json = json_decode($this->request($send_url, 'post', $data));
         //    print_r($json);
        //  exit;
              if(!$json){
                  $ret['errMsg'] = $str;
                //  exit(json_encode($ret));
              }else if(isset($json->errcode) && $json->errcode){
                  $ret['errMsg'] = $json->errcode.', '.$json->errmsg;
                 // exit(json_encode($ret));
              }
              $ret['resultCode'] = 0;

              return true;
          }

      public function generate_token(&$access_token, $appid, $appsecret){
          $token_file = '/tmp/token';
          $general_token = true;
          if(file_exists($token_file) && ($info = json_decode(file_get_contents($token_file)))){
              if(time() < $info->create_time + $info->expires_in - 200){
                  $general_token = false;
                  $access_token = $info->access_token;
              }
          }
          if($general_token){
              $this->new_access_token($access_token, $token_file, $appid, $appsecret);
          }
      }

     public function new_access_token(&$access_token, $token_file, $appid, $appsecret){
          $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
          $str = file_get_contents($token_url);
          $json = json_decode($str);
          if(isset($json->access_token)){
              $access_token = $json->access_token;
              file_put_contents($token_file, json_encode(array('access_token' => $access_token, 'expires_in' => $json->expires_in, 'create_time' => time())));
          }else{
              file_put_contents('/tmp/error', date('Y-m-d H:i:s').'-Get Access Token Error: '.print_r($json, 1).PHP_EOL, FILE_APPEND);
          }
      }
  
  
    function request($url, $method, array $data, $timeout = 30) {
    try {
        $ch = curl_init();
        /*支持SSL 不验证CA根验证*/
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        /*重定向跟随*/
        if (ini_get('open_basedir') == '' && !ini_get('safe_mode')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        //设置 CURLINFO_HEADER_OUT 选项之后 curl_getinfo 函数返回的数组将包含 cURL
        //请求的 header 信息。而要看到回应的 header 信息可以在 curl_setopt 中设置
        //CURLOPT_HEADER 选项为 true
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);

        //fail the request if the HTTP code returned is equal to or larger than 400
        //curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $header = array("Content-Type:application/json;charset=utf-8;", "Connection: keep-alive;");
        switch (strtolower($method)) {
            case "post":
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_URL, $url);
                break;
            case "put":
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_URL, $url);
                break;
            case "delete":
                curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($data));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case "get":
                curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($data));
                break;
            case "new_get":
                curl_setopt($ch, CURLOPT_URL, $url."?para=".urlencode(json_encode($data)));
                break;
            default:
                throw new Exception('不支持的HTTP方式');
                break;
        }
        $result = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    } catch (Exception $e) {
        return "CURL EXCEPTION: ".$e->getMessage();
    }
}
   public function doWebGroupsending()
 {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');		
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
	

		if ($operation == 'post') {
			
            
            if (checksubmit('submit')) {
              
                 $jobid = intval($_GPC['companyid']);
                 $sendway = intval($_GPC['sendway']);
                 $jobinfo = pdo_get('weixinmao_zp_job',array('uniacid' => $_W['uniacid'],'id'=>$jobid));
                 $companyinfo =  pdo_get('weixinmao_zp_company',array('uniacid' => $_W['uniacid'],'id'=>$jobinfo['companyid']));
                 $jobcateid = $jobinfo['worktype'];
                  if($sendway == 0)
                  {
                            $params = array(':uniacid' => $_W['uniacid'],':jobcateid'=>$jobcateid);

                            $condition = ' WHERE n.uniacid = :uniacid AND n.jobcateid = :jobcateid GROUP BY m.uid  ORDER BY m.createtime ASC   ';

                            $sql = " FROM " . tablename('weixinmao_zp_jobnote') . " as  n  ";

                            $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_userinfo') . " as u ON u.uid = n.uid ";

                            $sql .= "  RIGHT JOIN  " . tablename('weixinmao_zp_msgidlist') . " as m ON m.uid = n.uid ";


                            $sql = 'SELECT n.id AS noteid, u.openid AS openid,m.id AS msgid , m.form_id AS formid,u.uid AS uid '  .$sql . $condition ;

                  }else{
                  
                  			
                      $params = array(':uniacid' => $_W['uniacid']);
			
                      $condition = ' WHERE m.uniacid = :uniacid group by m.uid order by m.createtime ASC  ';

                      $sql = " FROM " . tablename('weixinmao_zp_msgidlist') . " AS  m  ";

                      $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_userinfo') . " AS  u ON m.uid = u.uid ";


                      $sql = 'SELECT m.id AS id, m.form_id AS formid, u.openid AS openid ,m.uid AS uid  '  .$sql . $condition ;
                    

                  }
              
               $list = pdo_fetchall($sql, $params);
              
               	   $msgtpl = pdo_get('weixinmao_zp_msgtpl',array('enabled'=>1,'msgtype'=>3,'weid'=> $_W['uniacid']));

					  $msgid = $msgtpl['msgid'];
               
               if($list)
               {
                 	      foreach($list as $k=>$v)
          					{
                                  if($v['openid']!='')
                                  {
                                      $data['touser'] = $v['openid'];
                                     $data['template_id'] = $msgid;
                                       $data['page'] =  'weixinmao_zp/pages/index/index'; //该字段不填则模板无跳转
                                     $data['form_id'] = $v['formid'];
                                     $data['data'] = array('keyword1' => array('value' =>$jobinfo['jobtitle'] ),
                                                               'keyword2' => array('value' => $companyinfo['companyname'])
                                                              );
                                     $data['emphasis_keyword'] = 'keyword5.DATA';
                                    $openid = $v['openid'];
                                    $msgid = $v['openid'];

                                     pdo_delete('weixinmao_zp_msgidlist',array('uniacid'=>$_W['uniacid'],'id'=>$v['id']));

                                     $this->Sendmessage($openid ,$msgid,$data);

                                  }

                 		 }
                 
               }
              
              
              
                $sendnum = count($list);
                $data = array(
                    'uniacid' => $_W['uniacid'],
                  	'jobid'=>$jobinfo['id'],
                    'sendnum'=>$sendnum,
                    'sendway'=>$sendway,
                    'createtime' => TIMESTAMP,
                );
               
                pdo_insert('weixinmao_zp_sendrecord', $data);
                $id = pdo_insertid();
                message('发送成功！', $this->createWebUrl('groupsending', array('op' => 'display')), 'success');
            }
		
			
			
			
			
		}elseif($operation == 'getcompanyjob'){
          
              $condition = ' WHERE j.uniacid = :uniacid  ';
      		  $params = array(':uniacid' => $_W['uniacid']);
			
              if (!empty($_GPC['jobtitle'])) {
                  $condition .= ' AND j.jobtitle LIKE :jobtitle';
                  $params[':jobtitle'] = '%' . trim($_GPC['jobtitle']) . '%';
              }
		
              $sql = " FROM " . tablename('weixinmao_zp_job') . " as  j  ";

              $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";


          $sql = 'SELECT j.jobtitle AS jobtitle,j.id AS jobid,c.companyname AS companyname  '  .$sql . $condition ;

   		
        
          $list = pdo_fetchall($sql, $params);
          
          echo json_encode(array('data'=>$list));
          
          exit;
        
        
        } elseif ($operation == 'display') {
          	$this->dealMsglist();
			
	        $condition2 = ' WHERE `uniacid` = :uniacid  group by uid order by createtime ASC ';
			$params2 = array(':uniacid' => $_W['uniacid']);
          
            $sql = 'SELECT id FROM ' . tablename('weixinmao_zp_msgidlist') .$condition2 ;
	        
			$sendlist = pdo_fetchall($sql, $params2);
          
            $sendtotal = count($sendlist);
          	

          
          
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE s.uniacid = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			

          
          
             $sql = " FROM " . tablename('weixinmao_zp_sendrecord') . " as  s  ";


              $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = s.jobid ";
              $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";

          
			
			$sqlcount = 'SELECT COUNT(*) '  .$sql . $condition ;
		
			$total = pdo_fetchcolumn($sqlcount, $params);
			
			if (!empty($total)) {
				$sqllist = 'SELECT s.id AS id, s.sendway AS sendway, c.companyname AS companyname,j.jobtitle AS jobtitle,s.sendnum AS sendnum, s.createtime AS createtime   '. $sql .$condition.' ORDER BY  s.createtime DESC  LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sqllist, $params);

	
				
				$pager = pagination($total, $pindex, $psize);
			}

			
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_sendrecord') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_sendrecord', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}
	
		include $this->template('groupsending');
		
	}
  
  
	public function doWebIntro() {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_W,$_GPC;
		load()->func('tpl');
      //	$this->Senduser();
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

	   if ($operation == 'post') {
            $id = intval($_GPC['id']);
            if (checksubmit('submit')) {
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'name'=>$_GPC['name'],
					'address'=>$_GPC['address'],
					'tel'=>$_GPC['tel'],
					'qq'=>$_GPC['qq'],
					'email'=>$_GPC['email'],
					'logo'=>$_GPC['logo'],
					'name'=>$_GPC['name'],
					'opentime'=>$_GPC['opentime'],
					'city'=>$_GPC['city'],
					'lng'=>$_GPC['location']['lng'],
					'lat'=>$_GPC['location']['lat'],
                    'content' => htmlspecialchars_decode($_GPC['content']),
                    'ischeck'=>$_GPC['ischeck'],
                    'iscompany'=>$_GPC['iscompany'],
                    'isnote'=>$_GPC['isnote'],
                      'issms'=>$_GPC['issms'],
                     'smsaccount'=>$_GPC['smsaccount'],
                      'smspwd'=>$_GPC['smspwd'],
                        'notenum'=>$_GPC['notenum'],
                    'createtime' => TIMESTAMP,
                );
               
                if (!empty($id)) {
                    unset($data['createtime']);
                    pdo_update('weixinmao_zp_intro', $data, array('id' => $id));
                } else {
                    pdo_insert('weixinmao_zp_intro', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('intro', array('op' => 'display')), 'success');
            }
            if (empty($shop)) {
                $shop['displayorder'] = 0;
                $shop['enabled'] = 1;
            }
        }elseif($operation == 'display'){
         
   	
		$intro = pdo_fetch("select * from " . tablename('weixinmao_zp_intro') . " where uniacid=:uniacid limit 1", array(":uniacid" => $_W['uniacid']));
		include $this->template('intro');
		}
	}



		public function doWebUserinfo() 

		{
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'post') {
			
	

		
			
		} elseif ($operation == 'display') {
			
			//echo $_GPC['keyword'];
			$pindex = max(1, intval($_GPC['page'])); 
			$psize = 15;
			$condition = " WHERE `uniacid` = :uniacid AND uid>0 AND tel <> '' ";
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `name` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_userinfo') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_userinfo') .$condition.' ORDER BY  `createtime`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				$pager = pagination($total, $pindex, $psize);
			}
			foreach($list as $k=>$v)
			{
				$list[$k]['avatarUrl'] = tomedia($v['avatarUrl']);
			}
			
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_userinfo') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，商品不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_userinfo', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('userinfo');
		
	}



	public function doWebMsgtpl() {
		global $_W, $_GPC;
			load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_msgtpl') . " WHERE weid = '{$_W['uniacid']}' ");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'weid' => $_W['uniacid'],
					'msgid' => $_GPC['msgid'],
					'msgcontent' => $_GPC['msgcontent'],
					'msgtype' => $_GPC['msgtype'],
					'enabled' => intval($_GPC['enabled']),
				);
			
				if (!empty($id)) {
					pdo_update('weixinmao_zp_msgtpl', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_msgtpl', $data);
					$id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('msgtpl', array('op' => 'display')), 'success');
			}
			$adv = pdo_fetch("select * from " . tablename('weixinmao_zp_msgtpl') . " where id=:id and weid=:weid limit 1", array(":id" => $id, ":weid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$adv = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_msgtpl') . " WHERE id = '$id' AND weid=" . $_W['uniacid'] . "");
			if (empty($adv)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('msgtpl', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_msgtpl', array('id' => $id));
			message('删除成功！', $this->createWebUrl('msgtpl', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('msgtpl', TEMPLATE_INCLUDEPATH, true);
	}



public function doWebNav() {
		global $_W, $_GPC;
			load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_nav') . " WHERE weid = '{$_W['uniacid']}' ORDER BY displayorder DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'weid' => $_W['uniacid'],
					'advname' => $_GPC['advname'],
					'link' => $_GPC['link'],
					'enabled' => intval($_GPC['enabled']),
					'displayorder' => intval($_GPC['displayorder']),
                  'appid' => $_GPC['appid'],
                   'innerurl' => $_GPC['innerurl'],
					'thumb'=>$_GPC['thumb']
				);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_nav', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_nav', $data);
					$id = pdo_insertid();
				}
				message('更新导航成功！', $this->createWebUrl('nav', array('op' => 'display')), 'success');
			}
			$adv = pdo_fetch("select * from " . tablename('weixinmao_zp_nav') . " where id=:id and weid=:weid limit 1", array(":id" => $id, ":weid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$adv = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_nav') . " WHERE id = '$id' AND weid=" . $_W['uniacid'] . "");
			if (empty($adv)) {
				message('抱歉，导航不存在或是已经被删除！', $this->createWebUrl('nav', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_nav', array('id' => $id));
			message('导航删除成功！', $this->createWebUrl('nav', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('nav', TEMPLATE_INCLUDEPATH, true);
	}

	public function doWebAdv() {
		global $_W, $_GPC;
			load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_adv') . " WHERE weid = '{$_W['uniacid']}' ORDER BY displayorder DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'weid' => $_W['uniacid'],
					'advname' => $_GPC['advname'],
					'link' => $_GPC['link'],
					'enabled' => intval($_GPC['enabled']),
					'displayorder' => intval($_GPC['displayorder']),
					'thumb'=>$_GPC['thumb'],
					'toway'=>$_GPC['toway'],
					'appid'=>$_GPC['appid']
				);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_adv', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_adv', $data);
					$id = pdo_insertid();
				}
				message('更新幻灯片成功！', $this->createWebUrl('adv', array('op' => 'display')), 'success');
			}
			$adv = pdo_fetch("select * from " . tablename('weixinmao_zp_adv') . " where id=:id and weid=:weid limit 1", array(":id" => $id, ":weid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$adv = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_adv') . " WHERE id = '$id' AND weid=" . $_W['uniacid'] . "");
			if (empty($adv)) {
				message('抱歉，幻灯片不存在或是已经被删除！', $this->createWebUrl('adv', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_adv', array('id' => $id));
			message('幻灯片删除成功！', $this->createWebUrl('adv', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('adv', TEMPLATE_INCLUDEPATH, true);
	}




	
	public function doWebAgent() {
		global $_W, $_GPC;
			load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		//$sql = 'SELECT * FROM ' . tablename('weixinmao_house_city') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		//$citylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
		if ($operation == 'display') {
			
			
				$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `title` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
		
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_agent') .$condition ;
		
			
			$total = pdo_fetchcolumn($sql, $params);

			$today = strtotime(date('Y-m-d'));
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_agent') .$condition.' ORDER BY  `createtime`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
			
				$pager = pagination($total, $pindex, $psize);
			}
			
			
			
			
			
		} elseif ($operation == 'post') {
			$agent = pdo_fetch("select * from " . tablename('weixinmao_zp_agent_setting') . " where  uniacid=:uniacid limit 1", array(":uniacid" => $_W['uniacid']));

		
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'notemoney' => $_GPC['notemoney'],
					'companymoney'=>$_GPC['companymoney']
					
					);
				if ($agent) {
					pdo_update('weixinmao_zp_agent_setting', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_agent_setting', $data);
					$id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('agent', array('op' => 'post')), 'success');
			}

		} elseif ($operation == 'setting') {
			$agent = pdo_fetch("select * from " . tablename('weixinmao_zp_agent_setting') . " where  uniacid=:uniacid limit 1", array(":uniacid" => $_W['uniacid']));

		
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'notemoney' => $_GPC['notemoney'],
					'companymoney'=>$_GPC['companymoney']
					
					);
				if ($agent) {
                 
					pdo_update('weixinmao_zp_agent_setting', $data, array('id' => $agent['id']));
				} else {
					pdo_insert('weixinmao_zp_agent_setting', $data);
					$id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('agent', array('op' => 'setting')), 'success');
			}

		}elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_agent') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('agent', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_agent', array('id' => $id));
			message('删除成功！', $this->createWebUrl('agent', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('agent', TEMPLATE_INCLUDEPATH, true);
	}
	




public function doWebContent() {
		//这个操作被定义用来呈现 管理中心导航菜单
				global $_GPC, $_W;
		load()->func('tpl');

		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_category') . ' WHERE `weid` = :weid ORDER BY `parentid`, `displayorder` DESC';
		
		$category = pdo_fetchall($sql, array(':weid' => $_W['uniacid']), 'id');
		
	
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'post') {
			
			$id = $_GPC['id'];
	
			 if (!empty($id)) {
				 
				 		$item = pdo_fetch("SELECT *  FROM " . tablename('weixinmao_zp_content') . " WHERE id = :id", array(':id' => $id));
					
						
			}
			
			$pid = $_GPC['category']['parentid'];
			
			$sid = 0;

			
			if (checksubmit('submit')) {
				//print_r($_GPC);
				//exit;
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'title'=>$_GPC['title'],
					'pid'=>$pid,
					'sid'=>$sid,
                    'content' => ihtmlspecialchars($_GPC['content']),
					'sort'=>$_GPC['sort'],
					'thumb'=>$_GPC['thumb'],
                    'createtime' => TIMESTAMP,
                );
               
                if (!empty($id)) {
                    unset($data['createtime']);
                    pdo_update('weixinmao_zp_content', $data, array('id' => $id));
                } else {
                    pdo_insert('weixinmao_zp_content', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('content', array('op' => 'display')), 'success');
            }
			
			
			
			
		} elseif ($operation == 'display') {
			
			echo $_GPC['keyword'];
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `title` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_content') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_content') .$condition.' ORDER BY  `sort`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				$pager = pagination($total, $pindex, $psize);
			}
			if($list)
			{
					foreach($list as $k=>$v)
					{
						$parent_info = pdo_fetch("SELECT name  FROM " . tablename('weixinmao_zp_category') . " WHERE id = :id", array(':id' => $v['pid']));
						$children_info = pdo_fetch("SELECT name  FROM " . tablename('weixinmao_zp_category') . " WHERE id = :id", array(':id' => $v['sid']));

						$list[$k]['parent_catename'] = $parent_info['name'];
						$list[$k]['children_catename'] = $children_info['name'];
					}
			}
			
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_content') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，商品不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_content', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('goods');
		
	}
	
  
  	public function doWebMoneylist(){
    
        		global $_GPC, $_W;
		load()->func('tpl');
		
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

		
	     if ($operation == 'display') {
			
	
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;

			$params = array(':uniacid' => $_W['uniacid']);
			
			
			
           
           $condition = "  WHERE r.uniacid = :uniacid AND r.type='getmoney'  ORDER BY r.createtime DESC  ";
           
           if (!empty($_GPC['keyword'])) {
				$condition .= ' AND  n.name  LIKE :title  ';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
		
              $sql = " FROM " . tablename('weixinmao_zp_moneyrecord') . " as  r  ";

              $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_userinfo') . " as u ON u.uid = r.uid ";
              
              $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_bindcard') . " as b ON b.uid = u.uid ";
             
       
			$sqlcount = 'SELECT COUNT(*) '. $sql .$condition ;
         
		
			$total = pdo_fetchcolumn($sqlcount, $params);
			
			if (!empty($total)) {
$sql = 'SELECT r.id AS id, r.money AS money  ,r.createtime AS createtime,r.status AS status,b.name AS name,b.account AS account '  .$sql . $condition ;
				$list = pdo_fetchall($sql, $params);

			
            //   print_r($list);
				
				$pager = pagination($total, $pindex, $psize);
			}

			
			
		}  elseif ($operation == 'donepay') {
			$id = $_GPC['id'];
				pdo_update('weixinmao_zp_moneyrecord',array('status'=>1),array('id' => $id));

			


				message('操作完成', $this->createWebUrl('moneylist', array('op' => 'display')), 'success');



		}elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_moneyrecord') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_moneyrecord', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}
		include $this->template('moneyrecord');
    
    
    
    
    
    }
  
  
  
  
  
    	public function doWebJobrecord(){
    
        		global $_GPC, $_W;
		load()->func('tpl');
		
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

		
	     if ($operation == 'display') {
			
	
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;

			$params = array(':uniacid' => $_W['uniacid']);
			
			
			
           
           $condition = ' WHERE r.uniacid = :uniacid  AND c.id>0 ';
           
           if (!empty($_GPC['keyword'])) {
				$condition .= ' AND  n.name  LIKE :title ';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
		
              $sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";

              $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";

              $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";
              
               $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobnote') . " AS n ON n.uid = r.uid ";

         //     $sql = 'SELECT j.jobtitle as jobtitle,j.id AS jobid, r.id AS id ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;

   		
		//	print_r($params);
       
			$sqlcount = 'SELECT COUNT(*) '. $sql .$condition ;
         
		
			$total = pdo_fetchcolumn($sqlcount, $params);
			
			if (!empty($total)) {
$sql = 'SELECT j.jobtitle as jobtitle,j.id AS jobid, r.id AS id ,c.companyname AS companyname ,n.name AS name, n.tel AS tel ,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;
				$list = pdo_fetchall($sql, $params);

			
            //   print_r($list);
				
				$pager = pagination($total, $pindex, $psize);
			}

			
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_jobrecord') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_jobrecord', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}
		include $this->template('jobrecord');
    
    
    
    
    
    }
  
  
  
  

	public function doWebCompany() {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');
		
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
			$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_city') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		$citylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

	
       $nocheckcompany = pdo_getcolumn('weixinmao_zp_company', array('status' => 0, 'uniacid' => $_W['uniacid']), array('count(*)'));
      
     $endcompany = pdo_fetch("SELECT count(id) AS endcount FROM " . tablename('weixinmao_zp_company') . " WHERE uniacid = :uniacid AND  endtime < ".time(), array(':uniacid' => $_W['uniacid']));

     if($endcompany)
           $endcount = $endcompany['endcount'];
          else
            $endcount = 0;

		if ($operation == 'post') {
			
			$id = $_GPC['id'];


		
	
			 if (!empty($id)) {
				 		$item = pdo_fetch("SELECT *  FROM " . tablename('weixinmao_zp_company') . " WHERE id = :id", array(':id' => $id));

						 $sql = 'SELECT * FROM ' . tablename('weixinmao_zp_area') . ' WHERE `uniacid` = :uniacid AND `cityid`=:cityid ORDER BY `sort` DESC';
						$arealist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':cityid'=>$item['cityid']));
							
			}
			
		
			if (checksubmit('submit')) {
			
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'companyname'=>$_GPC['companyname'],
					'companycate'=>$_GPC['companycate'],
					'companytype'=>$_GPC['companytype'],
					'companyworker'=>$_GPC['companyworker'],
					'mastername'=>$_GPC['mastername'],
					'tel'=>$_GPC['tel'],
					'cityid'=>$_GPC['cityid'],
					'areaid'=>$_GPC['areaid'],
					'address'=>$_GPC['address'],
					'lng'=>$_GPC['location']['lng'],
					'lat'=>$_GPC['location']['lat'],
                    'content' => htmlspecialchars_decode($_GPC['content']),
					'sort'=>$_GPC['sort'],
					'thumb'=>$_GPC['thumb'],
					'status'=>$_GPC['status'],
					'isrecommand'=>$_GPC['isrecommand'],
					'notenum'=>$_GPC['notenum'],
					//'roleid'=>$_GPC['roleid'],
                   'createtime' => TIMESTAMP,
                );
         
                if (!empty($id)) {
                    unset($data['createtime']);
                    pdo_update('weixinmao_zp_company', $data, array('id' => $id));
                } else {
                //	$intro = pdo_get('weixinmao_zp_intro',array('uniacid'=>$_W['uniacid']));
                //	$data['notenum'] = $intro['notenum'];
                  $companyrole = pdo_get('weixinmao_zp_companyrole',array('uniacid'=>$_W['uniacid'],'isinit'=>1));
				 $endtime = time()+60*60*24*$companyrole['days'];
                  
                  $data['notenum'] = $companyrole['notenum'];
                  $data['jobnum'] = $companyrole['jobnum'];
                  $data['roleid'] = $companyrole['id'];
                  $data['endtime'] = $endtime;
             
                  pdo_insert('weixinmao_zp_company', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('company', array('op' => 'display')), 'success');
            }
			
	   	}elseif ($operation == 'noticecompany') {
			   
              $intro = pdo_get('weixinmao_zp_intro',array('uniacid'=>$_W['uniacid']));
          	  if($intro['issms'] == 1)
              {
                 $content = '您的招聘信息已过期，为了不影响使用请及时续费更新！';
                 $noticecompany = pdo_fetchall("SELECT  tel  FROM " . tablename('weixinmao_zp_company') . " WHERE uniacid = :uniacid AND  endtime < ".time(), array(':uniacid' => $_W['uniacid']));
                 if($noticecompany)
                 {
					foreach($noticecompany as $k =>$v)
                    {
                    		$this->sendmsgbao($v['tel'],$content);
                    }
                   
                     echo json_encode(array('error'=>0));
          
         			 exit;
                 }
              }else{
              
              echo json_encode(array('error'=>1));
          
          exit;
              }
			
		}elseif ($operation == 'getcity') {
			$cityid = $_GPC['cityid'];
          $condition = ' WHERE `uniacid` = :uniacid AND `cityid`=:cityid ';
      		  $params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);
			
			
			
			$sql = 'SELECT id,name FROM ' . tablename('weixinmao_zp_area') .$condition ;
        
          $list = pdo_fetchall($sql, $params);
          
          echo json_encode(array('data'=>$list));
          
          exit;
			
		}  elseif ($operation == 'display') {
			
			//echo $_GPC['keyword'];
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;


			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `companyname` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			
			
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_company') .$condition ;
		
			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_company') .$condition.' ORDER BY  `createtime`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);

				if($list)
				{
					foreach($list as $k=>$v)
						{
							$city_info = pdo_get('weixinmao_zp_city',array('id'=>$v['cityid']));
						$area_info = pdo_get('weixinmao_zp_area',array('id'=>$v['areaid']));
						$companyrole = pdo_get('weixinmao_zp_companyrole',array('id'=>$v['roleid']));
						$list[$k]['cityname'] =  $city_info['name'];
						$list[$k]['areaname'] =  $area_info['name'];
						$list[$k]['rolename'] = $companyrole['title'];
                        $list[$k]['cardimg'] = tomedia($v['cardimg']);
						}
				}
				
				$pager = pagination($total, $pindex, $psize);
			}

			
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_company') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，企业信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_company', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}elseif($operation == 'companyrole'){

			$companyid = $_GPC['id'];

		

			$companyinfo = pdo_get('weixinmao_zp_company',array('id'=>$companyid,'uniacid'=>$_W['uniacid']));

			$companyrole = pdo_get('weixinmao_zp_companyrole',array('id'=>$companyinfo['roleid'],'uniacid'=>$_W['uniacid']));

			if($companyinfo['endtime']>time())
					{

						$condition = ' AND sort > '.$companyrole['sort'];
					}else{

						$condition = ' ';
					}

			$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_companyrole') . ' WHERE `uniacid` = :uniacid AND isinit = 0 '.$condition.' ORDER BY `sort` ASC';

		    $moneylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));






		    if (checksubmit('submit')) {

		    			$companyid = $_GPC['id'];
		    			$roleid = $_GPC['roleid'];
                        $companyrole = pdo_get('weixinmao_zp_companyrole',array('uniacid'=>$_W['uniacid'],'id'=>$roleid));

                        $companyinfo = pdo_get('weixinmao_zp_company',array('uniacid'=>$_W['uniacid'],'id'=>$companyid));
                        $jobnum = $companyrole['jobnum'] + $companyinfo['jobnum'];
                        $notenum = $companyrole['notenum'] + $companyinfo['notenum'];

                        $time = 60*60*24*$companyrole['days'];

                       if($companyinfo['endtime']>time())
                       		{
                       				$endtime = $companyinfo['endtime'] + $time;
                        	}else{

                       				//$endtime = 60*60*24*365 + $time;
                       				$endtime = time() + $time ;

                        	}

                       

		                if (!empty($companyid)) {
		                    pdo_update('weixinmao_zp_company',array('jobnum'=>$jobnum,'notenum'=>$notenum,'endtime'=>$endtime,'roleid'=>$roleid), array('id'=>$companyid));
		                } 
                message('更新成功！', $this->createWebUrl('company', array('op' => 'display')), 'success');
            }
		   



		}
	
		include $this->template('company');
		
	}
	


   public function doWebJoblist() {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_area') . ' WHERE `uniacid` = :uniacid ORDER BY  `sort` DESC';
		
		$arealist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
		
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_company') . ' WHERE `uniacid` = :uniacid AND endtime > '.time().' ORDER BY `sort` DESC';
		
		$companylist  = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$worktypelist  = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));


		if ($operation == 'post') {
			
			$id = $_GPC['id'];
	
			 if (!empty($id)) {
				 
				 		$item = pdo_fetch("SELECT *  FROM " . tablename('weixinmao_zp_job') . " WHERE id = :id", array(':id' => $id));

				 	    $item['special'] = explode(',',$item['special']);
							
			}
			
		
			if (checksubmit('submit')) {
						
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'jobtitle'=>$_GPC['jobtitle'],
					'vprice'=>$_GPC['vprice'],
					'noteprice'=>$_GPC['noteprice'],
					'dmoney'=>$_GPC['dmoney'],
					'worktype'=>$_GPC['worktype'],
					'education'=>$_GPC['education'],
					'express'=>$_GPC['express'],
					'jobtype'=>$_GPC['jobtype'],
					'money'=>$_GPC['money'],
					'age'=>$_GPC['age'],
					'num'=>$_GPC['num'],
					'companyid'=>$_GPC['companyid'],
					'sex'=>$_GPC['sex'],
					'special'=>implode(',',$_GPC['special']),
                    'content' => htmlspecialchars_decode($_GPC['content']),
					'sort'=>$_GPC['sort'],
					'isrecommand'=>$_GPC['isrecommand'],
                   'createtime' => TIMESTAMP,
                );
         
                if (!empty($id)) {
                    unset($data['createtime']);
                    pdo_update('weixinmao_zp_job', $data, array('id' => $id));
                } else {

                   $companyinfo = pdo_get('weixinmao_zp_company',array('id'=>$_GPC['companyid'],'uniacid'=>$_W['uniacid']));
					
					$data['endtime'] = $companyinfo['endtime'];

                   $data['updatetime'] = TIMESTAMP;
                    pdo_insert('weixinmao_zp_job', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('joblist', array('op' => 'display')), 'success');
            }
			
					
		} elseif ($operation == 'updatetime') {
			$id = $_GPC['id'];
          
            pdo_update('weixinmao_zp_job', array('updatetime'=>TIMESTAMP), array('id' => $id));

            message('操作成功！', $this->createWebUrl('joblist', array('op' => 'display')), 'success');

			
		} elseif ($operation == 'display') {
			
			//echo $_GPC['keyword'];
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `jobtitle` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_job') .$condition ;
		
			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_job') .$condition.' ORDER BY  `createtime` DESC ,  `sort`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);

				foreach($list as $k=>$v)
			{
				$companyinfo = pdo_fetch("SELECT companyname  FROM " . tablename('weixinmao_zp_company') . " WHERE id = :id AND  `uniacid` = :uniacid", array(':id' => $v['companyid'],':uniacid' => $_W['uniacid']));

				$jobcateinfo = pdo_fetch("SELECT name  FROM " . tablename('weixinmao_zp_jobcate') . " WHERE id = :id  AND  `uniacid` = :uniacid", array(':id' => $v['worktype'],':uniacid' => $_W['uniacid']));

				$list[$k]['companyname'] = $companyinfo['companyname'];

				$list[$k]['jobcatename'] = $jobcateinfo['name'];
			}
			
				
				$pager = pagination($total, $pindex, $psize);
			}

			
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_job') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，职位信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_job', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}
	
		include $this->template('joblist');
		
	}
	

     public function doWebSharelist() {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');


		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

		if ($operation == 'post') {
						
		} elseif ($operation == 'display') {
         
	
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;

			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `jobtitle` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			
            
          
            $condition = ' WHERE s.uniacid = :uniacid  ';
	
			$params = array(':uniacid' => $_W['uniacid']);

			$sql = " FROM " . tablename('weixinmao_zp_sharerecord') . " as  s  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON s.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = s.jobid ";
          $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_userinfo') . " as u ON u.uid = s.uid ";



			$sqlcount = 'SELECT COUNT(*) '  .$sql . $condition ;
		
			$total = pdo_fetchcolumn($sqlcount, $params);
			
			if (!empty($total)) {
			$sql = 'SELECT s.id AS id,j.jobtitle as jobtitle,j.id AS jobid, j.id AS jobid ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,j.createtime AS createtime,s.view AS view, s.sendnum AS sendnum, s.usednum AS usednum,s.money AS money , u.name AS wechaname '  .$sql . $condition .' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize; ;
				$list = pdo_fetchall($sql, $params);
              
         

			foreach($list as $k=>$v)
			{
			
			}
			
				
				$pager = pagination($total, $pindex, $psize);
			}

			
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_sharerecord') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_sharerecord', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}
	
		include $this->template('sharelist');
		
	}
  



   public function doWebNotelist() {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');
		
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_company') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$companylist  = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$jobcatelist  = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

        $sql = 'SELECT * FROM ' . tablename('weixinmao_zp_city') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		$citylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		$birthdaylist = array('1960', '1961', '1962', '1963', '1964', '1965', '1966', '1967', '1968', '1969','1970', '1971', '1972', '1973', '1974', '1975', '1976', '1977', '1978', '1979', '1980', '1981', '1982', '1983', '1984', '1985', '1986', '1987', '1988', '1989', '1990', '1991', '1992', '1993', '1994', '1995', '1996', '1997', '1998', '1999', '2000');
		$educationlist = array('初中', '高中', '中技', '中专', '大专', '本科', '硕士', '博士', '博后');

		$moneylist = array('1千~2千/月', '1千~2千/月', '2千~3千/月', '3千~4千/月', '4千~5千/月', '5千~1万/月', '1万以上/月');
		$worktypelist = array('全职', '兼职', '实习');
		$currentlist = array('我目前已离职,可快速到岗', '我目前在职，但考虑换个新环境', '观望有好的机会再考虑', '目前暂无跳槽打算', '应届毕业生');
		$expresslist = array('无经验', '1年以下', '1-3年', '3-5年', '5-10年', '10年以上');


		if ($operation == 'post') {
			
			$id = $_GPC['id'];
	
			 if (!empty($id)) {

				 
				 		$item = pdo_fetch("SELECT *  FROM " . tablename('weixinmao_zp_jobnote') . " WHERE id = :id", array(':id' => $id));

				 		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_area') . ' WHERE `uniacid` = :uniacid AND `cityid`=:cityid ORDER BY `sort` DESC';
						$arealist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':cityid'=>$item['cityid']));
							
			}
			
		
			if (checksubmit('submit')) {
			
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'cityid'=>$_GPC['cityid'],
					'jobtitle'=>$_GPC['jobtitle'],
					'name'=>$_GPC['name'],
					'sex'=>$_GPC['sex'],
					'birthday'=>$_GPC['birthday'],
					'education'=>$_GPC['education'],
					'express'=>$_GPC['express'],
					'address'=>$_GPC['address'],
					'email'=>$_GPC['email'],
					'tel'=>$_GPC['tel'],
					'currentstatus'=>$_GPC['currentstatus'],
					'worktype'=>$_GPC['worktype'],
					'jobcateid'=>$_GPC['jobcateid'],
					'money'=>$_GPC['money'],
					'areaid'=>$_GPC['areaid'],
					'address'=>$_GPC['address'],
					'avatarUrl'=>$_GPC['thumb'],
					'content' => ihtmlspecialchars($_GPC['content']),
                  'status'=>$_GPC['status'],
                   'createtime' => TIMESTAMP,

                ); 
         
                if (!empty($id)) {
                    unset($data['createtime']);
                    pdo_update('weixinmao_zp_jobnote', $data, array('id' => $id));
                } else {
                	$data['refreshtime'] = TIMESTAMP;
                    pdo_insert('weixinmao_zp_jobnote', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('notelist', array('op' => 'display')), 'success');
            }
			
			
			
			
		} elseif ($operation == 'display') {
			
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `name` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_jobnote') .$condition ;
		
			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_jobnote') .$condition.' ORDER BY  `createtime`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				
				$pager = pagination($total, $pindex, $psize);
			}
           	if($list)
			{
				
				
				foreach($list as $k=>$v)
					{
						$city_info = pdo_get('weixinmao_zp_city',array('id'=>$v['cityid']));

						$list[$k]['cityname'] =  $city_info['name'];
					}
				
				
			}
			
			
		}elseif($operation == 'getcompany'){
          
          $condition = ' WHERE `uniacid` = :uniacid ';
      		  $params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['companyname'])) {
				$condition .= ' AND `companyname` LIKE :companyname';
				$params[':companyname'] = '%' . trim($_GPC['companyname']) . '%';
			}
			
			
			$sql = 'SELECT id,companyname FROM ' . tablename('weixinmao_zp_company') .$condition ;
        
          $list = pdo_fetchall($sql, $params);
          
          echo json_encode(array('data'=>$list));
          
          exit;
        
        
        }elseif ($operation == 'getcity') {
			$cityid = $_GPC['cityid'];
          $condition = ' WHERE `uniacid` = :uniacid AND `cityid`=:cityid ';
      		  $params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);
			
			
			
			$sql = 'SELECT id,name FROM ' . tablename('weixinmao_zp_area') .$condition ;
        
          $list = pdo_fetchall($sql, $params);
          
          echo json_encode(array('data'=>$list));
          
          exit;
			
		}elseif($operation == 'sendnotelist'){
          $pindex = max(1, intval($_GPC['page']));
		  $psize = 15;
		  $condition = ' WHERE s.uniacid = :uniacid ';
		  $params = array(':uniacid' => $_W['uniacid']);
		  if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `name` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
	      $sort =' ORDER BY s.createtime DESC  ';
		  $condition .= $sort;

		  $sql = " FROM " . tablename('weixinmao_zp_sendnote') . " AS s ";
			
		  $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON s.companyid = c.id ";
			
		  $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobnote') . " as n ON n.id = s.noteid ";
           $sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_city') . " as t ON t.id = n.cityid ";

		  $sqltotal = 'SELECT *   '  .$sql .$where. $condition ;
          
          $total = pdo_fetchcolumn($sqltotal, $params);
          if (!empty($total)) {
           
                 $sql = 'SELECT s.id AS id,s.money AS money , s.paid AS paid,s.status AS status, s.gettime AS gettime, s.getpaytime AS getpaytime , s.createtime AS createtime, s.paytime AS paytime, t.name AS cityname, c.companyname AS companyname, n.name  AS name, n.jobtitle AS jobtitle, n.tel AS tel, n.sex AS sex ,n.education AS education ,n.express AS express  '  .$sql .$where. $condition .'  LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
                 $list = pdo_fetchall($sql, $params);
                 $pager = pagination($total, $pindex, $psize);


          }
          
        }elseif($operation == 'sendnote'){
          
          
          $noteid = $_GPC['id'];
	
			 if (!empty($noteid)) {

				 
				 		
							
			}
			
		
			if (checksubmit('submit')) {
			    $orderid = date("YmdHis"). rand(100000, 999999);
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'companyid'=>$_GPC['companyid'],
                    'noteid'=>$_GPC['noteid'],
                    'money'=>$_GPC['money'],
                    'mark'=>$_GPC['mark'],
                    'status'=>0,
                    'paid'=>0,
                    'orderid'=>$orderid,
                    'createtime' => TIMESTAMP,
                ); 
              
               $sendnote = pdo_get('weixinmao_zp_sendnote',array('uniacid'=>$_W['uniacid'],'companyid'=>$_GPC['companyid'],'noteid'=>$_GPC['noteid']));
         
                if (!$sendnote) {
                
                    pdo_insert('weixinmao_zp_sendnote', $data);
                    $id = pdo_insertid();
                  
                    pdo_update('weixinmao_zp_jobnote',array('send'=>1), array('id' => $_GPC['noteid']));
                  
                    message('操作成功！', $this->createWebUrl('notelist', array('op' => 'display')), 'success');

                }
              
            }
          
        
        
        
        
        }  elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_jobnote') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，职位信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_jobnote', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}elseif ($operation == 'deletesendnote') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_sendnote') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}

			pdo_delete('weixinmao_zp_sendnote', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}
	
		include $this->template('notelist');
		
	}




public function doWebRegsub() {
		global $_W, $_GPC;
			load()->func('tpl');

		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `name` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			

			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_regsub') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_regsub') .$condition.' ORDER BY  `createtime`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				$pager = pagination($total, $pindex, $psize);
			}




//			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_city') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");


		} elseif ($operation == 'post') {
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$area = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_regsub') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($area)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('regsub', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_regsub', array('id' => $id));
			message('删除成功！', $this->createWebUrl('regsub', array('op' => 'display')), 'success');
		}  else {
			message('请求方式不存在');
		}
		include $this->template('regsub', TEMPLATE_INCLUDEPATH, true);
	}


public function doWebRegmoney() {
		global $_W, $_GPC;
			load()->func('tpl');

		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `name` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			

			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_regmoney') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_regmoney') .$condition.' ORDER BY  `createtime`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				$pager = pagination($total, $pindex, $psize);
			}




//			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_city') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");


		} elseif ($operation == 'post') {
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$area = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_regmoney') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($area)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('regmoney', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_regmoney', array('id' => $id));
			message('删除成功！', $this->createWebUrl('regmoney', array('op' => 'display')), 'success');
		}  else {
			message('请求方式不存在');
		}
		include $this->template('regmoney', TEMPLATE_INCLUDEPATH, true);
	}


public function doWebCity() {
		global $_W, $_GPC;
			load()->func('tpl');

		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `name` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			

			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_city') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_city') .$condition.' ORDER BY  `sort`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				$pager = pagination($total, $pindex, $psize);
			}




//			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_city') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");


		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['name'],
					'firstname' => $_GPC['firstname'],
					'sort' => $_GPC['displayorder'],
					'enabled'=>1,
					'ison'=>$_GPC['ison'],
					'ishot'=>$_GPC['ishot'],
					);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_city', $data, array('id' => $id));


				} else {
					pdo_insert('weixinmao_zp_city', $data);
					$id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('city', array('op' => 'display')), 'success');
			}
			$area = pdo_fetch("select * from " . tablename('weixinmao_zp_city') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$area = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_city') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($area)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('city', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_city', array('id' => $id));
			message('删除成功！', $this->createWebUrl('city', array('op' => 'display')), 'success');
		} elseif ($operation == 'donedate') {
				$cityinfo = pdo_get('weixinmao_zp_city',array("uniacid" => $_W['uniacid'],'ison'=>1));
				pdo_update('weixinmao_zp_area',array('cityid'=>$cityinfo['id']),array('uniacid' => $_W['uniacid']));
				pdo_update('weixinmao_zp_company',array('cityid'=>$cityinfo['id']),array('uniacid' => $_W['uniacid']));
				pdo_update('weixinmao_zp_jobnote',array('cityid'=>$cityinfo['id']),array('uniacid' => $_W['uniacid']));

			


				message('操作完成', $this->createWebUrl('city', array('op' => 'display')), 'success');



		} else {
			message('请求方式不存在');
		}
		include $this->template('city', TEMPLATE_INCLUDEPATH, true);
	}
	





public function doWebArea() {
		global $_W, $_GPC;
			load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
				$citylist = pdo_fetchall("select id, name from " . tablename('weixinmao_zp_city') . " where  uniacid=:uniacid ", array( ":uniacid" => $_W['uniacid']));

		if ($operation == 'display') {


			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `name` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			

			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_area') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_area') .$condition.' ORDER BY  `cityid`  DESC  LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
              	if($list)
                {
                	foreach($list as $k=>$v)
                    {
                    	$cityinfo = pdo_get('weixinmao_zp_city',array('uniacid'=>$_W['uniacid'],'id'=>$v['cityid']));
                      	$list[$k]['cityname'] = $cityinfo['name'];
                    }
                }
				$pager = pagination($total, $pindex, $psize);
			}



		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['name'],
					'cityid' => $_GPC['cityid'],
					'sort' => $_GPC['sort'],
					'enabled'=>$_GPC['enabled']
					);
					
				if (!empty($id)) {
					pdo_update('weixinmao_zp_area', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_area', $data);
					$id = pdo_insertid();
				}
				message('更新区域成功！', $this->createWebUrl('area', array('op' => 'display')), 'success');
			}
			$area = pdo_fetch("select * from " . tablename('weixinmao_zp_area') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$area = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_area') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($area)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('area', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_area', array('id' => $id));
			message('删除成功！', $this->createWebUrl('area', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('area', TEMPLATE_INCLUDEPATH, true);
	}

public function doWebJobcate() {
		global $_W, $_GPC;
			load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
          	$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
		
			$sqlcount = "SELECT COUNT(*) FROM " . tablename('weixinmao_zp_jobcate') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC " ;
			$total = pdo_fetchcolumn($sqlcount, $params);
			if (!empty($total)) {
				$sqllist = "SELECT * FROM " . tablename('weixinmao_zp_jobcate') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC  LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sqllist, $params);
				
				$pager = pagination($total, $pindex, $psize);
			}
          
          
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['name'],
					'sort' => $_GPC['sort'],
					'enabled'=>$_GPC['enabled']
					);
					
				if (!empty($id)) {
					pdo_update('weixinmao_zp_jobcate', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_jobcate', $data);
					$id = pdo_insertid();
				}
				message('更新职业类别成功！', $this->createWebUrl('jobcate', array('op' => 'display')), 'success');
			}
			$area = pdo_fetch("select * from " . tablename('weixinmao_zp_jobcate') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$area = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_jobcate') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($area)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('jobcate', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_jobcate', array('id' => $id));
			message('删除成功！', $this->createWebUrl('jobcate', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('jobcate', TEMPLATE_INCLUDEPATH, true);
	}





	public function doWebjobprice() {
		global $_W, $_GPC;
			load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_jobprice') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
		
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['name'],
				    'beginprice'=>$_GPC['beginprice'],
					'endprice'=>$_GPC['endprice'],
					'sort' => $_GPC['sort'],
					'enabled'=>1
					);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_jobprice', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_jobprice', $data);
					$id = pdo_insertid();
				}
				message('更新薪资范围成功！', $this->createWebUrl('jobprice', array('op' => 'display')), 'success');
			}

			$oldhouseprice = pdo_fetch("select * from " . tablename('weixinmao_zp_jobprice') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_jobprice') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('jobprice', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_jobprice', array('id' => $id));
			message('删除成功！', $this->createWebUrl('jobprice', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('jobprice', TEMPLATE_INCLUDEPATH, true);
	}

	public function doWebcompanyaccount() {
		global $_W, $_GPC;
			load()->func('tpl');

		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
			$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_company') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$companylist  = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
		if ($operation == 'display') {
			//$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_companyaccount') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY `createtime` DESC ");
			
          
           	$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
		
			$sqlcount = "SELECT COUNT(*) FROM " . tablename('weixinmao_zp_companyaccount') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY createtime DESC " ;
			$total = pdo_fetchcolumn($sqlcount, $params);
			if (!empty($total)) {
				$sqllist = "SELECT * FROM " . tablename('weixinmao_zp_companyaccount') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY createtime DESC  LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sqllist, $params);
				
				$pager = pagination($total, $pindex, $psize);
			}
          
          
          if($list)
			{
				foreach ($list as $k => $v) {
				
				$companyinfo = pdo_fetch("SELECT companyname FROM " . tablename('weixinmao_zp_company') . " WHERE uniacid = '{$_W['uniacid']}' AND id=".$v['companyid']);
				$list[$k]['companyname'] = $companyinfo['companyname'];
			}
			}
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
		
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['name'],
					'companyid'=>$_GPC['companyid'],
					 'createtime' => TIMESTAMP,
					'status'=>$_GPC['enabled']
					);
				if($_GPC['password'])
					$data['password'] = md5($_GPC['password']);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_companyaccount', $data, array('id' => $id));
                  
                     if($_GPC['enabled'] == 1)
                        {
						 $companyinfo =  pdo_get('weixinmao_zp_company', array('id' => $_GPC['companyid']));
                       
                       	 $content ='尊敬的企业用户,您申请入驻的账号已审核通过.';
                         $this->Sendsmsbao($companyinfo['tel'],$content);

                        }
				} else {
					pdo_insert('weixinmao_zp_companyaccount', $data);
					$id = pdo_insertid();
				}
				message('更新区域成功！', $this->createWebUrl('companyaccount', array('op' => 'display')), 'success');
			}

			$companyaccount = pdo_fetch("select * from " . tablename('weixinmao_zp_companyaccount') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_companyaccount') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('companyaccount', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_companyaccount', array('id' => $id));
			message('删除成功！', $this->createWebUrl('companyaccount', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('companyaccount', TEMPLATE_INCLUDEPATH, true);
	}






		public function doWebCate() {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			if (!empty($_GPC['displayorder'])) {
				foreach ($_GPC['displayorder'] as $id => $displayorder) {
					pdo_update('weixinmao_zp_category', array('displayorder' => $displayorder), array('id' => $id, 'weid' => $_W['uniacid']));
				}
				message('分类排序更新成功！', $this->createWebUrl('cate', array('op' => 'display')), 'success');
			}
			$children = array();
			
			$category = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_category') . " WHERE weid = '{$_W['uniacid']}' ORDER BY parentid ASC, displayorder DESC");
			foreach ($category as $index => $row) {
				if (!empty($row['parentid'])) {
					$children[$row['parentid']][] = $row;
					unset($category[$index]);
				}
			}
			include $this->template('category');
		} elseif ($operation == 'post') {
			
			$parentid = intval($_GPC['parentid']);
			$id = intval($_GPC['id']);
			if (!empty($id)) {
				$category = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_category') . " WHERE id = :id AND weid = :weid", array(':id' => $id, ':weid' => $_W['uniacid']));
			} else {
				$category = array(
					'displayorder' => 0,
				);
			}
			if (!empty($parentid)) {
				$parent = pdo_fetch("SELECT id, name FROM " . tablename('weixinmao_zp_category') . " WHERE id = '$parentid'");
				if (empty($parent)) {
					message('抱歉，上级分类不存在或是已经被删除！', $this->createWebUrl('post'), 'error');
				}
			}
			if (checksubmit('submit')) {
				if (empty($_GPC['catename'])) {
					message('抱歉，请输入分类名称！');
				}
				$data = array(
					'weid' => $_W['uniacid'],
					'name' => $_GPC['catename'],
					'enabled' => intval($_GPC['enabled']),
					'displayorder' => intval($_GPC['displayorder']),
					'isrecommand' => intval($_GPC['isrecommand']),
					'model'=>intval($_GPC['model']),
					'description' => $_GPC['description'],
					'parentid' => intval($parentid),
					'thumb' => $_GPC['thumb']
				);
				if (!empty($id)) {
					unset($data['parentid']);
					pdo_update('weixinmao_zp_category', $data, array('id' => $id, 'weid' => $_W['uniacid']));
					load()->func('file');
					file_delete($_GPC['thumb_old']);
				} else {
					pdo_insert('weixinmao_zp_category', $data);
					$id = pdo_insertid();
				}
				message('更新分类成功！', $this->createWebUrl('cate', array('op' => 'display')), 'success');
			}
			include $this->template('category');
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$category = pdo_fetch("SELECT id, parentid FROM " . tablename('weixinmao_zp_category') . " WHERE id = '$id'");
			if (empty($category)) {
				message('抱歉，分类不存在或是已经被删除！', $this->createWebUrl('weixinmao_zp_category', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_category', array('id' => $id, 'parentid' => $id), 'OR');
			message('分类删除成功！', $this->createWebUrl('cate', array('op' => 'display')), 'success');
		}
		
	}




public function doWebActive() {
		//这个操作被定义用来呈现 管理中心导航菜单
		global $_GPC, $_W;
		load()->func('tpl');

	
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'post') {
			
			$id = $_GPC['id'];
	
			 if (!empty($id)) {
				 $item = pdo_fetch("SELECT *  FROM " . tablename('weixinmao_zp_active') . " WHERE id = :id", array(':id' => $id));		
			}
		  //  $newhouselist = pdo_fetchall("SELECT id,housename  FROM " . tablename('weixinmao_house_houseinfo') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));		
			
			if (checksubmit('submit')) {
				if(is_array($_GPC['thumbs'])){
					$thumb_data['thumb_url'] = serialize($_GPC['thumbs']);
				}
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'title'=>$_GPC['title'],
					'begintime'=>$_GPC['begintime'],
                   'endtime'=>$_GPC['endtime'],
					'mainwork'=>$_GPC['mainwork'],
					'fuwork'=>$_GPC['fuwork'],
                    'content' => ihtmlspecialchars($_GPC['content']),
					'sort'=>$_GPC['sort'],
					'thumb'=>$_GPC['thumb'],
                    'createtime' => TIMESTAMP,
					'money'=>$_GPC['money'],
					'pid'=>$_GPC['pid']
                );
               
                if (!empty($id)) {
                    unset($data['createtime']);
                    pdo_update('weixinmao_zp_active', $data, array('id' => $id));
                } else {
                    pdo_insert('weixinmao_zp_active', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('active', array('op' => 'display')), 'success');
            }
			
			
			
			
		} elseif ($operation == 'display') {
			
		
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
			
			if (!empty($_GPC['keyword'])) {
				$condition .= ' AND `title` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
			}
			
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_active') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_active') .$condition.' ORDER BY  `sort`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
              
              		$jobrecordcount = 0;

			if($list)
			{
				foreach($list as $k=>$v)
				{
					
						$activerecord = pdo_getall('weixinmao_zp_activerecord',array('uniacid'=>$_W['uniacid'],'aid'=>$v['id']));
						foreach ($activerecord as $k2 => $v2) { 
							# code...
							$record = pdo_getall('weixinmao_zp_jobrecord',array('uniacid'=>$_W['uniacid'],'companyid'=>$v2['companyid']));
 							$jobrecordcount  = $jobrecordcount  + count($record);
						}



             $list[$k]['jobrecordcount'] =$jobrecordcount;
 					$list[$k]['companycount'] =count($activerecord);
					//$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					$list[$k]['thumb'] = tomedia($v['thumb']);
                   $begintime = strtotime($v['begintime']);
                  $endtime = strtotime($v['endtime']);
                  if($begintime >time())
                  {
                  		$list[$k]['status_str'] = '未开始';
                  }else{
                  
                  		if($endtime < time())
                        {
                       	 $list[$k]['status_str'] = '已结束';
                        }else{
                         
                          $list[$k]['status_str'] = '进行中';
                        
                        }
                  
                  }
                  
                  
						$jobrecordcount = 0;
				}
			}
              
              
              
              
				$pager = pagination($total, $pindex, $psize);
			}
			
			
			
		}elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_active') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，商品不存在或是已经被删除！');
			}
			pdo_delete('weixinmao_zp_active', array('id' => $id));

			message('删除成功！', referer(), 'success');
		}
		include $this->template('active');
		
	}



public function doWebToplist() {
		global $_W, $_GPC;
	    load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_toplist') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
				
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'money' => $_GPC['money'],
				    'days'=>$_GPC['days'],
					'sort' => $_GPC['sort'],
					'enabled'=>1
					);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_toplist', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_toplist', $data);
					$id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('toplist', array('op' => 'display')), 'success');
			}

			$toplist = pdo_fetch("select * from " . tablename('weixinmao_zp_toplist') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_toplist') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('toplist', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_toplist', array('id' => $id));
			message('删除成功！', $this->createWebUrl('toplist', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('toplist', TEMPLATE_INCLUDEPATH, true);
	}



public function doWebPaytoplist() {
		global $_W, $_GPC;
	    load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_paytoplist') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
				
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'money' => $_GPC['money'],
				    'days'=>$_GPC['days'],
					'sort' => $_GPC['sort'],
					'enabled'=>1
					);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_paytoplist', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_paytoplist', $data);
					$id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('paytoplist', array('op' => 'display')), 'success');
			}

			$toplist = pdo_fetch("select * from " . tablename('weixinmao_zp_paytoplist') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_paytoplist') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('paytoplist', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_paytoplist', array('id' => $id));
			message('删除成功！', $this->createWebUrl('paytoplist', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('paytoplist', TEMPLATE_INCLUDEPATH, true);
	}


public function doWebPayjoblist() {
		global $_W, $_GPC;
	    load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_payjoblist') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
				
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'money' => $_GPC['money'],
				    'days'=>$_GPC['days'],
					'sort' => $_GPC['sort'],
					'enabled'=>1
					);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_payjoblist', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_payjoblist', $data);
					$id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('payjoblist', array('op' => 'display')), 'success');
			}

			$toplist = pdo_fetch("select * from " . tablename('weixinmao_zp_payjoblist') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_payjoblist') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('payjoblist', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_payjoblist', array('id' => $id));
			message('删除成功！', $this->createWebUrl('payjoblist', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('payjoblist', TEMPLATE_INCLUDEPATH, true);
	}



public function doWebCompanyrole() {
		global $_W, $_GPC;
	    load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_companyrole') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
				
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'money' => $_GPC['money'],
				    'days'=>$_GPC['days'],
					'jobnum'=>$_GPC['jobnum'],
					'notenum'=>$_GPC['notenum'],
					'isinit'=>$_GPC['isinit'],
					'sort' => $_GPC['sort'],
					'enabled'=>1
					);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_companyrole', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_companyrole', $data);
					$id = pdo_insertid();
				}
				message('更新区域成功！', $this->createWebUrl('companyrole', array('op' => 'display')), 'success');
			}

			$toplist = pdo_fetch("select * from " . tablename('weixinmao_zp_companyrole') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
			
		}elseif($operation == 'updaterole'){

			$roleid = $_GPC['roleid'];
			if($roleid == 0 )
			{
			message('请选择同步等级');
			}

		   $companyrole = pdo_get('weixinmao_zp_companyrole',array('uniacid'=>$_W['uniacid'],'id'=>$roleid));
		   $endtime = time()+60*60*24*$companyrole['days'];
		   $data = array('endtime'=>$endtime);
          	$companydata = array('jobnum'=>$companyrole['jobnum'],'notenum'=>$companyrole['notenum'],'endtime'=>$endtime,'roleid'=>$roleid);

		   
		   pdo_update('weixinmao_zp_company',$companydata,array('uniacid'=>$_W['uniacid']));
		   pdo_update('weixinmao_zp_job',$data,array('uniacid'=>$_W['uniacid']));

			message('同步成功！', $this->createWebUrl('companyrole', array('op' => 'display')), 'success');


		}elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_companyrole') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('companyrole', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_companyrole', array('id' => $id));
			message('删除成功！', $this->createWebUrl('companyrole', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('companyrole', TEMPLATE_INCLUDEPATH, true);
	}


  public function doWebLookrole() {
		global $_W, $_GPC;
	    load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_lookrole') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY sort DESC");
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
				
			if (checksubmit('submit')) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'money' => $_GPC['money'],
					'looknum'=>$_GPC['looknum'],
					'isinit'=>$_GPC['isinit'],
					'sort' => $_GPC['sort'],
					'enabled'=>1
					);
				if (!empty($id)) {
					pdo_update('weixinmao_zp_lookrole', $data, array('id' => $id));
				} else {
					pdo_insert('weixinmao_zp_lookrole', $data);
					$id = pdo_insertid();
				}
				message('更新区域成功！', $this->createWebUrl('lookrole', array('op' => 'display')), 'success');
			}

			$toplist = pdo_fetch("select * from " . tablename('weixinmao_zp_lookrole') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
			
		}elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$oldhouseprice = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_lookrole') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
			if (empty($oldhouseprice)) {
				message('抱歉，不存在或是已经被删除！', $this->createWebUrl('lookrole', array('op' => 'display')), 'error');
			}
			pdo_delete('weixinmao_zp_lookrole', array('id' => $id));
			message('删除成功！', $this->createWebUrl('lookrole', array('op' => 'display')), 'success');
		} else {
			message('请求方式不存在');
		}
		include $this->template('lookrole', TEMPLATE_INCLUDEPATH, true);
	}

  
  
  
  

  public function doWebOrder()
	{
		global $_GPC, $_W;
		load()->func('tpl');
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		
		if ($operation == 'post') {
			$id = $_GPC['id'];
			if (!empty($id)) {
				 $item = pdo_fetch("SELECT *  FROM " . tablename('weixinmao_zp_order') . " WHERE id = :id", array(':id' => $id));			
			}
			if (checksubmit('submit')) {
				//print_r($_GPC);
				//exit;
                $data = array(
                    'uniacid' => $_W['uniacid'],
					'title'=>$_GPC['title'],
                    'content' => htmlspecialchars_decode($_GPC['content']),
					'sort'=>$_GPC['sort'],
					'thumb'=>$_GPC['thumb'],
                    'createtime' => TIMESTAMP,
                );
                if (!empty($id)) {
                    unset($data['createtime']);
                    pdo_update('weixinmao_zp_order', $data, array('id' => $id));
                } else {
                    pdo_insert('weixinmao_zp_order', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('order', array('op' => 'display')), 'success');
            }
		} elseif($operation == 'done'){
			
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_order') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，订单不存在或是已经被删除！');
			}
			 pdo_update('weixinmao_zp_order', array('status'=>2), array('id' => $id));

			message('操作成功！', referer(), 'success');
			
		
		}elseif ($operation == 'display') {
			$status = $_GPC['status'];
			if(!isset($status))
					$status = -1;
			$pindex = max(1, intval($_GPC['page']));
			$psize = 15;
			$condition = ' WHERE `uniacid` = :uniacid ';
			$params = array(':uniacid' => $_W['uniacid']);
            
            $totalmoney = pdo_getcolumn('weixinmao_zp_order', array('paid' => 1, 'uniacid' => $_W['uniacid']), array('sum(money)'));
            if(!$totalmoney)
              $totalmoney = 0;
          $time = strtotime(date('Y-m-d'));
          $todaymoneyinifo = pdo_fetch("SELECT sum(money) AS totalmoney FROM " . tablename('weixinmao_zp_order') . " WHERE uniacid = :uniacid AND paid = 1 AND createtime > ".$time, array(':uniacid' => $_W['uniacid']));
         if($todaymoneyinfo)
           $todaymoney = $todaymoneyinfo['totalmoney'];
          else
            $todaymoney = 0;

          
          
          
			
			if (!empty($_GPC['member'])) {
				$condition .= ' AND `tel` LIKE :title';
				$params[':title'] = '%' . trim($_GPC['member']) . '%';
			}
			if($status ==0)
			{
				$condition .= ' AND `paid` = 0 ';
			}elseif($status ==1)
			{
					$condition .= ' AND `paid` = 1 AND status =1 ';
			}elseif($status == 2){
				
				$condition .= ' AND `paid` = 1 AND status =2 ';
			}elseif($status ==3){
				
				$condition .= ' AND `paid` = 1 AND status =3 ';
			}
			
			$sql = 'SELECT COUNT(*) FROM ' . tablename('weixinmao_zp_order') .$condition ;

			$total = pdo_fetchcolumn($sql, $params);
			
			if (!empty($total)) {
				$sql = 'SELECT * FROM  ' . tablename('weixinmao_zp_order') .$condition.' ORDER BY  `createtime`  DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				$pager = pagination($total, $pindex, $psize);
				if($list)
				{
						foreach($list as $k=>$v)
						{
							if($v['couponid']>0)
							{
								$coupon_order = pdo_fetch("SELECT title FROM " . tablename('weixinmao_house_order') . " WHERE id = :id", array(':id' => $v['couponid']));
								//print_r($coupon_order);
								$list[$k]['coupon'] = $coupon_order['title'];
							}else{
								
								$list[$k]['coupon'] = '';
							}
						}
					
				}
				
			}
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_order') . " WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，订单不存在或是已经被删除！');
			}
			pdo_delete('weixinmao_zp_order', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('order');
		
	}













  }