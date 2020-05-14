<?php

defined('IN_IA') or exit('Access Denied');

class Weixinmao_zpModuleWxapp extends WeModuleWxapp {


	public function doPageGetbanner()
		{
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_adv') ."WHERE  enabled =1 AND weid=:weid ORDER BY displayorder DESC  ",array(":weid" => $_W['uniacid']));
			if($list)
			{
				foreach($list as $k=>$v)
				{
					$list[$k]['thumb'] = tomedia($v['thumb']);
					
				}
			}
			return $this->result(0, 'success', $list);
			
		}
  
    public function dealMsglist($uid)
  {
    global $_GPC, $_W;
  		$uid = $uid;
    
   
    	 $msglist =   pdo_fetchall("SELECT id,createtime  FROM " . tablename('weixinmao_zp_msgidlist') ." WHERE  uniacid=:weid AND status = 0  AND uid=".$uid,array(":weid" => $_W['uniacid']));
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
    public function doPageUploadvideo()
	 {
		 
		 global $_GPC, $_W;
		 
	
		 //$filename =str_replace( array('attachment; filename=', '"',' '),'',$response['headers']['Content-disposition']);
		//$filename = 'images/'.$_W['uniacid'].'/diamondvote/'.date('Y/m/').$filename;
			load()->func('file');
			
		$log = json_encode($_FILES);
	
		$res = file_upload($_FILES['file'],'video');
			$data = array(
					'weid' => $_W['uniacid'],
					'content'=>json_encode($res)
					);
		
	   // pdo_insert('weixinmao_house_log', $data);
		
			//file_write($filename, $response['content']);
			//file_image_thumb(ATTACHMENT_ROOT.$filename,ATTACHMENT_ROOT.$filename,$media['width']);
		 return $this->result(0, 'success', $res);
		
	 }
  
  	 public function doPageSaveFormId()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		if(!$uid || $uid <=0 )
		{
			return $this->result(1, '用户未授权');
		}
	
		$msgdata = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$uid,
					'form_id' => $_GPC['form_id'],
					'status'=>0,
					'createtime' => TIMESTAMP
					);
	    pdo_insert('weixinmao_zp_msgidlist', $msgdata);

		$this->dealMsglist($uid);
		
	     $list = array('msg'=>'提交成功','error'=>0,'msgcount'=>$msgcount);
		
		
		return $this->result(0, 'success', $list);
	}

	
	public function doPageGetphone()
		{

			global $_GPC, $_W;
			include "inc/wxBizDataCrypt.php";
		//var_dump($is);
		//	$code          = $_GET['code'];
			$iv            = $_GPC['iv'];
			$encryptedData = $_GPC['encryptedData'];
			$appid      = $_W['uniaccount']['key'];;//小程序唯一标识   (在微信小程序管理后台获取)
			$appsecret  = $_W['uniaccount']['secret'];//小程序的 app secret (在微信小程序管理后台获取)


		//	$grant_type = "authorization_code"; //授权（必填）
			 
		//	$params = "appid=".$appid."&secret=".$appsecret."&js_code=".$code."&grant_type=".$grant_type;
		//	$url = "https://api.weixin.qq.com/sns/jscode2session?".$params;
			 
		//	$res = json_decode($this->httpGet($url),true);
			//json_decode不加参数true，转成的就不是array,而是对象。 下面的的取值会报错  Fatal error: Cannot use object of type stdClass as array in
		//	$sessionKey = $res['session_key'];//取出json里对应的值
			 
				//echo $_SESSION['session_key'];
			$pc = new WXBizDataCrypt($appid, $_SESSION['session_key']);
		//	var_dump($pc);

		//	echo $encryptedData, $iv;

			$errCode = $pc->decryptData($encryptedData, $iv, $data);
			 $obj = json_decode($data);
			 var_dump($obj);
			 $uid = $_GPC['uid'];
			 $tel = $obj->phoneNumber;
			// echo $tel;
			 pdo_update('weixinmao_zp_userinfo',array('tel'=>$tel),array('uid'=>$uid));
		
         return $this->result(0, 'success', array());

		}


public function doPageIntro()
		{
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$list = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_intro')." WHERE   uniacid=:uniacid",array(":uniacid" => $_W['uniacid']));
			$list['logo'] = tomedia($list['logo']);
			$list['description']=html_entity_decode($list['content']);
 			$list['content'] = trim(html_entity_decode(strip_tags($list['content'])),chr(0xc2).chr(0xa0));  
			
			
			$map = $this->Convert_BD09_To_GCJ02($list['lat'],$list['lng']);
			$list['lat'] = $map['lat'];
			$list['lng'] = $map['lng'];
  			$list['intro'] = $list;
			
			
			
			return $this->result(0, 'success', $list);
		}



		public function doPageRegister()
		{
			global $_GPC, $_W;
			$uid = $_GPC['uid'];
			$tel = $_GPC['phone'];
			$code = $_GPC['code'];
			$is_code = pdo_fetch("SELECT id FROM " . tablename('weixinmao_wy_mobile_verify_code') ." WHERE uniacid=:uniacid AND verify_code=:code AND mobile=".$tel,array(":uniacid" => $_W['uniacid'],':code'=>$code));

			if(!$is_code)
				{
					$list = array('msg'=>'验证码错误','error'=>1);
					 return $this->result(0, 'success', $list);
				}

			$is_user = pdo_fetch("SELECT id FROM " . tablename('weixinmao_wy_userinfo') ." WHERE uniacid=:uniacid AND uid=:uid ",array(":uniacid" => $_W['uniacid'],':uid'=>$uid));

			if($is_user)
			{
				$list = array('msg'=>'您已认证过手机号','error'=>1);
					 return $this->result(0, 'success', $list);

			}

			$userinfodata = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$uid,
					'tel' => $tel,
					'createtime' => TIMESTAMP
					);

	    pdo_insert('weixinmao_wy_userinfo', $userinfodata);
	    $id = pdo_insertid();
	    $list = array('msg'=>'认证成功','error'=>0);
	    return $this->result(0, 'success', $list);

		}




public function doPageSendsms()
	{
        global $_GPC, $_W;
		$phone = $_GPC['phone'];
		$is_user = pdo_fetch("SELECT id,tel FROM " . tablename('weixinmao_zp_userinfo') ." WHERE  uniacid=:weid AND tel=".$phone,array(":weid" => $_W['uniacid']));

		if($is_user)
			return $this->result(0, 'success', array('msg'=>'手机号已经存在','error'=>1));

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
	
			$intro = pdo_fetch("SELECT smsaccount,smspwd FROM " . tablename('weixinmao_zp_intro')." WHERE   uniacid=:uniacid",array(":uniacid" => $_W['uniacid']));



		$smsapi = "http://www.smsbao.com/"; //短信网关
		$user = $intro['smsaccount']; //短信平台帐号
		$pass = md5($intro['smspwd']); //短信平台密码
		$code = rand(100000,999999);
		$content="验证码为：".$code;//要发送的短信内容
		
		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
		$result =file_get_contents($sendurl) ;

       if( $statusStr[$result]=='success')
			{
				
				//$data = array( 'mobile'=>$phone,'create_time'=>time(),'verify_code'=>$code);
				//$is_exist = M("mobile_verify_code")->where(array('mobile'=>$phone))->find();


			   $is_exist = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_mobile_verify_code') ." WHERE  uniacid=:weid AND phone=".$phone,array(":weid" => $_W['uniacid']));


				if($is_exist)
					{
				$data = array(
					'verify_code'=>$code,
                    'createtime' => TIMESTAMP,
                			);
                    pdo_update('weixinmao_zp_mobile_verify_code', $data, array('mobile' => $mobile,'uniacid'=>$_W['uniacid']));
					}else{
      
                   $data = array(
                    'uniacid' => $_W['uniacid'],
					'mobile'=>$phone,
					'verify_code'=>$code,
                    'createtime' => TIMESTAMP,
                			);
                	 
                    pdo_insert('weixinmao_zp_mobile_verify_code', $data);
                    $id = pdo_insertid();
                
					}

				return $this->result(0, 'success', array('msg'=>'发送成功','error'=>0));

			}else{
			return $this->result(0, 'success', array('msg'=>'发送失败','error'=>1));
				
				}


	}

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

	public function doPageGetshopmsg()
		{
			global $_GPC, $_W;
			$companyid = intval($_GPC['companyid']);
			

			$msglist = pdo_getall('weixinmao_zp_msgidlist',array('companyid'=>$companyid,'status'=>0));

		
			$msgcount = count($msglist );
			$data = array('msgcount'=>$msgcount);

			return $this->result(0, 'success', $data);
		}


	 public function doPageSaveshopmsg()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
	    $companyid = $_GPC['companyid'];
		if(!$uid || $uid <=0 )
		{
			return $this->result(1, '用户未授权');
		}
	

		$msgdata = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$uid,
					'companyid'=>$companyid,
					'form_id' => $_GPC['form_id'],
					'status'=>0,
					'createtime' => TIMESTAMP
					);
	    pdo_insert('weixinmao_zp_msgidlist', $msgdata);

		
	$msglist = pdo_getall('weixinmao_zp_msgidlist',array('companyid'=>$companyid,'status'=>0));

		
			$msgcount = count($msglist );
		
	     $list = array('msg'=>'提交成功','error'=>0,'msgcount'=>$msgcount);
		
		
		return $this->result(0, 'success', $list);
	}

public function doPageGetcitylist()
		{
			global $_GPC, $_W;
		
			
			
			$condition_hot = " WHERE  ishot = 1 AND  uniacid=:uniacid  ORDER BY sort DESC ";
			$sql = 'SELECT id ,name FROM ' . tablename('weixinmao_zp_city') . $condition_hot ;
			$hotlist = pdo_fetchall($sql,array(":uniacid" => $_W['uniacid']));
			
			$condition = " WHERE   uniacid=:uniacid GROUP BY firstname ORDER BY firstname ,sort DESC";
			$sql = 'SELECT firstname FROM ' . tablename('weixinmao_zp_city') . $condition ;
			$firstnamelist = pdo_fetchall($sql,array(":uniacid" => $_W['uniacid']));
			
			
			$conditionlist = " WHERE   uniacid=:uniacid AND firstname=:firstname ";
			$sql = 'SELECT id ,name FROM ' . tablename('weixinmao_zp_city') . $conditionlist ;
			foreach($firstnamelist AS $k=>$v)
			{
				$list = pdo_fetchall($sql,array(":uniacid" => $_W['uniacid'],':firstname'=>$v['firstname']));

				$firstnamelist[$k]['firstlist']= $list;
			
				
				
			}
		//	print_r($firstnamelist);
			
			$data = array('hotlist'=>$hotlist,'firstnamelist'=>$firstnamelist);

			return $this->result(0, 'success', $data);
			
		}

	public function doPageSysinit(){

		global $_GPC, $_W;
		$intro = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_intro')." WHERE   uniacid=:uniacid",array(":uniacid" => $_W['uniacid']));
		return $this->result(0, 'success', array('intro'=>$intro));


	}
	public function doPageGetIndexList()
	{
		global $_GPC, $_W;

	
		$city = $_GPC['city'];	
		$cityinfo = pdo_get('weixinmao_zp_city',array('name'=>$city,'uniacid'=>$_W['uniacid']));
		if(!$cityinfo)
			{
				$cityinfo = pdo_get('weixinmao_zp_city',array('uniacid'=>$_W['uniacid'],'ison'=>1));
			}
					



		$intro = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_intro')." WHERE   uniacid=:uniacid",array(":uniacid" => $_W['uniacid']));
	
      if($intro['ischeck'] == 1)
      {
      		$category_list = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_category') ." WHERE weid=:weid AND enabled=2 ",array(":weid" => $_W['uniacid']));
			
			$glist  = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_content') ." WHERE  uniacid=:uniacid AND pid=".$category_list['id']."  ORDER BY sort DESC",array(":uniacid" => $_W['uniacid']));
			if($glist)
			{
				foreach($glist as $k=>$v)
				{
					$glist[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					$glist[$k]['thumb'] =tomedia($v['thumb']);
					
				}
			}

      
      }
      

	    $bannerlist = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_adv') ."WHERE  enabled =1 AND weid=:weid ORDER BY displayorder DESC  ",array(":weid" => $_W['uniacid']));
			if($bannerlist)
			{
				foreach($bannerlist as $k=>$v)
				{
					$bannerlist[$k]['thumb'] = tomedia($v['thumb']);
					
				}
			}



		$condition = ' WHERE `uniacid` = :uniacid AND `status`=0 AND `isrecommand` = 1 AND endtime > '.time().'   ORDER BY sort DESC LIMIT 30 ';
		$params = array(':uniacid' => $_W['uniacid']);
		
		$sql = 'SELECT id,thumb FROM ' . tablename('weixinmao_zp_company') .$condition ;
		
		$companylist = pdo_fetchall($sql, $params);
		
	
		if($companylist)
		{
			foreach($companylist as $k=>$v)
				{
					$companylist[$k]['thumb'] = tomedia($v['thumb']);
				}
		}

		
		$condition = ' WHERE `uniacid` = :uniacid AND status =0  ORDER BY createtime DESC LIMIT 10 ';
		$params = array(':uniacid' => $_W['uniacid']);
		
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobnote') .$condition ;
		
		$notelist = pdo_fetchall($sql, $params);
		
	   if($notelist)
		{
			foreach($notelist as $k=>$v)
				{

					if($v['avatarUrl'])
						{

							$notelist[$k]['avatarUrl'] =tomedia($v['avatarUrl']);
						}else{

							$notelist[$k]['avatarUrl'] = '../../resource/images/male'.$v['sex'].'.png';
						}
				}
		}
		
		
       $condition = ' WHERE j.status = 0 AND j.uniacid = :uniacid AND j.isrecommand = 1 AND c.cityid = :cityid AND j.endtime > '.time() ;
      
     //  $condition = ' WHERE j.uniacid = :uniacid AND j.isrecommand = 1 AND c.cityid = :cityid AND j.endtime > '.time() ;
		$params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityinfo['id']);
      //$params = array(':uniacid' => $_W['uniacid']);

		$sort =' ORDER BY j.updatetime DESC  ';
		$condition .= $sort;

        $limit  = ' LIMIT 20';

		$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money ,j.createtime AS createtime,j.updatetime AS updatetime,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special,c.address AS address ,j.education AS education,j.age AS age , j.vprice AS vprice  '  .$sql . $condition . $limit ;



		$joblist = pdo_fetchall($sql, $params);

		if($joblist)
		{
			foreach($joblist as $k=>$v)
				{
					$joblist[$k]['thumb'] =tomedia($v['thumb']);
					$joblist[$k]['special'] =explode(',',$v['special']);
					
					$joblist[$k]['createtime'] =$this->time_tran($v['updatetime']);

					if($v['vprice']>0)
						{
							$joblist[$k]['toJobDetail'] = 'toJobmoneyDetail';
						}else{

							$joblist[$k]['toJobDetail'] = 'toJobDetail';
						}

				}
		}
	
       
       	$condition = ' WHERE `weid` = :uniacid AND enabled = 1  ORDER BY displayorder DESC LIMIT 8 ';
	$params = array(':uniacid' => $_W['uniacid']);
	$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_nav') .$condition ;
	$navlist = pdo_fetchall($sql, $params);

				if($navlist)
		{
			foreach($navlist as $k=>$v)
				{
					$navlist[$k]['thumb'] =tomedia($v['thumb']);

				}
		}
		return $this->result(0, 'success', array('companylist'=>$companylist,'notelist'=>$notelist,'joblist'=>$joblist,'bannerlist'=>$bannerlist,'intro'=>$intro,'navlist'=>$navlist,'cityinfo'=>$cityinfo,'glist'=>$glist));
		
	}




public function doPageGetarticle()
		{
			global $_GPC, $_W;
			
			$pid = $_GPC['pid'];
			$category_list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_category') ." WHERE weid=:weid AND enabled=1 ",array(":weid" => $_W['uniacid']));
			
			$content  = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_content') ." WHERE  uniacid=:uniacid AND pid=".$category_list[0]['id']."  ORDER BY sort DESC",array(":uniacid" => $_W['uniacid']));
			if($content)
			{
				foreach($content as $k=>$v)
				{
					$content[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					$content[$k]['thumb'] =tomedia($v['thumb']);
					
				}
			}

            $intro = pdo_fetch("SELECT ischeck FROM " . tablename('weixinmao_zp_intro')." WHERE   uniacid=:uniacid",array(":uniacid" => $_W['uniacid']));


			$list = array('category'=>$category_list,'article'=>$content,'activeCategoryId'=>$category_list[0]['id'],'intro'=>$intro);
			return $this->result(0, 'success', $list);
			
		}




public function doPageGetsecondlist()
		{
			global $_GPC, $_W;
			
			$pid = $_GPC['pid'];
			
			$category_info = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_category') ." WHERE  weid=:weid AND id=".$pid,array(":weid" => $_W['uniacid']));
			if($category_info['parentid'] == 0)
			{
				$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_content') ." WHERE   uniacid=:weid AND pid=".$pid." ORDER BY sort DESC",array(":weid" => $_W['uniacid']));
			}else{
				
				$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_content') ." WHERE   uniacid=:weid AND sid=".$pid." ORDER BY sort DESC",array(":weid" => $_W['uniacid']));

				
				
			}
			if($list)
			{
				foreach($list as $k=>$v)
				{
					$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					$list[$k]['thumb'] = tomedia($v['thumb']);
					
				}
			}
			return $this->result(0, 'success', $list);
			
		}



public function doPageGetinitinfo()
	{
		global $_GPC, $_W;
		
		//$sql = "SELECT * FROM " . tablename('weixinmao_house_area') ." WHERE  enabled =1 AND uniacid=:uniacid ORDER BY sort ASC  ";

		
		$city = $_GPC['city'];	
		$cityinfo = pdo_get('weixinmao_zp_city',array('name'=>$city,'uniacid'=>$_W['uniacid']));
		if(!$cityinfo)
			{
				$cityinfo = pdo_get('weixinmao_zp_city',array('uniacid'=>$_W['uniacid'],'ison'=>1));
			}
					

		$arealist = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_area') ." WHERE  enabled =1 AND uniacid=:uniacid AND cityid=:cityid  ORDER BY sort ASC  ",array(":uniacid" => $_W['uniacid'],":cityid"=>$cityinfo['id']));

		$housepricelist = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_jobprice') ." WHERE  enabled =1 AND uniacid=:uniacid ORDER BY sort ASC  ",array(":uniacid" => $_W['uniacid']));

		$jobcatelist = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_jobcate') ." WHERE  enabled =1 AND uniacid=:uniacid ORDER BY sort ASC  ",array(":uniacid" => $_W['uniacid']));
$intro = pdo_get('weixinmao_zp_intro', array('uniacid' => $_W['uniacid']) );
  
  
  if($intro['ischeck'] == 1)
      {
      		$category_list = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_category') ." WHERE weid=:weid AND enabled=2 ",array(":weid" => $_W['uniacid']));
			
			$glist  = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_content') ." WHERE  uniacid=:uniacid AND pid=".$category_list['id']."  ORDER BY sort DESC",array(":uniacid" => $_W['uniacid']));
			if($glist)
			{
				foreach($glist as $k=>$v)
				{
					$glist[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					$glist[$k]['thumb'] =tomedia($v['thumb']);
					
				}
			}

      
      }
		return $this->result(0, 'success', array('arealist'=>$arealist,'housepricelist'=>$housepricelist,'jobcatelist'=>$jobcatelist,'cityinfo'=>$cityinfo,'intro'=>$intro,'glist'=>$glist));
		
		
		
	}

public function doPageGetmoneyjoblist()
	{
		global $_GPC, $_W;
		//$siteurl = $this->GetSiteUrl();
		$cityid = $_GPC['cityid'];
		$condition = ' WHERE j.vprice>0 AND j.status = 0 AND  j.uniacid = :uniacid AND c.cityid = :cityid AND j.endtime >'.time().' AND j.toptime <  '.time();
		$params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);

		if($_GPC['page']) 
			$page = $_GPC['page'];
		else 
			$page =1;
		$limit  = ' LIMIT 0,'.$page*10;
		
		if ($_GPC['houseareaid']>0) {
				$condition .= ' AND  c.areaid  = :houseareaid';
				$params[':houseareaid'] = $_GPC['houseareaid'] ;
			}
		if ($_GPC['housetype']>0) {
				$condition .= ' AND  j.worktype  = :housetype';
				$params[':housetype'] = $_GPC['housetype'] ;
			}
		if($_GPC['housepriceid']>0)
		{	$housepriceid = $_GPC['housepriceid'];
			$priceinfo = pdo_fetch("SELECT beginprice,endprice FROM " . tablename('weixinmao_zp_jobprice')." WHERE   uniacid=:uniacid AND id=:id",array(":uniacid" => $_W['uniacid'],":id"=>$housepriceid));
			if($priceinfo)
			{
				$condition .= ' AND  j.money >  '.$priceinfo['beginprice'].'  AND  j.money <= '.$priceinfo['endprice'];
			}
			
			
		}


		$sort =' ORDER BY j.sort DESC ,j.createtime DESC  ';
		$condition .= $sort;



		$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money,j.vprice AS vprice,j.createtime AS createtime ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special ,c.address AS address, j.education AS education,j.age AS age  '  .$sql .$where. $condition . $limit ;

	//	echo $sql;

		$joblist = pdo_fetchall($sql, $params);

       $condition2 = ' WHERE j.status = 0 AND  j.uniacid = :uniacid AND c.cityid = :cityid AND j.endtime >'.time().' AND j.toptime >  '.time();
		$params2 = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);

		$sort2 =' ORDER BY j.toptime DESC  ';
		$condition2 .= $sort2;

		$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

	//	$where ="  j.toptime >  ".time();

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money,j.vprice AS vprice,j.createtime AS createtime ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special ,j.toptime AS toptime ,c.address AS address, j.education AS education,j.age AS age '  .$sql . $condition2 ;



		$topjoblist = pdo_fetchall($sql, $params2);
	
	
		
        



		$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid  ORDER BY `sort` DESC';

		$jobcatelist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		if($joblist)
		{
			foreach($joblist as $k=>$v)
				{
					$joblist[$k]['thumb'] =tomedia($v['thumb']);
					$joblist[$k]['special'] =explode(',',$v['special']);
					$joblist[$k]['createtime'] =$this->time_tran($v['createtime']);

					if($v['vprice']>0)
						{
							$joblist[$k]['toJobDetail'] = 'toJobmoneyDetail';
						}else{

							$joblist[$k]['toJobDetail'] = 'toJobDetail';
						}

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}

	   	if($topjoblist)
		{
			foreach($topjoblist as $k=>$v)
				{
					$topjoblist[$k]['thumb'] =tomedia($v['thumb']);
					$topjoblist[$k]['special'] =explode(',',$v['special']);
					$topjoblist[$k]['createtime'] =$this->time_tran($v['createtime']);

					if($v['vprice']>0)
						{
							$topjoblist[$k]['toJobDetail'] = 'toJobmoneyDetail';
						}else{

							$topjoblist[$k]['toJobDetail'] = 'toJobDetail';
						}

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}


		$data = array('joblist'=>$joblist, 'jobcatelist'=>$jobcatelist,'topjoblist'=>$topjoblist);

		return $this->result(0, 'success', $data);

	}


public function doPageGetjoblist()
	{
		global $_GPC, $_W;
		//$siteurl = $this->GetSiteUrl();
		$cityid = $_GPC['cityid'];
		$condition = ' WHERE j.status = 0 AND  j.uniacid = :uniacid AND c.cityid = :cityid AND j.endtime >'.time().' AND j.toptime <  '.time();
		$params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);

		if($_GPC['page']) 
			$page = $_GPC['page'];
		else 
			$page =1;
		$limit  = ' LIMIT 0,'.$page*10;
		
		if ($_GPC['houseareaid']>0) {
				$condition .= ' AND  c.areaid  = :houseareaid';
				$params[':houseareaid'] = $_GPC['houseareaid'] ;
			}
		if ($_GPC['housetype']>0) {
				$condition .= ' AND  j.worktype  = :housetype';
				$params[':housetype'] = $_GPC['housetype'] ;
			}
		if($_GPC['housepriceid']>0)
		{	$housepriceid = $_GPC['housepriceid'];
			$priceinfo = pdo_fetch("SELECT beginprice,endprice FROM " . tablename('weixinmao_zp_jobprice')." WHERE   uniacid=:uniacid AND id=:id",array(":uniacid" => $_W['uniacid'],":id"=>$housepriceid));
			if($priceinfo)
			{
				$condition .= ' AND  j.money >  '.$priceinfo['beginprice'].'  AND  j.money <= '.$priceinfo['endprice'];
			}
			
			
		}


		$sort =' ORDER BY j.sort DESC ,j.updatetime DESC  ';
		$condition .= $sort;



		$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money,j.vprice AS vprice,j.createtime AS createtime ,j.updatetime AS updatetime ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special ,c.address AS address, j.education AS education,j.age AS age  '  .$sql .$where. $condition . $limit ;

	//	echo $sql;

		$joblist = pdo_fetchall($sql, $params);

       $condition2 = ' WHERE j.status = 0 AND  j.uniacid = :uniacid AND c.cityid = :cityid AND j.endtime >'.time().' AND j.toptime >  '.time();
		$params2 = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);

		$sort2 =' ORDER BY j.toptime DESC  ';
		$condition2 .= $sort2;

		$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

	//	$where ="  j.toptime >  ".time();

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money,j.vprice AS vprice,j.createtime AS createtime,j.updatetime AS updatetime ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special ,j.toptime AS toptime ,c.address AS address, j.education AS education,j.age AS age '  .$sql . $condition2 ;



		$topjoblist = pdo_fetchall($sql, $params2);
	
	
		
        



		$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid  ORDER BY `sort` DESC';

		$jobcatelist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		if($joblist)
		{
			foreach($joblist as $k=>$v)
				{
					$joblist[$k]['thumb'] =tomedia($v['thumb']);
					$joblist[$k]['special'] =explode(',',$v['special']);
					$joblist[$k]['createtime'] =$this->time_tran($v['updatetime']);

					if($v['vprice']>0)
						{
							$joblist[$k]['toJobDetail'] = 'toJobmoneyDetail';
						}else{

							$joblist[$k]['toJobDetail'] = 'toJobDetail';
						}

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}

	   	if($topjoblist)
		{
			foreach($topjoblist as $k=>$v)
				{
					$topjoblist[$k]['thumb'] =tomedia($v['thumb']);
					$topjoblist[$k]['special'] =explode(',',$v['special']);
					$topjoblist[$k]['createtime'] =$this->time_tran($v['updatetime']);

					if($v['vprice']>0)
						{
							$topjoblist[$k]['toJobDetail'] = 'toJobmoneyDetail';
						}else{

							$topjoblist[$k]['toJobDetail'] = 'toJobDetail';
						}

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}


		$data = array('joblist'=>$joblist, 'jobcatelist'=>$jobcatelist,'topjoblist'=>$topjoblist);

		return $this->result(0, 'success', $data);

	}
	


public function doPageGetpartjoblist()
	{
		global $_GPC, $_W;
		//$siteurl = $this->GetSiteUrl();

		$cityid = $_GPC['cityid'];
		$condition = ' WHERE j.status = 0 AND  j.uniacid = :uniacid AND c.cityid = :cityid ';
		$params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);

		if($_GPC['page']) 
			$page = $_GPC['page'];
		else 
			$page =1;
		$limit  = ' LIMIT 0,'.$page*10;
		
		if ($_GPC['houseareaid']>0) {
				$condition .= ' AND  c.areaid  = :houseareaid';
				$params[':houseareaid'] = $_GPC['houseareaid'] ;
			}
		if ($_GPC['housetype']>0) {
				$condition .= ' AND  j.worktype  = :housetype';
				$params[':housetype'] = $_GPC['housetype'] ;
			}
		if($_GPC['housepriceid']>0)
		{	$housepriceid = $_GPC['housepriceid'];
			$priceinfo = pdo_fetch("SELECT beginprice,endprice FROM " . tablename('weixinmao_zp_jobprice')." WHERE   uniacid=:uniacid AND id=:id",array(":uniacid" => $_W['uniacid'],":id"=>$housepriceid));
			if($priceinfo)
			{
				$condition .= ' AND  j.money >  '.$priceinfo['beginprice'].'  AND  j.money <= '.$priceinfo['endprice'];
			}
			
			
		}


		$sort =' ORDER BY j.sort DESC ,j.createtime DESC  ';
		$condition .= $sort;



		$sql = " FROM " . tablename('weixinmao_zp_partjob') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.jobtype AS jobtype,j.money AS money,j.createtime AS createtime ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special  '  .$sql . $condition . $limit ;

	//	echo $sql;

		$joblist = pdo_fetchall($sql, $params);
	
		

		$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid  ORDER BY `sort` DESC';

		$jobcatelist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		if($joblist)
		{
			foreach($joblist as $k=>$v)
				{
					$joblist[$k]['thumb'] =tomedia($v['thumb']);
					$joblist[$k]['special'] =explode(',',$v['special']);
					$joblist[$k]['createtime'] =$this->time_tran($v['createtime']);

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}

		$data = array('joblist'=>$joblist, 'jobcatelist'=>$jobcatelist);

		return $this->result(0, 'success', $data);

	}
	


public function doPageCompanylogin()
		{
		
			global $_GPC, $_W;
			$uid = $_GPC['uid'];
			$name = $_GPC['name'];
			$password = md5($_GPC['password']);
			/*
			$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['name'],
					'passowrd' => md5($_GPC['passowrd']),
					);
				*/
		   // $uid = $_GPC['uid'];

			$sql = 'SELECT id,companyid,status FROM ' . tablename('weixinmao_zp_companyaccount') . ' WHERE `uniacid` = :uniacid AND `name` =:name  AND `password` =:password';
			$companyinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':name'=>$name,':password'=>$password));
			
			if($companyinfo)
			{
				if($companyinfo['status'] ==1)
						{
				$list = array('msg'=>'登录成功','error'=>0,'companyid'=>$companyinfo['companyid']);
						}else{

				$list = array('msg'=>'正在审核中,请耐心等待','error'=>1);

						}

				
			}else{

				$list = array('msg'=>'登录失败','error'=>1);
			}
			return $this->result(0, 'success', $list);
			
		}



	public function doPageGetMoneyLable()
		{

			global $_GPC, $_W;
			$companyid = $_GPC['companyid'];

			$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_toplist') . ' WHERE `uniacid` = :uniacid  ORDER BY `sort` DESC';

		$moneylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
   
if($moneylist)
		{
			foreach($moneylist as $k=>$v)
				{
					$moneylist[$k]['chongzhi'] ='￥'.$v['money'];
					$moneylist[$k]['song'] =$v['title'];

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}
		$data = array('moneylist'=>$moneylist);

		return $this->result(0, 'success', $data);

		
		}

	public function doPageGetTopMoneyLable()
		{

			global $_GPC, $_W;
			$companyid = $_GPC['companyid'];

			$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_paytoplist') . ' WHERE `uniacid` = :uniacid  ORDER BY `sort` DESC';

		$moneylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
   
if($moneylist)
		{
			foreach($moneylist as $k=>$v)
				{
					$moneylist[$k]['chongzhi'] ='￥'.$v['money'];
					$moneylist[$k]['song'] =$v['title'];

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}
		$data = array('moneylist'=>$moneylist);

		return $this->result(0, 'success', $data);

		
		}


public function doPageGetagentlist()
	{
		global $_GPC, $_W;
		//$siteurl = $this->GetSiteUrl();
		
		/*
		$city = trim($_GPC['city']);
				
		$cityinfo = pdo_get('weixinmao_zp_city',array('name'=>$city,'uniacid'=>$_W['uniacid']));

				if(!$cityinfo)
				{

					$cityinfo = pdo_get('weixinmao_zp_city',array('uniacid'=>$_W['uniacid'],'ison'=>1));

				}
		*/



		//$condition = ' WHERE `uniacid` = :uniacid AND `cityid` = :cityid  AND enabled = 1 ';
		//$params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityinfo['id']);
		
       $condition = ' WHERE `uniacid` = :uniacid  AND status = 1 ';
		$params = array(':uniacid' => $_W['uniacid']);

		if($_GPC['page']) 
			$page = $_GPC['page'];
		else 
			$page =1;
		$limit  = ' LIMIT 0,'.$page*100;
		
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_agent') .$condition ;
		
		$list = pdo_fetchall($sql, $params);
		
		if($list){
				foreach($list as $k=>$v)
					{
						$userinfo = pdo_get('weixinmao_zp_userinfo',array('uniacid'=>$_W['uniacid'],'uid'=>$v['uid']));
						$list[$k]['logo'] = tomedia($userinfo['avatarUrl']);
					
					}
		}



	  $data = array('list'=>$list, 'cityinfo'=>$cityinfo);
		
		return $this->result(0, 'success', $data);
		
		
	}


public function doPageGetagentdetail()
		{
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			
			$list = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_agent') ." WHERE uniacid=:uniacid AND id=".$id,array(":uniacid" => $_W['uniacid']));

			$userinfo = pdo_get('weixinmao_zp_userinfo',array('uniacid'=>$_W['uniacid'],'uid'=>$list['uid']));
		
			$list['avatarUrl'] = $userinfo['avatarUrl'];
			
		

			$data = array('list'=>$list);

			return $this->result(0, 'success', $data);
			
		}

  

  public function doPageGetwebview()
		{
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];

			
			$list = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_nav') ." WHERE weid=:weid AND id=".$id,array(":weid" => $_W['uniacid']));

		
			
		

			$data = array('list'=>$list);

			return $this->result(0, 'success', $data);
			
		}

  
  
  
public function doPageMycustomer()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$sessionid = $_GPC['sessionid'];
		$ordertype = $_GPC['ordertype']? $_GPC['ordertype'] : 1;
        
        $condition = ' WHERE uniacid = :uniacid AND  ';
        $params = array(':uniacid' => $_W['uniacid']);


		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

         $agentinfo = pdo_get('weixinmao_zp_agent',array('uniacid'=>$_W['uniacid'],'uid'=>$uid));

/*
		if($ordertype == 1)
			{
				 $list  = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_userinfo') ." WHERE   uniacid=:weid AND agentid=".$agentinfo['id'],array(":weid" => $_W['uniacid']));


			}
		elseif($ordertype == 2)
			{
				//$condition .= " c.fid = :uid  ";
			}
*/
       $agent_setting = pdo_get('weixinmao_zp_agent_setting',array('uniacid'=>$_W['uniacid']));
  
       $data = array('agentinfo'=>$agentinfo,'agent_setting'=>$agent_setting );
		
		return $this->result(0, 'success', $data);
		
	}


public function doPagechangeagent()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$sessionid = $_GPC['sessionid'];
       
		$userinfo = pdo_get('weixinmao_zp_userinfo',array('uniacid'=>$_W['uniacid'],'uid'=>$uid));

		$agentrecord = pdo_getall('weixinmao_zp_agent_record',array('uniacid'=>$_W['uniacid'],'uid'=>$uid));
		if(count($agentrecord)>=1)
		{
             return $this->result(0, 'success',  array('msg'=>'切换次数已经达到上限','error'=>1));

		}else{

         
				$sql = 'SELECT id, name ,tel ,weixin,email FROM ' . tablename('weixinmao_zp_agent').'WHERE uniacid = '.$_W['uniacid'].' AND id <> '.$userinfo['agentid'].' ORDER BY RAND() LIMIT 1 ' ;  


				$agentinfo =  pdo_fetch($sql);

				if($agentinfo)
						{

		       					 pdo_update('weixinmao_zp_userinfo', array('agentid'=>$agentinfo['id']), array('uid' => $uid));

		       					 
						       	$data = array(
									'uniacid' => $_W['uniacid'],
									'uid' => $uid,
									'createtime'=>TIMESTAMP
									);
						 


				        	   pdo_insert('weixinmao_zp_agent_record', $data);
				               $id = pdo_insertid();


		        		}







		}

             return $this->result(0, 'success',  array('msg'=>'切换成功','error'=>0));


	}


public function doPageMyagent()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$sessionid = $_GPC['sessionid'];
		$ordertype = $_GPC['ordertype']? $_GPC['ordertype'] : 1;
        
        $condition = ' WHERE uniacid = :uniacid AND  ';
     


		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		$userinfo = pdo_get('weixinmao_zp_userinfo',array('uniacid'=>$_W['uniacid'],'uid'=>$uid));

		if($userinfo['agentid'] > 0)
			{

				$agent = pdo_get('weixinmao_zp_agent',array('uniacid'=>$_W['uniacid'],'id'=>$userinfo['agentid']));
			}


		if($userinfo['agentid'] == 0 || !$agent)
			{

		


				$sql = 'SELECT id, name ,tel ,weixin,email FROM ' . tablename('weixinmao_zp_agent').'WHERE uniacid = '.$_W['uniacid'].' ORDER BY RAND() LIMIT 1 ' ;  


				$agentinfo =  pdo_fetch($sql);

				if($agentinfo)
						{

		       					 pdo_update('weixinmao_zp_userinfo', array('agentid'=>$agentinfo['id']), array('uid' => $uid));

		        		}

				

			}else{

			  $agentinfo = pdo_get('weixinmao_zp_agent',array('uniacid'=>$_W['uniacid'],'id'=>$userinfo['agentid']));


			}

			$useragent = pdo_get('weixinmao_zp_userinfo',array('uniacid'=>$_W['uniacid'],'uid'=>$agentinfo['uid']));

			$agentinfo['avatarUrl'] = $useragent['avatarUrl'];


	  
	

 		$data = array('list'=>$agentinfo );
		
		return $this->result(0, 'success', $data);
		
	}


public function doPageGetCompanyrole()
		{

			global $_GPC, $_W;
			$companyid = $_GPC['companyid'];

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
   
if($moneylist)
		{
			foreach($moneylist as $k=>$v)
				{
					$moneylist[$k]['chongzhi'] ='￥'.$v['money'];
					$moneylist[$k]['song'] =$v['title'];

					//$houselist[$k]['areaname'] =  $areainfo[$v['houseareaid']];
					//$houselist[$k]['housetypename'] =  $housetypeinfo[$v['housetype']];
				}
			}
		$data = array('moneylist'=>$moneylist);

		return $this->result(0, 'success', $data);

		
		}

public function doPageGetlookrole()
		{

			global $_GPC, $_W;


			$uid = $_GPC['uid'];

			$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_lookrole') . ' WHERE `uniacid` = :uniacid   ORDER BY `sort` ASC';

		$moneylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
  

  
  
   
if($moneylist)
		{
			foreach($moneylist as $k=>$v)
				{
					$moneylist[$k]['chongzhi'] ='￥'.$v['money'];
					$moneylist[$k]['song'] =$v['title'];

	
				}
			}
  
     $sql = 'SELECT * FROM ' . tablename('weixinmao_zp_lookrolerecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC  ';

              $moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
  
  
  foreach($moneyrecordlist  as $k=>$v)
						{
					
					
							$moneyrecordlist[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					
						}
  
		$data = array('moneylist'=>$moneylist,'moneyrecordlist'=>$moneyrecordlist);

		return $this->result(0, 'success', $data);

		
		}



	public function doPageGetCompanyinfo()
		{

			global $_GPC, $_W;
			$companyid = $_GPC['companyid'];
			$uid = $_GPC['uid'];
			$condition = ' WHERE c.uniacid = :uniacid AND c.id = :companyid ';
			$params = array(':uniacid' => $_W['uniacid'] ,':companyid'=>$companyid);

			$companyaccount = pdo_get('weixinmao_zp_companyaccount',array('companyid'=>$companyid,'uniacid'=>$_W['uniacid']));

			if(!$companyaccount)
					{
$data = array('companyinfo'=>'','error'=>1);
			return $this->result(0, 'success', $data);

					}
			$sql = " FROM " . tablename('weixinmao_zp_company') . " AS c ";
							
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

			$sql = 'SELECT c.id AS id,a.name AS areaname ,c.companyname AS companyname,c.notenum AS notenum,c.jobnum AS jobnum,c.thumb AS thumb,c.address AS address ,c.mastername AS mastername, c.tel AS tel ,c.companycate AS companycate, c.companytype AS companytype,c.roleid AS roleid  '  .$sql . $condition ;

			$companyinfo = pdo_fetch($sql, $params);
			$companyinfo['thumb'] = tomedia($companyinfo['thumb']);

			$companyrole = pdo_get('weixinmao_zp_companyrole',array('id'=>$companyinfo['roleid'],'uniacid'=>$_W['uniacid']));
           
           	$companyinfo['rolename'] = $companyrole['title'];

			$joblist = pdo_getall('weixinmao_zp_job',array('companyid'=>$companyinfo['id'],'uniacid'=>$_W['uniacid']));
			$companyinfo['pubjobnum'] = count($joblist);



			$data = array('companyinfo'=>$companyinfo,'error'=>0);
			return $this->result(0, 'success', $data);



		}



	public function doPageMycompanyjoblist()
	{
		global $_GPC, $_W;
		$companyid = $_GPC['companyid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

	
			
		//echo $condition;
		
	    $list  = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_job') ." WHERE  uniacid=:weid AND companyid=".$companyid." ORDER BY createtime DESC",array(":weid" => $_W['uniacid']));
		
		foreach($list  as $k=>$v)
			{
				
				
				$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
				$list[$k]['endtime'] = date('Y-m-d',$v['endtime']);


				if($list[$k]['toptime']< time())
						{
                           $list[$k]['toptime'] = '已到期';

						}else{

                           $list[$k]['toptime'] =  date('Y-m-d',$v['toptime']);



						}
				
				if($v['paid']==0){
					if($v['status'] ==-1)
						$list[$k]['statusStr'] = '已取消';
					else
						$list[$k]['statusStr'] = '未支付';
					
				}else{
					if($v['status'] ==0)
					{
						$list[$k]['statusStr'] = '已付款待完成';
					}elseif($v['status']==1){
						$list[$k]['statusStr'] = '已完成';
						
					}
					
				}
				
			}
		
		return $this->result(0, 'success', $list);
		
	}
	
 
  
  	public function doPageMysendnotelist()
	{
		global $_GPC, $_W;
		$companyid = $_GPC['companyid'];
		$sessionid = $_GPC['sessionid'];
      
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		
          $condition = ' WHERE s.uniacid = :uniacid AND s.companyid = :companyid ';
		  $params = array(':uniacid' => $_W['uniacid'],':companyid' => $companyid);
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

   		 $sql = 'SELECT  s.gettime AS gettime , s.getpaytime AS getpaytime , s.id AS id,s.money AS money ,s.status AS status, s.paid AS paid,s.createtime AS createtime, s.paytime AS paytime, t.name AS cityname, c.companyname AS companyname, n.name  AS name, n.jobtitle AS jobtitle, n.tel AS tel, n.sex AS sex ,n.education AS education ,n.express AS express  '  .$sql .$where. $condition ;
         $list = pdo_fetchall($sql, $params);
      
      
		foreach($list  as $k=>$v)
			{
				
				
				$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
                $list[$k]['gettime'] = date('Y-m-d',$v['gettime']);
                $list[$k]['getpaytime'] = date('Y-m-d',$v['getpaytime']);

                if($v['status'] ==0)
					{
						$list[$k]['statusStr'] = '等待接收';
					}elseif($v['status']==1){
						$list[$k]['statusStr'] = '已接收';
						
					}
				
				
			
				
			}
		
		return $this->result(0, 'success', $list);
		
	}



	public function doPageCheckaddcompanyjob()
	{
		global $_GPC, $_W;
		$companyid = $_GPC['companyid'];
	

	
			
		//echo $condition;
		
	    $companyinfo  = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_company') ." WHERE  uniacid=:weid AND id=".$companyid,array(":weid" => $_W['uniacid']));
		
		$list = array('companyinfo'=>$companyinfo);
		
		return $this->result(0, 'success', $list);
		
	}





public function doPageMycompanypartjoblist()
	{
		global $_GPC, $_W;
		$companyid = $_GPC['companyid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

	
			
		//echo $condition;
		
	    $list  = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_partjob') ." WHERE  uniacid=:weid AND companyid=".$companyid." ORDER BY createtime DESC",array(":weid" => $_W['uniacid']));
		
		foreach($list  as $k=>$v)
			{
				
				
				$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
				
				if($v['paid']==0){
					if($v['status'] ==-1)
						$list[$k]['statusStr'] = '已取消';
					else
						$list[$k]['statusStr'] = '未支付';
					
				}else{
					if($v['status'] ==0)
					{
						$list[$k]['statusStr'] = '已付款待完成';
					}elseif($v['status']==1){
						$list[$k]['statusStr'] = '已完成';
						
					}
					
				}
				
			}
		
		return $this->result(0, 'success', $list);
		
	}




		public function doPageEditpartjobinit()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];

		$id = $_GPC['id'];
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$jobcate = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
        
        $sql = 'SELECT id,tel FROM ' . tablename('weixinmao_zp_userinfo') . ' WHERE `uniacid` = :uniacid';
		$userinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_partjob') . ' WHERE `uniacid` = :uniacid AND `id`= :id';
		$jobinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':id'=>$id));

		if($userinfo['tel'])
			{
				$isbind = 1 ;
			}else{

				$isbind = 1;
			}
		$data = array('jobcate'=>$jobcate,'isbind'=>$isbind,'jobinfo'=>$jobinfo);
		return $this->result(0, 'success', $data);
		
		
	}

	public function doPageMycompanynotelist()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$companyid = $_GPC['companyid'];
		$sessionid = $_GPC['sessionid'];
		$ordertype = $_GPC['ordertype']? $_GPC['ordertype'] : 1;
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		if($ordertype == 1)
			$condition = " paid = 0 AND status =0 AND ";
		elseif($ordertype == 2)
			$condition = " paid = 1 AND status =0 AND ";
		elseif($ordertype == 3)
			$condition = " paid = 1 AND status =1 AND ";
		elseif($ordertype == 4)
			$condition = " status =-1 AND ";
			
		//echo $condition;
		

			$condition = ' WHERE r.uniacid = :uniacid AND c.id = :companyid ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':companyid'=>$companyid);

			
			$sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";

			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobnote') . " as n ON n.uid = r.uid ";

			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_sharerecord') . " as s ON s.id = r.shareid ";


			$sql = 'SELECT r.id AS id,n.id AS noteid, n.name AS name,n.sex AS sex, n.tel AS tel ,j.jobtitle AS jobtitle ,r.createtime AS createtime,r.status AS status,r.shareid AS shareid,s.money AS money  '  .$sql . $condition ;

			$list = pdo_fetchall($sql,$params);
			if($list)
				{

					foreach($list  as $k=>$v)
						{
					
					
							$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
							if($v['shareid']>0)
							{
							$orderinfo = pdo_get('weixinmao_zp_order',array('uniacid'=>$_W['uniacid'],'pid'=>$v['id'],'type'=>'paysharenote','paid'=>1));
								if($orderinfo)
								{
									$list[$k]['paid'] = 1 ;
                                   	$orderlastinfo = pdo_get('weixinmao_zp_order',array('uniacid'=>$_W['uniacid'],'pid'=>$v['id'],'type'=>'paysharenotelast','paid'=>1));
                                    if($orderlastinfo)
                                    {
                                     $list[$k]['paidlast'] = 1 ;
                                    }else{
                                     $list[$k]['paidlast'] = 0 ;
                                    }
                                  
								}else{

									$list[$k]['paid'] = 0 ;
								}
                              
                              
                              
							}else{

                               		$list[$k]['paid'] = 0 ;
							}
					
						}

				}


	
		
		return $this->result(0, 'success', $list);
		
	}


  




	public function doPageGetjobdetail()
	{
		
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$uid = $_GPC['uid'];
			$shareid = $_GPC['shareid'];

			if($shareid >0)
			{
				$sharerecord = pdo_get('weixinmao_zp_sharerecord',array('uniacid'=>$_W['uniacid'],'id'=>$shareid));
				$view = $sharerecord['view'] +1;

				pdo_update('weixinmao_zp_sharerecord', array('view'=>$view), array('uniacid'=>$_W['uniacid'],'id'=>$shareid));


			}

			$condition = ' WHERE j.uniacid = :uniacid AND j.id = :id ';
			$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$id);

			
			$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

			$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb,c.address AS address,c.companyworker AS companyworker ,j.special AS special,j.sex AS sex,j.age AS age,j.education AS education,j.express AS express, j.jobtype AS jobtype,j.num AS num,j.content AS content,j.dmoney AS dmoney,c.mastername AS mastername, c.tel AS tel ,c.companycate AS companycate, c.companytype AS companytype ,j.companyid AS companyid,j.vprice AS vprice,j.noteprice AS noteprice '  .$sql . $condition ;

			$jobdetail = pdo_fetch($sql, $params);
			$jobdetail['special'] = explode(',',$jobdetail['special']);
		    $jobdetail['thumb'] = tomedia( $jobdetail['thumb']);
		 //   $jobdetail['content'] = html_entity_decode( $jobdetail['content'] );


		    $sql = 'SELECT id FROM ' . tablename('weixinmao_zp_jobsave') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid AND `jobid`=:jobid AND `companyid` = :companyid';
			$jobrecord = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid,':jobid'=>$jobdetail['id'],':companyid'=>$jobdetail['companyid']));
			if($jobrecord)
			{
				$savestatus = 1;

			}else{

					$savestatus = 0;
			}

           $orderinfo = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_order') ." WHERE uniacid=:uniacid AND uid=:uid AND status =1 AND pid=".$id,array(":uniacid" => $_W['uniacid'],":uid"=>$uid));
		
			if($orderinfo)
			{
				$ispay = 1;
			}else{
				
				$ispay = 0;
			}


			$sharerecordlist = pdo_getall('weixinmao_zp_sharerecord',array('uniacid'=>$_W['uniacid'],'jobid'=>$id));
			$sharenum = 0 ;
			if($sharerecordlist)
			{
				$sharenum = count($sharerecordlist);

			}

			$data = array('jobdetail'=>$jobdetail,'savestatus'=>$savestatus,'ispay'=>$ispay ,'sharenum'=>$sharenum);
			return $this->result(0, 'success', $data);
			
		
	}

		public function doPageGetpartjobdetail()
	{
		
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$uid = $_GPC['uid'];
			$condition = ' WHERE j.uniacid = :uniacid AND j.id = :id ';
			$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$id);

			
			$sql = " FROM " . tablename('weixinmao_zp_partjob') . " AS j ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

			$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb,c.address AS address,c.companyworker AS companyworker ,j.special AS special,j.sex AS sex,j.age AS age,j.education AS education,j.express AS express, j.jobtype AS jobtype,j.num AS num,j.content AS content,j.dmoney AS dmoney,c.mastername AS mastername, c.tel AS tel ,c.companycate AS companycate, c.companytype AS companytype ,j.companyid AS companyid '  .$sql . $condition ;

			$jobdetail = pdo_fetch($sql, $params);
			$jobdetail['special'] = explode(',',$jobdetail['special']);
		    $jobdetail['thumb'] = tomedia( $jobdetail['thumb']);


		    $sql = 'SELECT id FROM ' . tablename('weixinmao_zp_jobsave') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid AND `jobid`=:jobid AND `companyid` = :companyid';
			$jobrecord = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid,':jobid'=>$jobdetail['id'],':companyid'=>$jobdetail['companyid']));
			if($jobrecord)
			{
				$savestatus = 1;

			}else{

					$savestatus = 0;
			}

           $orderinfo = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_order') ." WHERE uniacid=:uniacid AND uid=:uid AND status =1 AND pid=".$id,array(":uniacid" => $_W['uniacid'],":uid"=>$uid));
		
			if($orderinfo)
			{
				$ispay = 1;
			}else{
				
				$ispay = 0;
			}


			$data = array('jobdetail'=>$jobdetail,'savestatus'=>$savestatus,'ispay'=>$ispay);
			return $this->result(0, 'success', $data);
			
		
	}
	
	public function doPageGetcompanydetail(){

				global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$condition = ' WHERE c.uniacid = :uniacid AND c.id = :id ';
			$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$id);

			
			$sql = " FROM " . tablename('weixinmao_zp_company') . " AS c ";
			
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

			$sql = 'SELECT c.id AS id,a.name AS areaname,c.content AS content ,c.companyname AS companyname,c.thumb AS thumb,c.address AS address ,c.mastername AS mastername, c.tel AS tel ,c.companycate AS companycate, c.companytype AS companytype,c.companyworker AS companyworker  '  .$sql . $condition ;

			$companydetail = pdo_fetch($sql, $params);
			$companydetail['special'] = explode(',',$companydetail['special']);
		    $companydetail['thumb'] = tomedia( $companydetail['thumb']);
		    $companydetail['content'] = html_entity_decode( $companydetail['content']);

		    $condition = ' WHERE j.uniacid = :uniacid AND  j.companyid = :companyid AND j.endtime >  '.time();
		$params = array(':uniacid' => $_W['uniacid'],':companyid'=>$id);

		$sort =' ORDER BY j.sort DESC  ';
		$condition .= $sort;

      

		$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special  '  .$sql . $condition ;



		$joblist = pdo_fetchall($sql, $params);
		if($joblist)
		{
			foreach($joblist as $k=>$v)
				{
					$joblist[$k]['thumb'] =tomedia($v['thumb']);
					$joblist[$k]['special'] =explode(',',$v['special']);

				}
		}
	


		
			$data = array('companydetail'=>$companydetail,'joblist'=>$joblist);
			return $this->result(0, 'success', $data);

	}


	public function doPagemyorderlist()
		{

					global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$sessionid = $_GPC['sessionid'];
		$ordertype = $_GPC['ordertype']? $_GPC['ordertype'] : 1;
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		if($ordertype == 1)
			$condition = "";
		elseif($ordertype == 2)
			$condition = " paid = 1 AND status =0 AND ";
		elseif($ordertype == 3)
			$condition = " paid = 1 AND status =1 AND ";
		elseif($ordertype == 4)
			$condition = " status =-1 AND ";
			
		//echo $condition;
		
	    $list  = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_order') ." WHERE  " .$condition. "  uniacid=:weid AND uid=".$uid." ORDER BY createtime DESC",array(":weid" => $_W['uniacid']));
		
		foreach($list  as $k=>$v)
			{
				
				
				$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
				
				if($v['paid']==0){
					if($v['status'] ==-1)
						$list[$k]['statusStr'] = '已取消';
					else
						$list[$k]['statusStr'] = '未支付';
					
				}else{
					if($v['status'] ==0)
					{
						$list[$k]['statusStr'] = '已付款待完成';
					}elseif($v['status']==1){
						$list[$k]['statusStr'] = '已完成';
						
					}
					
				}
				
			}
		

		$data = array('list'=>$list);


		return $this->result(0, 'success', $data);


		}

	public function doPageGetfindnotevideolist()
	{
		global $_GPC, $_W;
      	load()->func('file');
      
      /*
      $src_path = 'https://api.site100.cn/attachment/images/31/2019/03/cNjsV2gNZvzvEJgNJ3jJw2wvWZk4Vv.png';

      
      
      //创建源图的实例
      $src = imagecreatefromstring(file_get_contents($src_path));

      //list($src_w, $src_h) = getimagesize($src_img);  // 获取原图尺寸
      $info = getimagesize($src_path);
      
      //var_dump($info);exit;

      //裁剪开区域左上角的点的坐标
      $x = 100;
      $y = 100;
      //裁剪区域的宽和高
      $width = 500;
      $height = 500;
      //最终保存成图片的宽和高，和源要等比例，否则会变形
      $final_width = 500;
      $final_height =500;

      //将裁剪区域复制到新图片上，并根据源和目标的宽高进行缩放或者拉升
      $new_image = imagecreatetruecolor($final_width, $final_height);
      imagecopyresampled($new_image, $src, 0, 0, $x, $y, $final_width, $final_height, $width, $height);

      $ext = pathinfo($src_path, PATHINFO_EXTENSION);
      $rand_name = md5(mt_rand() . time()) . "." . $ext;
   
      

     //header('Content-Type: image/png'); //有头直接渲染图片;无头保存图片
    //  var_dump($new_image);
       $btn =   imagepng($new_image,'../attachment/images/'.$rand_name );
      var_dump($btn);
       //imagepng($new_image);
         imagedestroy($src);
         imagedestroy($new_image);
         echo $rand_name;
      
      exit;
      
     */
     
      

		$cityid = $_GPC['cityid'];
		$condition = ' WHERE n.uniacid = :uniacid  AND n.cityid = :cityid ';
		$params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);

		if($_GPC['page']) 
			$page = $_GPC['page'];
		else 
			$page =1;
		$limit  = ' LIMIT 0,'.$page*10;
		
		if ($_GPC['houseareaid']>0) {
				$condition .= ' AND  n.areaid  = :houseareaid';
				$params[':houseareaid'] = $_GPC['houseareaid'] ;

			}

		
		if ($_GPC['housetype']>0) {

				$education_array= array(1=>'初中', 2=>'高中', 3=>'中技', 4=>'中专', 5=>'大专', 6=>'本科', 7=>'硕士', 8=>'博士', 9=>'博后');
				$condition .= ' AND  n.education  = :housetype';

				$params[':housetype'] = $education_array[$_GPC['housetype']] ;
			}
		if($_GPC['housepriceid']>0)
		{	
			
			$condition .= ' AND  n.jobcateid  = :housepriceid';
				$params[':housepriceid'] = $_GPC['housepriceid'] ;
			
		}

		
		$sort =' ORDER BY n.refreshtime DESC  ';
		$condition .= $sort;

		//	echo $condition;

		$sql = " FROM " . tablename('weixinmao_zp_notevideo') . " AS v ";
			
      		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobnote') . " as n ON v.noteid = n.id ";

			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = n.areaid ";

		$sql = 'SELECT v.picurl AS picurl,v.imageurl AS imageurl, n.id AS id,n.jobtitle AS jobtitle,n.name AS name,n.sex AS sex ,n.education AS education ,n.express AS express ,n.birthday AS birthday ,n.avatarUrl AS avatarUrl ,n.createtime AS createtime ,n.refreshtime as refreshtime '  .$sql . $condition . $limit ;



		$worklist = pdo_fetchall($sql, $params);
	
		

		$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid  ORDER BY `sort` DESC';

		$jobcatelist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		
		if($worklist)
		{
			foreach($worklist as $k=>$v)
				{
              		
                     $imagelist = explode('@',$v['imageurl']);
              		$worklist[$k]['avatarUrl'] = tomedia($imagelist[0]);
                    /*
					if($v['avatarUrl'])
						{

							$worklist[$k]['avatarUrl'] =tomedia($v['avatarUrl']);
						}else{

							$worklist[$k]['avatarUrl'] = '../../resource/images/male'.$v['sex'].'.png';
						}
					*/
				
					$worklist[$k]['createtime'] = $this->time_tran($v['refreshtime']); 
					$worklist[$k]['age'] =  date('Y') - $worklist[$k]['birthday'];

				}
			}


		$data = array('worklist'=>$worklist, 'jobcatelist'=>$jobcatelist);

		return $this->result(0, 'success', $data);

	}
  
  
  	public function doPageGetfindworkerlist()
	{
		global $_GPC, $_W;
		//$siteurl = $this->GetSiteUrl();
		$cityid = $_GPC['cityid'];
		$condition = ' WHERE n.uniacid = :uniacid  AND n.cityid = :cityid AND n.status =0 ';
		$params = array(':uniacid' => $_W['uniacid'],':cityid'=>$cityid);

		if($_GPC['page']) 
			$page = $_GPC['page'];
		else 
			$page =1;
		$limit  = ' LIMIT 0,'.$page*10;
		
		if ($_GPC['houseareaid']>0) {
				$condition .= ' AND  n.areaid  = :houseareaid';
				$params[':houseareaid'] = $_GPC['houseareaid'] ;

			}

		
		if ($_GPC['housetype']>0) {

				$education_array= array(1=>'初中', 2=>'高中', 3=>'中技', 4=>'中专', 5=>'大专', 6=>'本科', 7=>'硕士', 8=>'博士', 9=>'博后');
				$condition .= ' AND  n.education  = :housetype';

				$params[':housetype'] = $education_array[$_GPC['housetype']] ;
			}
		if($_GPC['housepriceid']>0)
		{	
			
			$condition .= ' AND  n.jobcateid  = :housepriceid';
				$params[':housepriceid'] = $_GPC['housepriceid'] ;
			
		}

		
		$sort =' ORDER BY n.refreshtime DESC  ';
		$condition .= $sort;

		//	echo $condition;

		$sql = " FROM " . tablename('weixinmao_zp_jobnote') . " AS n ";
			
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = n.areaid ";

		$sql = 'SELECT n.id AS id,n.jobtitle AS jobtitle,n.name AS name,n.sex AS sex ,n.education AS education ,n.express AS express ,n.birthday AS birthday ,n.avatarUrl AS avatarUrl ,n.createtime AS createtime ,n.refreshtime as refreshtime '  .$sql . $condition . $limit ;



		$worklist = pdo_fetchall($sql, $params);
	
		

		$sql = 'SELECT *  FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid  ORDER BY `sort` DESC';

		$jobcatelist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));

		
		if($worklist)
		{
			foreach($worklist as $k=>$v)
				{
					if($v['avatarUrl'])
						{

							$worklist[$k]['avatarUrl'] =tomedia($v['avatarUrl']);
						}else{

							$worklist[$k]['avatarUrl'] = '../../resource/images/male'.$v['sex'].'.png';
						}
					
				
					$worklist[$k]['createtime'] = $this->time_tran($v['refreshtime']); 
					$worklist[$k]['age'] =  date('Y') - $worklist[$k]['birthday'];

				}
			}


		$data = array('worklist'=>$worklist, 'jobcatelist'=>$jobcatelist);

		return $this->result(0, 'success', $data);

	}
	
  
 
    public function doPageGetnotevideodetail()
	{
		
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$uid = $_GPC['uid'];
			$companyid = $_GPC['companyid'];

			$condition = ' WHERE n.uniacid = :uniacid AND n.id = :id ';
			$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$id);

			
		$sql = " FROM " . tablename('weixinmao_zp_jobnote') . " AS n ";
      
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_notevideo') . " as v ON v.noteid = n.id ";	
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = n.areaid ";
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobcate') . " as c ON c.id = n.jobcateid ";

		$sql = 'SELECT v.picurl AS picurl,v.imageurl AS imageurl, v.videourl AS videourl,n.id AS id,n.jobtitle AS jobtitle,n.name AS name,n.sex AS sex ,n.education AS education ,n.express AS express ,n.birthday AS birthday ,n.avatarUrl AS avatarUrl  ,n.address AS adress ,n.currentstatus AS currentstatus,n.worktype AS worktype,n.address,a.name AS areaname ,n.address AS address ,n.money AS money ,n.content AS content,c.name AS jobcatename ,n.tel AS tel'  .$sql . $condition  ;

			$workerdetail = pdo_fetch($sql, $params);
		
             $workerdetail['content'] = html_entity_decode( $workerdetail['content'] );
			$workerdetail['videourl'] = tomedia($workerdetail['videourl']);
      $workerdetail['tel'] = substr_replace($workerdetail['tel'],'****',3,4);
            $imagelist = explode('@',$workerdetail['imageurl']);

            $workerdetail['avatarUrl'] =tomedia($imagelist[0]);
      $workerdetail['image01'] =tomedia($imagelist[1]);
      $workerdetail['image02'] =tomedia($imagelist[2]);
					
					$workerdetail['age'] =  date('Y') - $workerdetail['birthday'];

			if($companyid == 0 )
				{

					$showcontact = true;
				}else{

				$lookrecord = 	pdo_get('weixinmao_zp_lookrecord',array('uniacid'=>$_W['uniacid'],'companyid'=>$companyid,'noteid'=>$id));

				if($lookrecord)
						{

							$showcontact = false;
						}else{

							$showcontact = true;
						}

				}

			if($workerdetail['areaname'] == NULL)
			{
				$workerdetail['areaname'] = '未填写';
			}
			$data = array('workerdetail'=>$workerdetail,'showcontact'=>$showcontact);
			return $this->result(0, 'success', $data);
			
		
	}



  public function doPageGetworkerdetail()
	{
		
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$uid = $_GPC['uid'];
			$companyid = $_GPC['companyid'];

			$condition = ' WHERE n.uniacid = :uniacid AND n.id = :id ';
			$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$id);

			
		$sql = " FROM " . tablename('weixinmao_zp_jobnote') . " AS n ";
			
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = n.areaid ";
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobcate') . " as c ON c.id = n.jobcateid ";

		$sql = 'SELECT n.id AS id,n.jobtitle AS jobtitle,n.name AS name,n.sex AS sex ,n.education AS education ,n.express AS express ,n.birthday AS birthday ,n.avatarUrl AS avatarUrl  ,n.address AS adress ,n.currentstatus AS currentstatus,n.worktype AS worktype,n.address,a.name AS areaname ,n.address AS address ,n.money AS money ,n.content AS content,c.name AS jobcatename ,n.tel AS tel'  .$sql . $condition  ;

			$workerdetail = pdo_fetch($sql, $params);
		
             $workerdetail['content'] = html_entity_decode( $workerdetail['content'] );

		    if($workerdetail['avatarUrl'])
						{

							$workerdetail['avatarUrl'] =tomedia($workerdetail['avatarUrl']);
						}else{

							$workerdetail['avatarUrl'] = '../../resource/images/male'.$workerdetail['sex'].'.png';
						}
					
				
					
					$workerdetail['age'] =  date('Y') - $workerdetail['birthday'];

			if($companyid == 0 )
				{

					$showcontact = true;
				}else{

				$lookrecord = 	pdo_get('weixinmao_zp_lookrecord',array('uniacid'=>$_W['uniacid'],'companyid'=>$companyid,'noteid'=>$id));

				if($lookrecord)
						{

							$showcontact = false;
						}else{

							$showcontact = true;
						}

				}

			if($workerdetail['areaname'] == NULL)
			{
				$workerdetail['areaname'] = '未填写';
			}
			$data = array('workerdetail'=>$workerdetail,'showcontact'=>$showcontact);
			return $this->result(0, 'success', $data);
			
		
	}

	public function doPageCheckLookrecord()
	{

			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$companyid = $_GPC['companyid'];

		if($companyid == 0 )
				{

					$showcontact = true;
				}else{

				$lookrecord = 	pdo_get('weixinmao_zp_lookrecord',array('uniacid'=>$_W['uniacid'],'companyid'=>$companyid,'noteid'=>$id));

				if($lookrecord)
						{

							$showcontact = false;
							$status = 0 ;

						}else{

							$companyinfo = pdo_get('weixinmao_zp_company',array('uniacid'=>$_W['uniacid'],'id'=>$companyid));
							if($companyinfo['notenum'] <=0)
							{

								$status = 1;
								$showcontact = true;

							}else{

								
                            	//$companyinfo = pdo_get('weixinmao_zp_company',array('uniacid'=>$_W['uniacid'],'id'=>$companyid));
                            	$notenum = $companyinfo['notenum'] - 1;


								$data= array('uniacid'=>$_W['uniacid'],'companyid'=>$companyid,'noteid'=>$id,'createtime'=>TIMESTAMP);
                                pdo_insert('weixinmao_zp_lookrecord', $data);
                 			    $id = pdo_insertid();

                 			    if($id)
                            	 {
                            	   pdo_update('weixinmao_zp_company', array('notenum'=>$notenum), array('id' => $companyid));

                 			    	$status = 2;
									$showcontact = false;
                 			    }

							}

						

						}

			}
			
			$data = array('showcontact'=>$showcontact,'status'=>$status);
			
			return $this->result(0, 'success', $data);

	}

   
  
  
  	public function doPageCheckLookuserrecord()
	{

			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$uid = $_GPC['uid'];



				$lookrecord = 	pdo_get('weixinmao_zp_lookuserrecord',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'noteid'=>$id));

				if($lookrecord)
						{

							$showcontact = false;
							$status = 0 ;

						}else{

							
                            $sql = 'SELECT id,money,totalmoney FROM ' . tablename('weixinmao_zp_lookrolerecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

                              $moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
                              if($moneyrecordlist)
                              {
                                  $moneyrecordinfo = $moneyrecordlist[0];
                                 $totalmoney = $moneyrecordinfo['totalmoney'];
                              }else{

                                  $totalmoney = 0;
                              }
                  
                  
							if($totalmoney <=0)
							{

								$status = 1;
								$showcontact = true;

							}else{

								
                            	$totalmoney =$totalmoney - 1;


								//$data= array('uniacid'=>$_W['uniacid'],'companyid'=>$companyid,'noteid'=>$id,'createtime'=>TIMESTAMP);
                               $moneydata = array(
                              'uniacid' => $_W['uniacid'],
                              'uid'=>$uid,
                              'createtime'=>TIMESTAMP,
                               'money'=>-1,
                              'totalmoney'=>$totalmoney,
                              'type'=>'unlooknote',
                               'mark'=>'查看简历',
                              'pid'=>0,
                              'status'=>0
                              );

                          pdo_insert('weixinmao_zp_lookrolerecord', $moneydata);
                          $id = pdo_insertid();   
                              


                 			    if($id)
                            	 {

                 			    	$status = 2;
									$showcontact = false;
                 			    }

							}

						

						}

			
			
			$data = array('showcontact'=>$showcontact,'status'=>$status);
			
			return $this->result(0, 'success', $data);

	}
  
  
	
	public function doPageGetactivelist()
		{
			global $_GPC, $_W;
		//	$siteurl = $this->GetSiteUrl();
			
			$list = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_active') ." WHERE  uniacid=:weid ORDER BY sort ASC",array(":weid" => $_W['uniacid']));
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
					$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
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
			return $this->result(0, 'success', $list);
			
		}


public function doPageGetactivedetail()
	{
		
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			/*
			$activeinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_active') ." WHERE uniacid=:uniacid AND id=".$id,array(":uniacid" => $_W['uniacid']));
			$activeinfo['content'] = html_entity_decode($activeinfo['content']);
			$activeinfo['createtime'] = date('Y-m-d',$activeinfo['createtime']);
			*/
		    
		    $condition = ' WHERE r.uniacid = :uniacid AND r.aid = :aid ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':aid'=>$id);

			
			$sql = " FROM " . tablename('weixinmao_zp_activerecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			//$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.companyid = c.id ";



			$sql = 'SELECT r.id as id, c.id as companyid, c.companyname as companyname,c.thumb as thumb '  .$sql . $condition ;

			$list = pdo_fetchall($sql,$params);

	
            $companycount = 0;
            $jobcount = 0;
            $jobrecordcount = 0;

			if($list)
			{

				$companycount = count($list);

				foreach($list as $k=>$v)
					{

					$joblist = 	pdo_getall('weixinmao_zp_job',array('uniacid'=>$_W['uniacid'],'companyid'=>$v['companyid']));

					$jobcount = $jobcount + count($joblist);
				
					$list[$k]['thumb'] = tomedia($v['thumb']);

					foreach ($joblist as $k2 => $v2) {
						$record = pdo_getall('weixinmao_zp_jobrecord',array('uniacid'=>$_W['uniacid'],'jobid'=>$v2['id']));
						$jobrecordcount = $jobrecordcount + count($record);
						$joblist[$k2]['jobcount'] = count($record);
					}

						$list[$k]['joblist'] = $joblist;
					}
			}
 



			$data = array('list'=>$list,'total'=>array('companycount'=>$companycount,'jobcount'=>$jobcount,'jobrecordcount'=>$jobrecordcount));
			
			return $this->result(0, 'success', $data);
			
		}

public function doPageGetactivedetail2()
	{
		
			global $_GPC, $_W;
			//$siteurl = $this->GetSiteUrl();
			$id = $_GPC['id'];
			$activeinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_active') ." WHERE uniacid=:uniacid AND id=".$id,array(":uniacid" => $_W['uniacid']));
			$activeinfo['content'] = html_entity_decode($activeinfo['content']);
			$activeinfo['createtime'] = date('Y-m-d',$activeinfo['createtime']);
			
			$data = array('activeinfo'=>$activeinfo);
			
			return $this->result(0, 'success', $data);
			
		}
		
	public function doPageGetnewsdetail()
		{
			global $_GPC, $_W;
			
			$id = $_GPC['id'];
			
			$list = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_content') ." WHERE uniacid=:uniacid AND id=".$id,array(":uniacid" => $_W['uniacid']));
			$list['content'] = html_entity_decode($list['content']);
			$list['createtime'] = date('Y-m-d',$list['createtime']);
			return $this->result(0, 'success', $list);
			
		}
		
  
    public function doPageMyvideonote()
    {
    	global $_GPC, $_W;
		$uid = $_GPC['uid'];
	
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_notevideo') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid';
		$notevideo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
		
      	if($notevideo)
        {
        		$notevideo['videourl'] = tomedia($notevideo['videourl']);
                $imagelist = explode('@',$notevideo['picurl']);
          		if(count($imagelist)>0)
                {
                     foreach($imagelist as $k=>$v)
                     {
                      $imagelist[$k] = tomedia($v);
                     }
                }
        }
	
		$data = array('notevideo'=>$notevideo,'imagelist'=>$imagelist);
		return $this->result(0, 'success', $data);
    
    
    
    }
  
		public function doPageGetpubinit()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$city = $_GPC['city'];	
		$cityinfo = pdo_get('weixinmao_zp_city',array('name'=>$city,'uniacid'=>$_W['uniacid']));
		if(!$cityinfo)
			{
				$cityinfo = pdo_get('weixinmao_zp_city',array('uniacid'=>$_W['uniacid'],'ison'=>1));
			}
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_area') . ' WHERE `uniacid` = :uniacid AND `cityid`=:cityid ORDER BY `sort` DESC';
		
		$arealist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':cityid'=>$cityinfo['id']));

		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$jobcate = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
        
        $sql = 'SELECT id,tel FROM ' . tablename('weixinmao_zp_userinfo') . ' WHERE `uniacid` = :uniacid';
		$userinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobnote') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid';
		$noteinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));

		if($userinfo['tel'])
			{
				$isbind = 1 ;
			}else{

				$isbind = 1;
			}
		$data = array('arealist'=>$arealist,'jobcate'=>$jobcate,'isbind'=>$isbind,'noteinfo'=>$noteinfo,'cityinfo'=>$cityinfo);
		return $this->result(0, 'success', $data);
		
		
	}


  
		public function doPageEditjobinit()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];

		$id = $_GPC['id'];
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$jobcate = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
        
        $sql = 'SELECT id,tel FROM ' . tablename('weixinmao_zp_userinfo') . ' WHERE `uniacid` = :uniacid';
		$userinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_job') . ' WHERE `uniacid` = :uniacid AND `id`= :id';
		$jobinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':id'=>$id));

		if($userinfo['tel'])
			{
				$isbind = 1 ;
			}else{

				$isbind = 1;
			}
		$data = array('jobcate'=>$jobcate,'isbind'=>$isbind,'jobinfo'=>$jobinfo);
		return $this->result(0, 'success', $data);
		
		
	}

		public function doPageEditcompanyinit()
	{
		global $_GPC, $_W;
	//	$uid = $_GPC['uid'];
$companyid = $_GPC['companyid'];
		$id = $_GPC['id'];

		

		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$jobcate = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
        
     
		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_company') . ' WHERE `uniacid` = :uniacid AND `id`= :id';
		$companyinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':id'=>$companyid));
		$companyinfo['content'] =strip_tags( $companyinfo['content']);
		$companyinfo['thumb'] = tomedia($companyinfo['thumb']);

		$areainfo = pdo_get('weixinmao_zp_area',array('id'=>$companyinfo['areaid']));

		$arealist = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_area') ." WHERE  enabled =1 AND uniacid=:uniacid AND cityid=:cityid  ORDER BY sort ASC  ",array(":uniacid" => $_W['uniacid'],":cityid"=>$areainfo['cityid']));

		if($userinfo['tel'])
			{
				$isbind = 1 ;
			}else{

				$isbind = 1;
			}
		$data = array('jobcate'=>$jobcate,'isbind'=>$isbind,'companyinfo'=>$companyinfo,'arealist'=>$arealist);
		return $this->result(0, 'success', $data);
		
		
	}





	public function doPageRegcompanyinit()
	{
		global $_GPC, $_W;

		$city = $_GPC['city'];	
		$cityinfo = pdo_get('weixinmao_zp_city',array('name'=>$city,'uniacid'=>$_W['uniacid']));
		if(!$cityinfo)
			{
				$cityinfo = pdo_get('weixinmao_zp_city',array('uniacid'=>$_W['uniacid'],'ison'=>1));
			}

		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$jobcate = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
        


		$arealist = pdo_fetchall("SELECT * FROM " . tablename('weixinmao_zp_area') ." WHERE  enabled =1 AND uniacid=:uniacid AND cityid=:cityid  ORDER BY sort ASC  ",array(":uniacid" => $_W['uniacid'],":cityid"=>$cityinfo['id']));

		
		$data = array('jobcate'=>$jobcate,'arealist'=>$arealist,'isbind'=>1,'cityinfo'=>$cityinfo);
		return $this->result(0, 'success', $data);
		
		
	}
  
   public function doPageSavenotevideo()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
        $noteinfo = pdo_get('weixinmao_zp_jobnote',array('uniacid'=>$_W['uniacid'],'uid'=>$uid));
        $noteid = $noteinfo['id'];
		$videourl = $_GPC['videourl'];
	    $imgstr = $_GPC['imgstr'];
       $content = $_GPC['content'];
     
     $piclist= explode('@',$imgstr);
     foreach($piclist as $k=>$v)
     {
     		$url = tomedia($v);
          
          $picurl = $this->FixImage($url);
       
          $imglist[] = 'images/'.$picurl;
     		
     }
     
      $data = array(
                      'uniacid' => $_W['uniacid'],
                      'uid' => $uid,
                      'noteid' => $noteid,
                      'videourl'=>$videourl,
                      'picurl'=>$imgstr,
                      'imageurl'=>implode('@',$imglist),
                      'content'=>$_GPC['content'],
                      'updatetime'=>TIMESTAMP

                      );
      $notevideo = pdo_get('weixinmao_zp_notevideo',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'noteid'=>$noteid));
     if($notevideo)
     {
		
        pdo_update('weixinmao_zp_notevideo', $data, array('id' => $notevideo['id']));
     }else{
     
       pdo_insert('weixinmao_zp_notevideo', $data);
     }
     
       $data = array('msg'=>'保存成功','error'=>0);
		return $this->result(0, 'success', $data);
		
		
	}

    public function FixImage($url)
    {
    		
      $src_path = $url;
      $src = imagecreatefromstring(file_get_contents($src_path));

      //list($src_w, $src_h) = getimagesize($src_img);  // 获取原图尺寸
      $info = getimagesize($src_path);
      
      //var_dump($info);exit;

      //裁剪开区域左上角的点的坐标
      $x = 0;
      $y = 0;
      //裁剪区域的宽和高
      $width = 600;
      $height = 600;
      //最终保存成图片的宽和高，和源要等比例，否则会变形
      $final_width = 600;
      $final_height =600;

      //将裁剪区域复制到新图片上，并根据源和目标的宽高进行缩放或者拉升
      $new_image = imagecreatetruecolor($final_width, $final_height);
      imagecopyresampled($new_image, $src, 0, 0, $x, $y, $final_width, $final_height, $width, $height);

      $ext = pathinfo($src_path, PATHINFO_EXTENSION);
      $rand_name = md5(mt_rand() . time()) . "." . $ext;
   
      

     //header('Content-Type: image/png'); //有头直接渲染图片;无头保存图片
    //  var_dump($new_image);
       $btn =   imagepng($new_image,'../attachment/images/'.$rand_name );
   //  var_dump($btn);
       //imagepng($new_image);
         imagedestroy($src);
         imagedestroy($new_image);
       //  echo $rand_name;
       return $rand_name;
    //  exit;
    		
    }


   		public function doPageSavecompanyinfo()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$companyid = $_GPC['companyid'];
	
	$data = array(
					'uniacid' => $_W['uniacid'],
					'areaid' => $_GPC['areaid'],
					'companyname' => $_GPC['companyname'],
				    'companycate'=>$_GPC['companycate'],
					'companyworker'=>$_GPC['companyworker'],
					'companytype'=>$_GPC['companytype'],
					'mastername'=>$_GPC['mastername'],
					'tel'=>$_GPC['tel'],
					'address'=>$_GPC['address'],
					'content'=>$_GPC['content']

					);
		 if($_GPC['logo']!='')
		 		{

		 			$data['thumb'] = $_GPC['logo'];
		 		}
        	pdo_update('weixinmao_zp_company', $data, array('id' => $companyid));
		return $this->result(0, 'success', array());
		
		
	}



    
    	public function doPageSaveactiverecord()
	{
		global $_GPC, $_W;
		$aid = $_GPC['aid'];
		$companyid = $_GPC['companyid'];

		$is_get = pdo_get('weixinmao_zp_activerecord',array('uniacid'=>$_W['uniacid'],'aid'=>$aid,'companyid'=>$companyid));
			if(!$is_get)
			{
	$data = array(
					'uniacid' => $_W['uniacid'],
					'aid' => $aid,
				    'companyid'=>$companyid,
					'status'=>0,
					'createtime'=>TIMESTAMP
					);
		 


        	   pdo_insert('weixinmao_zp_activerecord', $data);
          $id = pdo_insertid();
        $data = array('msg'=>'报名成功','error'=>0);

          	}else{

        $data = array('msg'=>'你已报名过','error'=>1);


          	}
		return $this->result(0, 'success', $data);
		
		
	}

public function doPageNearcompanylist(){

global $_GPC, $_W;
  
    $lng = $_GPC['longitude'];
   $lat = $_GPC['latitude'];
  	$list = pdo_getall('weixinmao_zp_company',array("uniacid" => $_W['uniacid']));
  $org= $lat.','.$lng;
  $destinations = "";
  foreach($list as $k=>$v)
  {
    $zb = $v['lat'].','.$v['lng'];
  $destinations .=$zb.'|';
  }

//  print_r($list);
 $destinations =  substr($destinations,0,strlen($destinations)-1); 
  
     $send_url = 'http://api.map.baidu.com/routematrix/v2/driving?output=json&origins='.$org.'&destinations='.$destinations.'&ak=19d66b3e48ec726044d8a5dc99aead54';
        $str =file_get_contents($send_url);
  $place = json_decode($str);

 $placelist = $place->result;

  $companyplacelist = array(array());
  $i = 0;
  foreach($placelist as $k=>$v)
  {
  	$companyplacelist[$i]['km']= $v->distance->text;
    	$companyplacelist[$i]['kmtime']= $v->distance->value;
    
    $i++;
  }
$j =0;
  foreach($list as $k=>$v)
  {
	$list[$k]['km'] = 	$companyplacelist[$j]['km'];
    
    $list[$k]['kmtime'] = 	$companyplacelist[$j]['kmtime'];
    $list[$k]['thumb'] = tomedia(   $list[$k]['thumb'] );
    $joblist = pdo_getall('weixinmao_zp_job',array('companyid'=>$v['id']));

    $list[$k]['jobcount'] = count($joblist);
    $j++;
  }

   $list =$this->my_sort($list,'kmtime',SORT_DESC,SORT_STRING); 



  	return $this->result(0, 'success', $list);
  
}



   public  function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){  
        if(is_array($arrays)){  
            foreach ($arrays as $array){  
                if(is_array($array)){  
                    $key_arrays[] = $array[$sort_key];  
                }else{  
                    return false;  
                }  
            }  
        }else{  
            return false;  
        } 
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);  
        return $arrays;  
    } 

	public function doPageAddcompanyinfo()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		//$intro = pdo_get('weixinmao_zp_intro',array('uniacid'=>$_W['uniacid']));
		$companyrole = pdo_get('weixinmao_zp_companyrole',array('uniacid'=>$_W['uniacid'],'isinit'=>1));
		$endtime = time()+60*60*24*$companyrole['days'];
	$data = array(
					'uniacid' => $_W['uniacid'],
					'cityid' => $_GPC['cityid'],
					'areaid'=>$_GPC['areaid'],
					'lat' => $_GPC['lat'], 
					'lng' => $_GPC['lng'],
					'companyname' => $_GPC['companyname'],
				    'companycate'=>$_GPC['companycate'],
					'companyworker'=>$_GPC['companyworker'],
					'companytype'=>$_GPC['companytype'],
					'mastername'=>$_GPC['mastername'],
					'tel'=>$_GPC['tel'],
					'address'=>$_GPC['address'],
					'content'=>$_GPC['content'],
					'status'=>1,
					'notenum'=>$companyrole['notenum'],
					'jobnum' =>$companyrole['jobnum'],
					'roleid'=>$companyrole['id'],
					'thumb'=>$_GPC['logo'],
					'cardimg'=>$_GPC['cardimg'],
					'endtime'=>$endtime,
					'createtime'=>TIMESTAMP
					);
		 


         pdo_insert('weixinmao_zp_company', $data);
          $id = pdo_insertid();
          $account_data= array('uniacid' => $_W['uniacid'],
          						 'name'=>$_GPC['account'],
          						 'password'=>md5($_GPC['password']),
          						 'companyid'=>$id,
          						 'createtime'=>'',
          						 'status'=>0,
          						 'createtime'=>TIMESTAMP
          						 );

                   pdo_insert('weixinmao_zp_companyaccount', $account_data);

		return $this->result(0, 'success', array());
		
		
	}



public function doPageAddjobinit()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
        $companyid = $_GPC['companyid'];

        $companyinfo = pdo_get('weixinmao_zp_company',array('uniacid'=>$_W['uniacid'],'id'=>$companyid));

		$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_jobcate') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$jobcate = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
        
        $sql = 'SELECT id,tel FROM ' . tablename('weixinmao_zp_userinfo') . ' WHERE `uniacid` = :uniacid';
		$userinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));


		if($userinfo['tel'])
			{
				$isbind = 1 ;
			}else{

				$isbind = 1;
			}
	
	$sql = 'SELECT * FROM ' . tablename('weixinmao_zp_payjoblist') . ' WHERE `uniacid` = :uniacid ORDER BY `sort` DESC';
		
		$payjoblist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
		$data = array('jobcate'=>$jobcate,'isbind'=>$isbind,'jobinfo'=>$jobinfo,'payjoblist'=>$payjoblist,'ispay'=>$ispay);
		return $this->result(0, 'success', $data);
		
		
	}


   		public function doPageSavecompanyjob()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$companyid = $_GPC['companyid'];

		$data = array(
					'uniacid' => $_W['uniacid'],
					'jobtitle' => $_GPC['jobtitle'],
				    'worktype'=>$_GPC['jobcateid'],
					'money'=>$_GPC['money'],
					'vprice'=>$_GPC['vprice'],
					'noteprice'=>$_GPC['noteprice'],
					'num'=>$_GPC['num'],
					'age'=>$_GPC['age'],
					'sex'=>$_GPC['sex'],
					'education'=>$_GPC['education'],
					'express'=>$_GPC['express'],
					'jobtype'=>$_GPC['jobtype'],
					'special'=>$_GPC['special'],
					'content'=>$_GPC['content']
					);
		 
        	pdo_update('weixinmao_zp_job', $data, array('id' => $id));

		$data = array('jobcate'=>$jobcate,'isbind'=>$isbind,'jobinfo'=>$jobinfo);
		return $this->result(0, 'success', $data);
		
		
	}


 		public function doPageSavecompanypartjob()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$companyid = $_GPC['companyid'];
$data = array(
					'uniacid' => $_W['uniacid'],
					'jobtitle' => $_GPC['jobtitle'],
				    'worktype'=>$_GPC['jobcateid'],
					'money'=>$_GPC['money'],
					'num'=>$_GPC['num'],
					'age'=>$_GPC['age'],
					'sex'=>$_GPC['sex'],
					'education'=>$_GPC['education'],
					'express'=>$_GPC['express'],
					'jobtype'=>$_GPC['jobtype'],
					'special'=>$_GPC['special'],
					'content'=>$_GPC['content'],
                    'address'=>$_GPC['address'],
         		    'workaddress'=>$_GPC['workaddress'],
          'beginjobdate'=>$_GPC['beginjobdate'],
          'endjobdate'=>$_GPC['endjobdate'],
            'beginjobtime'=>$_GPC['beginjobtime'],
          'endjobtime'=>$_GPC['endjobtime'],
					'createtime'=>TIMESTAMP
					);
		 
        	pdo_update('weixinmao_zp_partjob', $data, array('id' => $id));

		$data = array('jobcate'=>$jobcate,'isbind'=>$isbind,'jobinfo'=>$jobinfo);
		return $this->result(0, 'success', $data);
		
		
	}



	public function doPageAddcompanyjob()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$companyid = $_GPC['companyid'];
       $toplistid = $_GPC['toplistid'];
		$companyinfo = pdo_get('weixinmao_zp_company',array('id'=>$companyid,'uniacid'=>$_W['uniacid']));
		$companyrole =  pdo_get('weixinmao_zp_companyrole',array('id'=>$companyinfo['roleid'],'uniacid'=>$_W['uniacid']));
      
       $diffdays = round(($companyinfo['endtime']- time())/3600/24);
      
       $payjobinfo =  pdo_get('weixinmao_zp_payjoblist',array('id'=>$toplistid,'uniacid'=>$_W['uniacid']));

		if(($companyinfo['endtime']<time() && $companyinfo['jobnum'] > 0) || ($companyinfo['endtime']>time() && $companyinfo['jobnum'] > 0) )
					{
                  		if($payjobinfo['days']>$diffdays)
                        {
                        	$ispay = 1;
                        }else{
							$ispay = 0 ;
							$endtime = time()+60*60*24*$payjobinfo['days'];
                        }
					}else{
							$endtime =0;			
							$ispay = 1;
					}
					$data = array(
										'uniacid' => $_W['uniacid'],
										'jobtitle' => $_GPC['jobtitle'],
									    'worktype'=>$_GPC['jobcateid'],
										'money'=>$_GPC['money'],
										'vprice'=>$_GPC['vprice'],
										'noteprice'=>$_GPC['noteprice'],
										'num'=>$_GPC['num'],
										'age'=>$_GPC['age'],
										'sex'=>$_GPC['sex'],
										'education'=>$_GPC['education'],
										'express'=>$_GPC['express'],
										'jobtype'=>$_GPC['jobtype'],
										'special'=>$_GPC['special'],
										'content'=>$_GPC['content'],
										'companyid'=>$_GPC['companyid'],
										'endtime'=>$endtime,
										'createtime'=>TIMESTAMP,
                     					'updatetime'=>TIMESTAMP
										);
							 pdo_insert('weixinmao_zp_job', $data);
					          $id = pdo_insertid();

					          $jobnum = $companyinfo['jobnum'] - 1;

					        pdo_update('weixinmao_zp_company', array('jobnum'=>$jobnum), array('id' => $companyid));


					        

					        $data = array('ispay'=>$ispay,'pid'=>$id);
	

		
		return $this->result(0, 'success', $data);
		
	}


	public function doPageAddcompanypartjob()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$companyid = $_GPC['companyid'];

		$data = array(
					'uniacid' => $_W['uniacid'],
					'jobtitle' => $_GPC['jobtitle'],
				    'worktype'=>$_GPC['jobcateid'],
					'money'=>$_GPC['money'],
					'num'=>$_GPC['num'],
					'age'=>$_GPC['age'],
					'sex'=>$_GPC['sex'],
					'education'=>$_GPC['education'],
					'express'=>$_GPC['express'],
					'jobtype'=>$_GPC['jobtype'],
					'special'=>$_GPC['special'],
					'content'=>$_GPC['content'],
                    'address'=>$_GPC['address'],
         		    'workaddress'=>$_GPC['workaddress'],
					'companyid'=>$_GPC['companyid'],
          'beginjobdate'=>$_GPC['beginjobdate'],
          'endjobdate'=>$_GPC['endjobdate'],
            'beginjobtime'=>$_GPC['beginjobtime'],
          'endjobtime'=>$_GPC['endjobtime'],
					'createtime'=>TIMESTAMP
					);
		 pdo_insert('weixinmao_zp_partjob', $data);
          $id = pdo_insertid();

		$data = array('jobcate'=>$jobcate,'isbind'=>$isbind,'jobinfo'=>$jobinfo);
		return $this->result(0, 'success', $data);
		
		
	}

   
   public function doPageSendjob()
   	{
   			global $_GPC, $_W;
			$uid = $_GPC['uid'];
			$jobid = $_GPC['jobid'];
			$companyid = $_GPC['companyid'];
			$shareid = intval($_GPC['shareid']);
     
         	$sql = 'SELECT id FROM ' . tablename('weixinmao_zp_jobrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid AND `jobid`=:jobid AND `companyid` = :companyid';
			$jobrecord = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid,':jobid'=>$jobid,':companyid'=>$companyid));
     
			if($shareid >0)
			{
				$sharerecord = pdo_get('weixinmao_zp_sharerecord',array('uniacid'=>$_W['uniacid'],'id'=>$shareid));
              
                if(!$jobrecord)
                {

                  $sendnum = $sharerecord['sendnum'] +1;

                  pdo_update('weixinmao_zp_sharerecord', array('sendnum'=>$sendnum), array('uniacid'=>$_W['uniacid'],'id'=>$shareid));

                }


             $moneydatarecord = pdo_get('weixinmao_zp_moneyrecord',array('uniacid'=>$_W['uniacid'],'pid'=>$sharerecord['id'],'uid'=>$sharerecord['uid'],'type'=>'sendjob'));
             if(!$moneydatarecord)
             {

             	$sql = 'SELECT id,dtotalmoney,money,totalmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

				$moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
				if($moneyrecordlist)
				{
					$moneyrecordinfo = $moneyrecordlist[0];
					$dtotalmoney = $moneyrecordinfo['dtotalmoney'] + $sharerecord['money'];
                     $toalmoney = $moneyrecordinfo['totalmoney'];
				}else{

					$dtotalmoney = $sharerecord['money'];
                    $toalmoney = 0 ;
				}

				$moneydata = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$sharerecord['uid'],
					'createtime'=>TIMESTAMP,
					'dmoney'=>$sharerecord['money'],
					'dtotalmoney'=>$dtotalmoney,
                     'totalmoney'=>$totalmoney,
					'type'=>'sendjob',
                    'mark'=>'简历奖金',
					'pid'=>$sharerecord['id'],
					'status'=>0
					);

				pdo_insert('weixinmao_zp_moneyrecord', $moneydata);
				$id = pdo_insertid();
               
               
               	$sql = 'SELECT id,dtotalmoney,totalmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

				$moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
				if($moneyrecordlist)
				{
					$moneyrecordinfo = $moneyrecordlist[0];
					$dtotalmoney = $moneyrecordinfo['dtotalmoney'] + $sharerecord['lastmoney'];
                   $toalmoney = $moneyrecordinfo['totalmoney'];
				}else{

					$dtotalmoney = $sharerecord['lastmoney'];
                   $toalmoney = 0 ;
				}
               
               $moneydata = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$sharerecord['uid'],
					'createtime'=>TIMESTAMP,
					'dmoney'=>$sharerecord['lastmoney'],
					'dtotalmoney'=>$dtotalmoney,
                    'totalmoney'=>$totalmoney,
					'type'=>'sendjob',
                    'mark'=>'入职奖金',
					'pid'=>$sharerecord['id'],
					'status'=>0
					);

				pdo_insert('weixinmao_zp_moneyrecord', $moneydata);
				$id = pdo_insertid();

			}





			}else{

				$shareid = 0;
			}

     /*
			$intro = pdo_get('weixinmao_zp_intro', array('uniacid' => $_W['uniacid']) );
		
			if($intro['issms'] == 1)
			{
					$istel = pdo_get('weixinmao_zp_userinfo', array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
		         if(!$istel)
						{
							$list = array('msg'=>'请先认证','error'=>4);
								return $this->result(0, 'success', $list);
						}

			}
	*/


			$sql = 'SELECT id,name FROM ' . tablename('weixinmao_zp_jobnote') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ';

			$isnote = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));


			if(!$isnote)
				{
					$list = array('msg'=>'请先填写简历','error'=>3);
						return $this->result(0, 'success', $list);
				}
		
		
			if($jobrecord)
				{
						$list = array('msg'=>'已投递过','error'=>2);

				}else{

				$data = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$_GPC['uid'],
					'jobid'=>$_GPC['jobid'],
					'companyid'=>$_GPC['companyid'],
					'createtime'=>TIMESTAMP,
					'status'=>0,
					'shareid'=>$shareid
					);

		 		  pdo_insert('weixinmao_zp_jobrecord', $data);
					$id = pdo_insertid();
				    if($id)
					{

						
				      $companyid = $_GPC['companyid'];
                      
                      $intro = pdo_get('weixinmao_zp_intro',array('uniacid'=>$_W['uniacid']));
                      if($intro['issms'] == 1)//发送应聘短信给企业
                      {    $companyinfo = pdo_get('weixinmao_zp_company',array('id'=>$companyid));
                           $content = '您有新的应聘信息，请及时到企业中心查看.';
                      		$this->Sendsmsbao($companyinfo['tel'],$content);
                      }
					  $msgtpl = pdo_get('weixinmao_zp_msgtpl',array('enabled'=>1,'msgtype'=>1,'weid'=> $_W['uniacid']));

					  $msgid = $msgtpl['msgid'];

			

					  //$companyinfo = pdo_get('weixinmao_zp_company',array('id'=>$companyid));

					  $userinfo = pdo_get('weixinmao_zp_userinfo',array('companyid'=>$companyid));

					  $fans = pdo_fetch("SELECT openid FROM " . tablename('mc_mapping_fans') ." WHERE  uniacid=:weid AND uid=".$userinfo['uid'] ,array(":weid" => $_W['uniacid']));
					   $openid = $fans['openid'];



					  $jobinfo = pdo_get('weixinmao_zp_job',array('id'=>$jobid));

					  $formid = isset($_GPC['form_id']) ? trim($_GPC['form_id']) : '';
					  $data['touser'] = $openid;
					  $data['template_id'] = $msgid;
					      //  $data['page'] =  ''; //该字段不填则模板无跳转
					  $data['form_id'] = $formid;

					  $data['data'] = array(

					         'keyword1' => array('value' =>$jobinfo['jobtitle'] ),
					         'keyword2' => array('value' => $isnote['name'])
					        );
					  $data['emphasis_keyword'] = 'keyword5.DATA';
   

   
   $this->Sendmessage($openid ,$msgid,$data);


						$list = array('msg'=>'投递成功','error'=>0);
					
					}else{
						
						$list = array('msg'=>'投递失败','error'=>1);
					}
		
				}
		return $this->result(0, 'success', $list);

   	}


  
  public function doPagedealmoneyrecord()
  {
  		global $_GPC, $_W;
		$uid = $_GPC['uid'];
        $type = $_GPC['type'];
        $money = $_GPC['money'];
    	if($type == 'getmoney')
        {
        	$sql = 'SELECT id,dtotalmoney,money,totalmoney,dmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

				$moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
				$moneyrecordinfo = 	$moneyrecordlist[0];
          	
          		if($moneyrecordinfo['totalmoney'] >= $money)
                {
          
                        $totalmoney = $moneyrecordinfo['totalmoney'] - $money;
                        $moneydata = array(
                            'uniacid' => $_W['uniacid'],
                            'uid'=>$uid,
                            'createtime'=>TIMESTAMP,
                            'dmoney'=>$moneyrecordinfo['dmoney'],
                            'dtotalmoney'=>$moneyrecordinfo['dtotalmoney'],
                            'totalmoney'=>$totalmoney,
                            'money'=>-$money,
                            'type'=>$type,
                            'pid'=>0,
                            'status'=>0
                            );

                        pdo_insert('weixinmao_zp_moneyrecord', $moneydata);
                        $id = pdo_insertid();

                        if($id)
                        {
                            $list = array('msg'=>'提现成功','error'=>0);

                        }else{

                        $list = array('msg'=>'提现失败','error'=>1);
                        }
                  
                  
                }else{
                
                  $list = array('msg'=>'提现失败,提现金额大于余额','error'=>1);
                
                }
        }
    
    	return $this->result(0, 'success', $list);
  
  }
  
  
  public function doPageCheckbindcard()
  {
        global $_GPC, $_W;
		$uid = $_GPC['uid'];
        $bindcard = pdo_get('weixinmao_zp_bindcard',array('uniacid' => $_W['uniacid'],'uid'=>$uid));
    	 if($bindcard)
					{

						
						$list = array('msg'=>'已绑定提现账号','error'=>0);
					
					}else{
						
						$list = array('msg'=>'未绑定提现账号','error'=>1);
					}
    	return $this->result(0, 'success', $list);
  
  }
  
  public function doPageSavebindcard()
  {
  		global $_GPC, $_W;
		$uid = $_GPC['uid'];
     $bindcard = pdo_get('weixinmao_zp_bindcard',array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
    	 if($bindcard)
         {
           $data= array('account'=>$_GPC['account'],
                       'name'=>$_GPC['name']);
          
           
           pdo_update('weixinmao_zp_bindcard', $data, array('uid' => $uid));
           $list = array('msg'=>'更新绑定成功','error'=>0);

         }else{
          $data= array(
                        'uniacid' => $_W['uniacid'],
            		   'account'=>$_GPC['account'],
                       'name'=>$_GPC['name'],
                       'uid'=>$_GPC['uid'],
                       'createtime'=>TIMESTAMP
                      );
           
             pdo_insert('weixinmao_zp_bindcard', $data);
			 $id = pdo_insertid();
               $list = array('msg'=>'绑定成功','error'=>0);

         }
        
				
    	return $this->result(0, 'success', $list);
  
  }

  
    public function doPagegetmoneyrecord()
  {
  		global $_GPC, $_W;
		$uid = $_GPC['uid'];

        $sql = 'SELECT id,dtotalmoney,money,totalmoney,dmoney,createtime,type,mark FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC ';

		$moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
				
         $typelist = array('sendjob'=>'推荐简历待收益','getmoney'=>'提现');
        if($moneyrecordlist)
				{

					foreach($moneyrecordlist  as $k=>$v)
						{
					
					       $moneyrecordlist[$k]['type'] = $typelist[$v['type']];
							$moneyrecordlist[$k]['createtime'] = date('Y-m-d',$v['createtime']);
                           
					
						}

				}
        
    
    	return $this->result(0, 'success', $moneyrecordlist);
  
  }
  

   	public function doPageinvatejob()
   		{

   			global $_GPC, $_W;
			$uid = $_GPC['uid'];
			$id = $_GPC['id'];
			$companyid = $_GPC['companyid'];
		    pdo_update('weixinmao_zp_jobrecord', array('status'=>1,'invatetime'=>TIMESTAMP), array('id' => $id));

			$list = array('msg'=>'邀请成功','error'=>0);
					
				
		
		return $this->result(0, 'success', $list);

   		}


   	public function doPageSavejob()
   	{
   			global $_GPC, $_W;
			$uid = $_GPC['uid'];
			$jobid = $_GPC['jobid'];
			$companyid = $_GPC['companyid'];
			$sql = 'SELECT id FROM ' . tablename('weixinmao_zp_jobsave') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid AND `jobid`=:jobid AND `companyid` = :companyid';
			$jobrecord = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid,':jobid'=>$jobid,':companyid'=>$companyid));
		
			if($jobrecord)
				{
						$list = array('msg'=>'已收藏过','error'=>2);

				}else{

				$data = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$_GPC['uid'],
					'jobid'=>$_GPC['jobid'],
					'companyid'=>$_GPC['companyid'],
					'createtime'=>TIMESTAMP,
					'status'=>0
					);

		 		  pdo_insert('weixinmao_zp_jobsave', $data);
					$id = pdo_insertid();
				    if($id)
					{

						
						$list = array('msg'=>'收藏成功','error'=>0);
					
					}else{
						
						$list = array('msg'=>'收藏失败','error'=>1);
					}
		
				}
		return $this->result(0, 'success', $list);

   	}


	public function doPageSavenote()
	{
		global $_GPC, $_W;
		$uploadimagelist_str = $_GPC['uploadimagelist_str'];
	//	$imagelist = explode('@',$uploadimagelist_str);
		$uid = $_GPC['uid'];
		$cityid = $_GPC['cityid'];
		$sql = 'SELECT id FROM ' . tablename('weixinmao_zp_jobnote') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid';
		$noteinfo = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
		
		$data = array(
					'uniacid' => $_W['uniacid'],
					'cityid'=>$_GPC['cityid'],
					'uid'=>$_GPC['uid'],
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
					'content'=>$_GPC['content'],
                  'avatarUrl'=>$uploadimagelist_str
					);
		
		if($noteinfo)
			{

				pdo_update('weixinmao_zp_jobnote', $data, array('id' => $noteinfo['id']));

				$list = array('msg'=>'保存成功','error'=>0,'totalnum'=>$totalnum);


			}else{
				$data['createtime'] = $data['refreshtime']= TIMESTAMP;
			

				    pdo_insert('weixinmao_zp_jobnote', $data);
					$id = pdo_insertid();
				    if($id)
					{

						
						$list = array('msg'=>'保存成功','error'=>0,'totalnum'=>$totalnum);
					
					}else{
						
						$list = array('msg'=>'保存失败','error'=>1);
					}

			}
		return $this->result(0, 'success', $list);
		
	}

	public function doPagerefreshNotice(){

				global $_GPC, $_W;
		$uid = $_GPC['uid'];
		 $data['refreshtime']= TIMESTAMP;
			pdo_update('weixinmao_zp_jobnote', $data, array('uid' => $uid));

				$list = array('msg'=>'保存成功','error'=>0);
					return $this->result(0, 'success', $list);
		
	}


	public function doPageMyfind()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$companyid = $_GPC['companyid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		
			$condition = ' WHERE r.uniacid = :uniacid AND r.uid = :uid AND c.id>0';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':uid'=>$uid);

			
			$sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";



			$sql = 'SELECT j.jobtitle as jobtitle,j.id AS jobid, r.id AS id ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;

			$list = pdo_fetchall($sql,$params);
			if($list)
				{

					foreach($list  as $k=>$v)
						{
					
					
							$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					
						}

				}

		
		return $this->result(0, 'success', $list);
		
	}

	
	
	public function doPageMyinvaterecord()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$companyid = $_GPC['companyid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		
			$condition = ' WHERE r.uniacid = :uniacid AND r.uid = :uid AND c.id>0 ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':uid'=>$uid);

			
			$sql = " FROM " . tablename('weixinmao_zp_invaterecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				



			$sql = 'SELECT c.id AS companyid, r.id AS id ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;

			$list = pdo_fetchall($sql,$params);
			if($list)
				{

					foreach($list  as $k=>$v)
						{
					
					
							$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					
						}

				}

		
		return $this->result(0, 'success', $list);
		
	}
	
	
	
	

		public function doPageMynotice()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$companyid = $_GPC['companyid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		
			$condition = ' WHERE r.uniacid = :uniacid AND r.uid = :uid AND r.status =1 AND c.id>0  ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':uid'=>$uid);

			
			$sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";



			$sql = 'SELECT j.jobtitle as jobtitle,j.id AS jobid, r.id AS id ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;

			$list = pdo_fetchall($sql,$params);
			if($list)
				{

					foreach($list  as $k=>$v)
						{
					
					
							$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					
						}

				}

		
		return $this->result(0, 'success', $list);
		
	}

	public function doPageMysave()
		{

		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		
			$condition = ' WHERE s.uniacid = :uniacid AND s.uid = :uid AND c.id>0 ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':uid'=>$uid);

			
			$sql = " FROM " . tablename('weixinmao_zp_jobsave') . " as  s  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON s.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = s.jobid ";



			$sql = 'SELECT s.id AS id,j.jobtitle as jobtitle,j.id AS jobid, j.id AS jobid ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,j.createtime AS createtime '  .$sql . $condition ;

			$list = pdo_fetchall($sql,$params);
			if($list)
				{

					foreach($list  as $k=>$v)
						{
					
					
							$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					
						}

				}

		
		return $this->result(0, 'success', $list);

		}


	public function doPageMyshare()
		{

		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}

		
			$condition = ' WHERE s.uniacid = :uniacid AND s.uid = :uid ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':uid'=>$uid);

			
			$sql = " FROM " . tablename('weixinmao_zp_sharerecord') . " as  s  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON s.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = s.jobid ";



			$sql = 'SELECT s.id AS id,j.jobtitle as jobtitle,j.id AS jobid, j.id AS jobid ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,j.createtime AS createtime,s.view AS view, s.sendnum AS sendnum, s.usednum AS usednum,s.money AS money'  .$sql . $condition ;
			
			//$sql = 'SELECT * '  .$sql . $condition ;

			$list = pdo_fetchall($sql,$params);
			if($list)
				{

					foreach($list  as $k=>$v)
						{
					
					
							$list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					
						}

				}
			$sql = 'SELECT id,dtotalmoney,totalmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

			$moneylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));

			if($moneylist)
			{
				$moneyinfo = $moneylist[0];
			}else{

				$moneyinfo['dtotalmoney'] = 0;
				$moneyinfo['totalmoney'] = 0;
			}

			$data = array('list'=>$list,'moneyinfo'=>$moneyinfo);

		

		
		return $this->result(0, 'success', $data);

		}

      public function doPageGetmoney(){
          global $_GPC, $_W;
        		$uid = $_GPC['uid'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

			
		}
          $sql = 'SELECT id,dtotalmoney,totalmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';
          $moneylist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
          if($moneylist)
              {
                  $moneyinfo = $moneylist[0];
              }else{

                  $moneyinfo['dtotalmoney'] = 0;
                  $moneyinfo['totalmoney'] = 0;
              }
           $data = array('list'=>$list,'moneyinfo'=>$moneyinfo);

          return $this->result(0, 'success', $data);
      
      }


		public function doPageGetuserinfo(){
		
		global $_GPC, $_W;
		$sessionid = $_GPC['sessionid'];
		$uid = $_GPC['uid'];
		$companyid = $_GPC['companyid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

		}
		
		
		$userinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_userinfo') ." WHERE  uniacid=:weid AND companyid =:companyid AND uid=".$uid,array(":weid" => $_W['uniacid'],':companyid'=>$companyid));
		if($userinfo)
				{
					//return $this->result(3, '用户已绑定');
					return $this->result(0, 'success',  $userinfo);
					
				}else{
					
				
				}
		
		
	}

	public function doPageCheckusertel()
		{

			global $_GPC, $_W;
			$uid = $_GPC['uid'];
			$userinfo = pdo_get('weixinmao_zp_userinfo',array('uid'=>$uid));
	 		$isphone = false ;
		
	 		if($userinfo)
	 		{
	 			if($userinfo['tel']!='')
	 			{

	 				$isphone = true;
	 			}
			}
      
		$sql = 'SELECT id,dtotalmoney,money,totalmoney,dmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

				$moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$uid));
      			if($moneyrecordlist)
                {
                  $moneyrecordinfo = 	$moneyrecordlist[0];
                }else{
                 $moneyrecordinfo['totalmoney'] = 0;
                
                }
      
      		
      
      		$condition = ' WHERE r.uniacid = :uniacid AND r.uid = :uid AND c.id>0';
		
			$params = array(':uniacid' => $_W['uniacid'] ,':uid'=>$uid);
			
			$sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";

			$sql = 'SELECT j.jobtitle as jobtitle,j.id AS jobid, r.id AS id ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;

			$jobrecordlist = pdo_fetchall($sql,$params);
      		if($jobrecordlist)
            {
            	$countinfo['jobrecord'] = count($jobrecordlist);
            }else{
            
            	$countinfo['jobrecord'] = 0;
            }
      
      			

			
			$sql = " FROM " . tablename('weixinmao_zp_invaterecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				



			$sql = 'SELECT c.id AS companyid, r.id AS id ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;

			$invaterecordlist = pdo_fetchall($sql,$params);
      
      
      
      	if($invaterecordlist)
            {
            	$countinfo['invaterecord'] = count($invaterecordlist);
            }else{
            
            	$countinfo['invaterecord'] = 0;
            }
      
      
      	$condition = ' WHERE r.uniacid = :uniacid AND r.uid = :uid AND r.status =1 AND c.id>0  ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':uid'=>$uid);

			
			$sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";



			$sql = 'SELECT j.jobtitle as jobtitle,j.id AS jobid, r.id AS id ,c.companyname AS companyname ,c.tel AS tel,c.mastername as mastername,r.createtime AS createtime,r.status AS status '  .$sql . $condition ;

			$noticelist = pdo_fetchall($sql,$params);
      
         	if($noticelist)
            {
            	$countinfo['noticerecord'] = count($noticelist);
            }else{
            
            	$countinfo['noticerecord'] = 0;
            }
      
      
      
      
      
				$list = array('msg'=>'提交成功','error'=>0,'isphone'=>$isphone,'moneyrecordinfo'=>$moneyrecordinfo,'countinfo'=>$countinfo);
			
			
	
		return $this->result(0, 'success', $list);

		}

	public function doPageCheckagent()
		{

				global $_GPC, $_W;
			    $uid = $_GPC['uid'];
				$agentinfo = pdo_get('weixinmao_zp_agent',array('uid'=>$uid));
				if($agentinfo)
				{
				if($agentinfo['status']== 1)
					{
							$list = array('msg'=>'正常用户','error'=>0);

					}else{ 
						$list = array('msg'=>'正在审核中','error'=>1);
					}

				}else{


$list = array('msg'=>'未申请经纪人','error'=>2);
				}

		return $this->result(0, 'success', $list);



		}

  
  	public function doPageChecknote()
		{

				global $_GPC, $_W;
			    $uid = $_GPC['uid'];
				$agentinfo = pdo_get('weixinmao_zp_jobnote',array('uid'=>$uid));
				if($agentinfo)
				{
			
							$list = array('msg'=>'正常用户','error'=>0);

				

				}else{


$list = array('msg'=>'未填写简历','error'=>1);
				}

		return $this->result(0, 'success', $list);



		}


  	public function doPageUpdateuserinfo()
		{
			global $_GPC, $_W;
			$uid = $_GPC['uid'];
			$fans = pdo_get('mc_mapping_fans',array('uid'=>$uid));
			$openid = $fans['openid'];
			$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['nickname'],
					'uid' => $_GPC['uid'],
					'avatarUrl' => $_GPC['avatarUrl'],
					'openid'=>$openid,
					'createtime' => TIMESTAMP
					);
	 		
	 		$userinfo = pdo_get('weixinmao_zp_userinfo',array('uid'=>$uid));
	 		$isphone = false ;
		
	 		if($userinfo)
	 		{
	 			if($userinfo['tel']!='')
	 			{

	 				$isphone = true;
	 			}
				pdo_update('weixinmao_zp_userinfo', $data, array('uid' => $uid));
			}else{
				//$userinfo['score'] =0;
				pdo_insert('weixinmao_zp_userinfo', $data);
				$id = pdo_insertid();

			}
			
			if($id)
			{
				$list = array('msg'=>'提交成功','error'=>0,'userinfo'=>$userinfo,'isphone'=>$isphone);
			
			}else{
				
				$list = array('msg'=>'提交失败','error'=>1);
			}
	
		return $this->result(0, 'success', $list);
			
		}



   public function doPageSaveuserinfo()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
	    $companyid = $_GPC['companyid'];
		if(!$uid || $uid <=0 )
		{
			return $this->result(1, '用户未授权');
		}
		$data = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$uid,
					'companyid'=>$companyid,
					'name' => $_GPC['name'],
					'tel' => $_GPC['tel'],
					'createtime' => TIMESTAMP
					);
		
		$userinfo = pdo_fetch("SELECT id  FROM " . tablename('weixinmao_zp_userinfo') . " WHERE uid = :id", array(':id' => $uid));
		if($userinfo)
		{
			  pdo_update('weixinmao_zp_userinfo', array('uniacid' => $_W['uniacid'],
					'name' => $_GPC['name'],
					'tel' => $_GPC['tel'],
					'companyid'=>$companyid,
					), array('uid' => $uid));

			
		}else{

	    pdo_insert('weixinmao_zp_userinfo', $data);
		$id = pdo_insertid();
		}
		
		
	     $list = array('msg'=>'提交成功','error'=>0);
		
		
		return $this->result(0, 'success', $list);
	}


 public function doPageSaveregsub()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
	    $companyname = $_GPC['companyname'];
	    $date = $_GPC['date'];
	    $jobdate = $_GPC['jobdate'];
		if(!$uid || $uid <=0 )
		{
			return $this->result(1, '用户未授权');
		}
		$data = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$uid,
					'companyname'=>$companyname,
					'date'=>$date,
					'jobdate'=>$jobdate,
					'name' => $_GPC['name'],
					'tel' => $_GPC['tel'],
					'status'=>0,
					'createtime' => TIMESTAMP
					);
		
	

	    pdo_insert('weixinmao_zp_regsub', $data);
		$id = pdo_insertid();
		
		
		
	     $list = array('msg'=>'提交成功','error'=>0);
		
		
		return $this->result(0, 'success', $list);
	}

 public function doPageSaveregagent()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
	    $name = $_GPC['name'];
	    $tel = $_GPC['tel'];
	    $email = $_GPC['email'];
	  

		if(!$uid || $uid <=0 )
		{
			return $this->result(1, '用户未授权');
		}


		$is_exist = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_agent') ." WHERE  uniacid=:weid AND tel=".$tel ,array(":weid" => $_W['uniacid']));




		if($is_exist )
		{
            $list = array('msg'=>'手机号或邮箱已经存在','error'=>1);

		}else{

		$data = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$uid,
					'name' => $_GPC['name'],
					'tel' => $_GPC['tel'],
					'weixin' => $_GPC['weixin'],
					'email' => $_GPC['email'],
					'status'=>0,
					'createtime' => TIMESTAMP
					);
		
	

	    pdo_insert('weixinmao_zp_agent', $data);
		$id = pdo_insertid();
		 $list = array('msg'=>'提交成功','error'=>0);
		}
		
		
	    
		
		
		return $this->result(0, 'success', $list);
	}


 public function doPageSaveregmoney()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
	    $companyname = $_GPC['companyname'];
	
		if(!$uid || $uid <=0 )
		{
			return $this->result(1, '用户未授权');
		}
		$data = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$uid,
					'companyname'=>$companyname,
					'jobname' => $_GPC['name'],
					'jobtel' => $_GPC['tel'],
					'status'=>0,
					'createtime' => TIMESTAMP
					);
		
		

	    pdo_insert('weixinmao_zp_regmoney', $data);
		$id = pdo_insertid();
		
		
		
	     $list = array('msg'=>'提交成功','error'=>0);
		
		
		return $this->result(0, 'success', $list);
	}

   public function doPagecancleSave()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

		}

		pdo_delete('weixinmao_zp_jobsave', array('id' => $id));

		return $this->result(0, 'success', array());
		
	}

    public function doPagecancleJob()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

		}

		pdo_update('weixinmao_zp_job', array('status'=>1), array('id' => $id));

		return $this->result(0, 'success', array());
		
	}
    
  
   public function doPagesetsendnote()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
        $status = $_GPC['status'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

		}
		$getpaytime = TIMESTAMP + 60*60*24*30;//待付款时间
		pdo_update('weixinmao_zp_sendnote', array('status'=>$status,'gettime' => TIMESTAMP,'getpaytime'=>$getpaytime), array('id' => $id));

		return $this->result(0, 'success', array());
		
	}



public function doPageSearch()
	{
		global $_GPC, $_W;
		global $_GPC, $_W;
		//$siteurl = $this->GetSiteUrl();
		$condition = ' WHERE j.uniacid = :uniacid ';
		$params[':uniacid'] = $_W['uniacid'];
		if (!empty($_GPC['keyword'])) {
				$condition .= ' AND j.jobtitle LIKE :jobtitle ';
				$params[':jobtitle'] = '%' . trim($_GPC['keyword']) . '%';
			}
		
	

		$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
			
		$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

		$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money ,j.createtime AS createtime,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb ,j.special AS special  '  .$sql . $condition  ;



		$list = pdo_fetchall($sql, $params);
	
	
		if($list){
			foreach($list as $k=>$v)
				{
					$list[$k]['thumb'] =tomedia($v['thumb']);
					$list[$k]['special'] =explode(',',$v['special']);					
					$list[$k]['createtime'] =$this->time_tran($v['createtime']);
				
				}
		}
		
		

		
		
		return $this->result(0, 'success', $list);
		
	}
	
	





public function doPageSendinvatejob()
{
	global $_GPC, $_W;
	
	$id = $_GPC['id'];
    $companyid = $_GPC['companyid'];
	$jobnote =	pdo_get('weixinmao_zp_jobnote',array('id'=>$id));

    $fans = pdo_fetch("SELECT openid FROM " . tablename('mc_mapping_fans') ." WHERE  uniacid=:weid AND uid=".$jobnote['uid'],array(":weid" => $_W['uniacid']));
    $openid = $fans['openid'];
    
    $msgtpl = 	pdo_get('weixinmao_zp_msgtpl',array('enabled'=>1,'msgtype'=>2,'weid'=> $_W['uniacid']));



    $msgid = $msgtpl['msgid'];
    $companyinfo = pdo_get('weixinmao_zp_company',array('id'=>$companyid));


   $formid = isset($_GPC['form_id']) ? trim($_GPC['form_id']) : '';
   $data['touser'] = $openid;
   $data['template_id'] = $msgid;
      //  $data['page'] =  ''; //该字段不填则模板无跳转
   $data['form_id'] = $formid;
   $data['data'] = array(

         'keyword1' => array('value' =>$companyinfo['companyname'] ),
         'keyword2' => array('value' => $companyinfo['teamaddress']),
         'keyword3' => '',

        );
    $data['emphasis_keyword'] = 'keyword5.DATA';
   
   
   //$this->Sendmessage($openid ,$msgid,$data);


   $data = array(
					'uniacid' => $_W['uniacid'],
					'uid'=>$jobnote['uid'],
					'companyid'=>$companyid,
					'createtime'=>TIMESTAMP,
					'status'=>0
					);

	pdo_insert('weixinmao_zp_invaterecord', $data);
	$id = pdo_insertid();
	 if($id)
		{
       
       		 $intro = pdo_get('weixinmao_zp_intro',array('uniacid'=>$_W['uniacid']));
             if($intro['issms'] == 1)//发送短信给应聘者
                      {   
                           $content = '您有新的邀请面试信息，请及时到会员中心查看.';
                      		$this->Sendsmsbao($jobnote['tel'],$content);
                      }
       
			$list = array('msg'=>'邀请成功','error'=>0);
					
		}else{
						
			$list = array('msg'=>'邀请失败','error'=>1);
		}




   return $this->result(0, 'success',  $list);

}





 public function doPageupJob()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

		}

		pdo_update('weixinmao_zp_job', array('status'=>0), array('id' => $id));

		return $this->result(0, 'success', array());
		
	}




     public function doPageUpload()
	 {
		 
		 global $_GPC, $_W;
		 
	
		 //$filename =str_replace( array('attachment; filename=', '"',' '),'',$response['headers']['Content-disposition']);
		//$filename = 'images/'.$_W['uniacid'].'/diamondvote/'.date('Y/m/').$filename;
			load()->func('file');
			
		$log = json_encode($_FILES);
	
		$res = file_upload($_FILES['file']);
			$data = array(
					'weid' => $_W['uniacid'],
					'content'=>json_encode($res)
					);
		
	   // pdo_insert('weixinmao_house_log', $data);
		
			//file_write($filename, $response['content']);
			//file_image_thumb(ATTACHMENT_ROOT.$filename,ATTACHMENT_ROOT.$filename,$media['width']);
		 return $this->result(0, 'success', $res);
		
	 }
	 
	 public function doPagedelOrder()
	{
		global $_GPC, $_W;
		$uid = $_GPC['uid'];
		$id = $_GPC['id'];
		$sessionid = $_GPC['sessionid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权','error'=>1));

		}
		pdo_update('weixinmao_zp_order', array('status'=>-1), array('id' => $id));
		return $this->result(0, 'success', array());
		
	}


   public function doPageCheckuserinfo()
	{
		global $_GPC, $_W;
		$sessionid = $_GPC['sessionid'];
		$uid = $_GPC['uid'];
		if($sessionid !=$_W['session_id'])
		{
			return $this->result(0, 'success',  array('msg'=>'用户未授权ttttt','error'=>1));

			
		}

	
		
		
		if($uid >0)
		{
			$fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') ." WHERE  uniacid=:weid AND uid=".$uid,array(":weid" => $_W['uniacid']));
			if(!$fans){
				
				//return $this->result(1, '用户未授权');
			return $this->result(0, 'success',  array('msg'=>'用户未授权ffffff','error'=>1));

			}else{

									return $this->result(0, 'success',  array('msg'=>'用户已绑定','error'=>2));


			/*
				$userinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_userinfo') ." WHERE  uniacid=:weid AND uid=".$uid,array(":weid" => $_W['uniacid']));
				if($userinfo)
				{
					return $this->result(0, 'success',  array('msg'=>'用户已绑定','error'=>2));
					
				}else{
					
					return $this->result(0, 'success',  array('msg'=>'用户未绑定','error'=>0));
				}
			*/
				
			}
			
		}else{
			
			return $this->result(0, 'success',  array('msg'=>'用户未授权ff','error'=>1));


		}
		
		
	}


public function doPagePay() {
		global $_GPC, $_W;
	 //  $uid = $_SESSION['uid'];
	  $uid = $_GPC['uid'];
	   $pid = $_GPC['pid'];
       $ordertype = $_GPC['ordertype'];
       $companyid = 0 ;
       $toplistid = 0;
  
  
  
       if($ordertype == 'payjob')
       	{
				   $activeinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_job') ." WHERE  uniacid=:weid AND id=".$pid,array(":weid" => $_W['uniacid']));
				   if(!$activeinfo)
				   {
					   return $this->result(1, '支付失败，请重试aa');
				   }
				      $money = $activeinfo['dmoney'];
				   
				   $title = $activeinfo['jobtitle'];
	}elseif($ordertype == 'paylooknote'){

			 $companyid = $_GPC['companyid'];
			 $toplist = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_toplist') ." WHERE  uniacid=:weid AND id=".$pid,array(":weid" => $_W['uniacid']));
				   if(!$toplist)
				   {
					   return $this->result(1, '支付失败，请重试aa');
				   }
				   $money = $toplist['money'];
				   
				   $title = $toplist['title'];

	}elseif($ordertype == 'paycompanyrole'){

 			$companyid = $_GPC['companyid'];
			 $companyrole = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_companyrole') ." WHERE  uniacid=:weid AND id=".$pid,array(":weid" => $_W['uniacid']));
				   if(!$companyrole)
				   {
					   return $this->result(1, '支付失败，请重试aa');
				   }
				   $money = $companyrole['money'];
				   
				   $title = $companyrole['title'];

	}elseif($ordertype == 'paypubjob'){

			 $companyid = $_GPC['companyid'];
			 $toplistid = $_GPC['toplistid'];

			 $payjoblist = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_payjoblist') ." WHERE  uniacid=:weid AND id=".$toplistid,array(":weid" => $_W['uniacid']));
				   if(!$payjoblist)
				   {
					   return $this->result(1, '支付失败，请重试aa');
				   }
				   $money = $payjoblist['money'];
				   
				   $title = $payjoblist['title'];

	}elseif($ordertype == 'paytopjob'){

			$companyid = $_GPC['companyid'];
			$jobid = $_GPC['jobid'];
			$toplistid = $pid;
			$pid = $jobid;
			 $toplist = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_paytoplist') ." WHERE  uniacid=:weid AND id=".$toplistid,array(":weid" => $_W['uniacid']));
				   if(!$toplist)
				   {
					   return $this->result(1, '支付失败，请重试aa');
				   }
				   $money = $toplist['money'];
				   
				   $title = $toplist['title'];

	}elseif( $ordertype == 'paysharenote'){



			$condition = ' WHERE r.uniacid = :uniacid AND r.id = :id ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$pid);

			
			$sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";

			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobnote') . " as n ON n.uid = r.uid ";

			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_sharerecord') . " as s ON s.id = r.shareid ";


			$sql = 'SELECT r.id AS id,n.id AS noteid, n.name AS name,n.sex AS sex, n.tel AS tel ,j.jobtitle AS jobtitle ,r.createtime AS createtime,r.status AS status,r.shareid AS shareid,s.money AS money,c.companyname AS companyname  '  .$sql . $condition ;

			$sharerecord = pdo_fetch($sql,$params);

         
        $money = $sharerecord['money'];
				   
		$title = $sharerecord['companyname'].'于'.date('Y-m-d H:i:s').'支付赏金简历￥'.$sharerecord['money'].'元，应聘职位为'.$sharerecord['jobtitle'];

       


	}elseif( $ordertype == 'paysharenotelast'){


			$condition = ' WHERE r.uniacid = :uniacid AND r.id = :id ';
		

			$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$pid);

			
			$sql = " FROM " . tablename('weixinmao_zp_jobrecord') . " as  r  ";
			
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON r.companyid = c.id ";
				
			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_job') . " as j ON j.id = r.jobid ";

			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_jobnote') . " as n ON n.uid = r.uid ";

			$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_sharerecord') . " as s ON s.id = r.shareid ";


			$sql = 'SELECT r.id AS id,n.id AS noteid, n.name AS name,n.sex AS sex, n.tel AS tel ,j.jobtitle AS jobtitle ,r.createtime AS createtime,r.status AS status,r.shareid AS shareid,s.money AS money,s.lastmoney AS lastmoney,c.companyname AS companyname  '  .$sql . $condition ;

			$sharerecord = pdo_fetch($sql,$params);
         
        $money = $sharerecord['lastmoney'];
				   
		$title = $sharerecord['companyname'].'于'.date('Y-m-d H:i:s').'支付入职奖金￥'.$sharerecord['lastmoney'].'元，应聘职位为'.$sharerecord['jobtitle'];




	}elseif( $ordertype == 'paylookrole'){
         $pid = $_GPC['pid'];
			 $roleinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_lookrole') ." WHERE  uniacid=:weid AND id=".$pid,array(":weid" => $_W['uniacid']));
				   if(!$roleinfo)
				   {
					   return $this->result(1, '支付失败，请重试aa');
				   }
				   $money = $roleinfo['money'];
				   
				   $title = $roleinfo['title'];
         
         
       }


	   
	  $isorder = pdo_fetch("SELECT id FROM " . tablename('weixinmao_zp_order') ." WHERE  pid = :pid AND uniacid=:weid AND status =1 AND uid=".$uid,array(":weid" => $_W['uniacid'],":pid" => $pid));
	if($isorder)
	{
	//	return $this->result(1, '支付失败，请重试bb');
	}
	   
	
	  
	   $orderid = date("YmdHis"). rand(100000, 999999);
	
	   
	   $userinfo = pdo_fetch("SELECT openid FROM " . tablename('mc_mapping_fans') ." WHERE  uniacid=:weid AND uid=".$uid,array(":weid" => $_W['uniacid']));



		//构造订单信息，此处订单随机生成，业务中应该把此订单入库，支付成功后，根据此订单号更新用户是否支付成功
		$order = array(
			'tid' => $orderid,
			'user' => $userinfo['openid'],
			'fee' => $money,
			'title' => $title
		);
	
		$pay_params = $this->pay($order);
	
		$weixinmao_userinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_userinfo') ." WHERE  uniacid=:weid AND uid=".$uid,array(":weid" => $_W['uniacid']));

		$myorder = array(
			'uniacid' => $_W['uniacid'],
			'uid'=>$uid,
			'name'=>$weixinmao_userinfo['name'],
			'tel'=>$weixinmao_userinfo['tel'],
			'orderid' => $orderid,
			'money' => $money,
			'pid' => $pid,
		    'type'=> $ordertype,
			'title' => $title,
			'companyid'=>$companyid,
			'toplistid'=>$toplistid,
			'createtime'=>TIMESTAMP
		);
		//print_r($myorder);
		 pdo_insert('weixinmao_zp_order', $myorder);
		//var_dump($pay_params); 
		if (is_error($pay_params)) {
			return $this->result(1, '支付失败，请重试cc');
		}
		return $this->result(0, 'success', $pay_params);
		
		
	}



	public function doPageRepay() {
		global $_GPC, $_W;
	   $uid = $_SESSION['uid'];
	   $id = $_GPC['id'];
	   $orderinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_order') ." WHERE  uniacid=:weid AND id=".$id,array(":weid" => $_W['uniacid']));
	   if(!$orderinfo)
	   {
		   return $this->result(1, '支付失败，请重试');
	   }
	   $money = $orderinfo['money'];
	   $orderid = $orderinfo['orderid'];
	   $title = $orderinfo['title'];
	   
	   $userinfo = pdo_fetch("SELECT openid FROM " . tablename('mc_mapping_fans') ." WHERE  uniacid=:weid AND uid=".$uid,array(":weid" => $_W['uniacid']));

		//构造订单信息，此处订单随机生成，业务中应该把此订单入库，支付成功后，根据此订单号更新用户是否支付成功
		$order = array(
			'tid' => $orderid,
			'user' => $userinfo['openid'],
			'fee' => $money,
			'title'=>$title
		);
	
		$pay_params = $this->pay($order);
		
		if (is_error($pay_params)) {
			return $this->result(1, '支付失败，请重试');
		}
		return $this->result(0, 'success', $pay_params);
	}
  
  
  	public function doPageSendpay() {
		global $_GPC, $_W;
	   $uid = $_SESSION['uid'];
	   $pid = $_GPC['id'];
      $sendnote = pdo_get('weixinmao_zp_sendnote',array('uniacid'=>$_W['uniacid'],'id'=>$pid));
      $ordertype = 'sendpay';
	  $orderid = date("YmdHis"). rand(100000, 999999);
      		$weixinmao_userinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_userinfo') ." WHERE  uniacid=:weid AND uid=".$uid,array(":weid" => $_W['uniacid']));
     $title= '编号:'.time().'企业支付返费';
      $orderinfo = array(
			'uniacid' => $_W['uniacid'],
			'uid'=>$uid,
			'name'=>$weixinmao_userinfo['name'],
			'tel'=>$weixinmao_userinfo['tel'],
			'orderid' => $orderid,
			'money' => $sendnote['money'],
			'pid' => $pid,
		    'type'=> $ordertype,
			'title' => $title,
			'companyid'=> $sendnote['companyid'],
			'createtime'=>TIMESTAMP
		);
		
		 pdo_insert('weixinmao_zp_order', $orderinfo);
      
      
      
      
	   $money = $orderinfo['money'];
	   $orderid = $orderinfo['orderid'];
	   $title = $orderinfo['title'];
	   
	   $userinfo = pdo_fetch("SELECT openid FROM " . tablename('mc_mapping_fans') ." WHERE  uniacid=:weid AND uid=".$uid,array(":weid" => $_W['uniacid']));

		//构造订单信息，此处订单随机生成，业务中应该把此订单入库，支付成功后，根据此订单号更新用户是否支付成功
		$order = array(
			'tid' => $orderid,
			'user' => $userinfo['openid'],
			'fee' => $money,
			'title'=>$title
		);
	
		$pay_params = $this->pay($order);
		
		if (is_error($pay_params)) {
			return $this->result(1, '支付失败，请重试');
		}
		return $this->result(0, 'success', $pay_params);
	}
  
  
	
	
  
  
	public function payResult($pay_result) {
		global $_GPC, $_W;
		if ($pay_result['result'] == 'success') {
			//此处会处理一些支付成功的业务代码
					
			
			$tid = $pay_result['tid'];
		
		    $orderinfo =  pdo_fetch("SELECT pid ,type,companyid,toplistid,uid FROM " . tablename('weixinmao_zp_order') ." WHERE  uniacid=:weid AND orderid=".$tid,array(":weid" => $_W['uniacid']));


			
			 pdo_update('weixinmao_zp_order',array('paid' => 1,'status' => 1,'paytime'=>TIMESTAMP), array('orderid'=>$tid));
			 

			if($orderinfo['type'] == 'paylooknote')
					{


						$toplist = pdo_get('weixinmao_zp_toplist',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));

						$companyinfo = pdo_get('weixinmao_zp_company',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['companyid']));
						$notenum = $companyinfo['notenum'] + $toplist['days'];

						pdo_update('weixinmao_zp_company',array('notenum'=>$notenum), array('id'=>$orderinfo['companyid']));

					}elseif($orderinfo['type'] == 'paycompanyrole'){


						$companyrole = pdo_get('weixinmao_zp_companyrole',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));
                        $companyinfo = pdo_get('weixinmao_zp_company',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['companyid']));
                        $jobnum = $companyrole['jobnum'] + $companyinfo['jobnum'];
                        $notenum = $companyrole['notenum'] + $companyinfo['notenum'];

                        $time = 60*60*24*$companyrole['days'];

                       if($companyinfo['endtime']>time())
                       		{
                       				$endtime = $companyinfo['endtime'] + $time;
                        	}else{

                       				$endtime = $time + time();

                        	}
                       $roleid = $companyrole['id'];

                       pdo_update('weixinmao_zp_company',array('jobnum'=>$jobnum,'notenum'=>$notenum,'endtime'=>$endtime,'roleid'=>$roleid), array('id'=>$orderinfo['companyid']));

                       pdo_update('weixinmao_zp_job',array('endtime'=>$endtime), array('companyid'=>$orderinfo['companyid']));



					}elseif($orderinfo['type'] == 'paylookrole'){


						$companyrole = pdo_get('weixinmao_zp_lookrole',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));
						   $sql = 'SELECT id,money,totalmoney FROM ' . tablename('weixinmao_zp_lookrolerecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

                          $moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$orderinfo['uid']));
                          if($moneyrecordlist)
                          {
                              $moneyrecordinfo = $moneyrecordlist[0];
                            
                              $totalmoney =  $moneyrecordinfo['totalmoney'] + $companyrole['looknum'];
                          }else{

                              $dtotalmoney = $companyrole['looknum'];
                          }

                          $moneydata = array(
                              'uniacid' => $_W['uniacid'],
                              'uid'=>$sharerecord['uid'],
                              'createtime'=>TIMESTAMP,
                               'money'=> $sharerecord['money'],
                              'totalmoney'=>$totalmoney,
                              'type'=>'looknote',
                               'mark'=>'购买查看简历套餐',
                              'pid'=>0,
                              'status'=>0
                              );

                          pdo_insert('weixinmao_zp_lookrolerecord', $moneydata);
                          $id = pdo_insertid();              
              
              
              
              
              
                      //  $jobnum = $companyrole['looknum'] + $companyinfo['jobnum'];
                      

           
                     //  pdo_update('weixinmao_zp_company',array('jobnum'=>$jobnum,'notenum'=>$notenum,'endtime'=>$endtime,'roleid'=>$roleid), array('id'=>$orderinfo['companyid']));




					}elseif($orderinfo['type'] == 'paypubjob'){

	                     $toplistid = $orderinfo['toplistid'];

						 $payjoblist = pdo_get('weixinmao_zp_payjoblist',array('uniacid'=>$_W['uniacid'],'id'=>$toplistid));

						 $jobinfo = pdo_get('weixinmao_zp_job',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));                

						 if($jobinfo['endtime']>time())
	                       		{
	                       				$endtime = $jobinfo['endtime'] + 60*60*24*$payjoblist['days'];
	                        	}else{

	                       				$endtime = 60*60*24*$payjoblist['days']+time();

	                        	}


	                    pdo_update('weixinmao_zp_job',array('endtime'=>$endtime), array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));




					}elseif($orderinfo['type'] == 'paytopjob'){

						$toplist = pdo_get('weixinmao_zp_paytoplist',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['toplistid']));

						$jobinfo = pdo_get('weixinmao_zp_job',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));
						if($jobinfo['toptime'] > time())
								{

                                  
                                  $toptime = $jobinfo['toptime'] + 60*60*24*$toplist['days'];


								}else{

								  
								  $toptime = time() + 60*60*24*$toplist['days'];
								
								}

						

						pdo_update('weixinmao_zp_job',array('toptime'=>$toptime), array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));






					}elseif($orderinfo['type'] == 'paysharenote')
					{
              
                         //$uid = $orderinfo['uid'];
						//更新有效简历数
                          $jobrecord = pdo_get('weixinmao_zp_jobrecord',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));
                          $sharerecord =pdo_get('weixinmao_zp_sharerecord',array('uniacid'=>$_W['uniacid'],'id'=>$jobrecord['shareid']));
                          $usednum = $sharerecord['usednum'] +1;
                          pdo_update('weixinmao_zp_sharerecord',array('usednum'=>$usednum), array('uniacid'=>$_W['uniacid'],'id'=>$jobrecord['shareid']));
                        //分配收益
              
                         $sql = 'SELECT id,dtotalmoney,totalmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

                          $moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$sharerecord['uid']));
                          if($moneyrecordlist)
                          {
                              $moneyrecordinfo = $moneyrecordlist[0];
                              $dtotalmoney = $moneyrecordinfo['dtotalmoney'] - $sharerecord['money'];
                            
                              $totalmoney =  $moneyrecordinfo['totalmoney'] + $sharerecord['money'];
                          }else{

                              $dtotalmoney = $sharerecord['money'];
                          }

                          $moneydata = array(
                              'uniacid' => $_W['uniacid'],
                              'uid'=>$sharerecord['uid'],
                              'createtime'=>TIMESTAMP,
                              'dmoney'=>0,
                              'dtotalmoney'=>$dtotalmoney,
                               'money'=> $sharerecord['money'],
                              'totalmoney'=>$totalmoney,
                              'type'=>'sendjob',
                               'mark'=>'简历奖励',
                              'pid'=>$sharerecord['id'],
                              'status'=>0
                              );

                          pdo_insert('weixinmao_zp_moneyrecord', $moneydata);
                          $id = pdo_insertid();
              
                     
                          


					}elseif($orderinfo['type'] == 'paysharenotelast')
					{
						//更新有效简历数
                          $jobrecord = pdo_get('weixinmao_zp_jobrecord',array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));
                          $sharerecord =pdo_get('weixinmao_zp_sharerecord',array('uniacid'=>$_W['uniacid'],'id'=>$jobrecord['shareid']));
                    
                        //分配收益
                           $sql = 'SELECT id,dtotalmoney,totalmoney FROM ' . tablename('weixinmao_zp_moneyrecord') . ' WHERE `uniacid` = :uniacid AND `uid`= :uid ORDER BY createtime DESC LIMIT 1 ';

                          $moneyrecordlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'],':uid'=>$sharerecord['uid']));
                          if($moneyrecordlist)
                          {
                              $moneyrecordinfo = $moneyrecordlist[0];
                              $dtotalmoney = $moneyrecordinfo['dtotalmoney'] - $sharerecord['lastmoney'];
                            
                              $totalmoney =  $moneyrecordinfo['totalmoney'] + $sharerecord['lastmoney'];
                          }else{

                              $dtotalmoney = $sharerecord['money'];
                          }

                          $moneydata = array(
                              'uniacid' => $_W['uniacid'],
                              'uid'=>$sharerecord['uid'],
                              'createtime'=>TIMESTAMP,
                              'dmoney'=>0,
                              'dtotalmoney'=>$dtotalmoney,
                               'money'=> $sharerecord['lastmoney'],
                              'totalmoney'=>$totalmoney,
                              'type'=>'sendjob',
                               'mark'=>'入职奖励',
                              'pid'=>$sharerecord['id'],
                              'status'=>0
                              );

                          pdo_insert('weixinmao_zp_moneyrecord', $moneydata);
                          $id = pdo_insertid();


					}elseif($orderinfo['type'] == 'sendpay'){
            
                          pdo_update('weixinmao_zp_sendnote',array('paid'=>1,'paytime'=>TIMESTAMP), array('uniacid'=>$_W['uniacid'],'id'=>$orderinfo['pid']));

                    }
			
					
		}
	//	print_r($pay_result);
		return true;
	}



public function doPagegetQrcodenewhouse()
		{

			global $_GPC, $_W;
			
				load()->func('file');
 					
				$id = $_GPC['id'];
				$uid = $_GPC['uid'];
          		
				$houseinfo = pdo_fetch("SELECT * FROM " . tablename('weixinmao_zp_job') ." WHERE  uniacid=:weid AND id=".$id,array(":weid" => $_W['uniacid']));
				
				$condition = ' WHERE j.uniacid = :uniacid AND j.id = :id ';
				$params = array(':uniacid' => $_W['uniacid'] ,':id'=>$id);

			
				$sql = " FROM " . tablename('weixinmao_zp_job') . " AS j ";
			
				$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_company') . " as c ON j.companyid = c.id ";
				
				$sql .= "  LEFT JOIN  " . tablename('weixinmao_zp_area') . " as a ON a.id = c.areaid ";

				$sql = 'SELECT j.id AS id,j.jobtitle AS title ,j.money AS money ,a.name AS areaname ,c.companyname AS companyname,c.thumb AS thumb,c.address AS address,c.companyworker AS companyworker ,j.special AS special,j.sex AS sex,j.age AS age,j.education AS education,j.express AS express, j.jobtype AS jobtype,j.num AS num,j.content AS content,j.dmoney AS dmoney,c.mastername AS mastername, c.tel AS tel ,c.companycate AS companycate, c.companytype AS companytype ,j.companyid AS companyid,j.vprice AS vprice,j.noteprice AS noteprice '  .$sql . $condition ;



				$jobdetail = pdo_fetch($sql, $params);


				$userinfo = pdo_get('weixinmao_zp_userinfo',array('uid'=>$uid));

				$sharerecord = pdo_get('weixinmao_zp_sharerecord',array('uniacid'=>$_W['uniacid'],'jobid'=>$id,'uid'=>$uid));
				if(!$sharerecord)
				{
					
					$sharedata = array(
	                    'uniacid' => $_W['uniacid'],
						'uid'=>$uid,
	                    'jobid'=>$id,
	                    'companyid'=>$jobdetail['companyid'],
	                    'money'=>$jobdetail['noteprice'],
                       'lastmoney'=>$jobdetail['vprice'],
	                    'view'=>0,
	                    'sendnum'=>0,
	                    'usednum'=>0,
	                    'createtime' => TIMESTAMP,
                		);

					pdo_insert('weixinmao_zp_sharerecord', $sharedata);

					$shareid = pdo_insertid();

               

				}else{

					$shareid = $sharerecord['id'];
				}






				$appid = $_W['uniaccount']['key'];
				$secret = $_W['uniaccount']['secret'];
				$tokenUrl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
				$result=$this->api_notice_increment($tokenUrl);
				$tokenArr = json_decode($result);  				
   				$access_token=$tokenArr->access_token;
				
			
			    
			    $width=430;
				$data = array();
       			$data['scene'] = $id."@".$uid."@".$shareid;
        		$data['page'] = "weixinmao_zp/pages/jobnewdetail/index";
        		// $data['is_hyaline'] = true;
        		$post_data = json_encode($data);
      
			    $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
				$result=$this->api_notice_increment($url,$post_data,'POST');
				



				$uniacid = intval($_W['uniacid']);
				$path = "images/{$uniacid}/" . date('Y/m/');
				mkdirs(ATTACHMENT_ROOT . '/' . $path);
				$filename = file_random_name(ATTACHMENT_ROOT . '/' . $path, 'jpg');
				$filepath = $path . $filename;
					
				file_put_contents('../attachment/'.$filepath, $result);
				




				$data =  array('myqrcode'=>tomedia($filepath),'houseinfo'=>$jobdetail,'intro'=>$intro,'userinfo'=>$userinfo,'shareid'=>$shareid);
				return $this->result(0, 'success', $data);


		}




public function api_notice_increment($url, $data="",$method='GET'){
	error_reporting(0);
    $ch = curl_init();
    $header = "Accept-Charset: utf-8";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
   
    if (curl_errno($ch)) {
      return false;
    }else{
      return $tmpInfo;
    }
  }



public function Sendmessage($openid,$msgid,$data)
	{
		global $_GPC, $_W;
		
		
		
		$appid = $_W['uniaccount']['key'];
		$appsecret = $_W['uniaccount']['secret'];
        $access_token = '';

       // $openid = isset($_POST['openid']) ? trim($_POST['openid']) : ''; //小程序的openid
        
       
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
       // echo $appid;

       $this-> generate_token($access_token, $appid, $appsecret);


      
        $send_url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
        $str =$this->request($send_url, 'post', $data);
        $json = json_decode($this->request($send_url, 'post', $data));
        if(!$json){
            $ret['errMsg'] = $str;
            //exit(json_encode($ret));
        }else if(isset($json->errcode) && $json->errcode){
            $ret['errMsg'] = $json->errcode.', '.$json->errmsg;
          //  exit(json_encode($ret));
        }
        $ret['resultCode'] = 0;
   


	
		return true;
	}

function generate_token(&$access_token, $appid, $appsecret){
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

function new_access_token(&$access_token, $token_file, $appid, $appsecret){
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



public function time_tran($time){
      $t=time()-$time;
    $f=array(
        '31536000'=>'年',
        '2592000'=>'个月',
        '604800'=>'星期',
        '86400'=>'天',
        '3600'=>'小时',
        '60'=>'分钟',
        '1'=>'秒'
    );
    foreach ($f as $k=>$v)    {
        if (0 !=$c=floor($t/(int)$k)) {
            return $c.$v.'前';
        }
    }
}








}