<?php
/*
 |--------------------------------------------------------------------------
 | SkyCaiji (蓝天采集器)
 |--------------------------------------------------------------------------
 | Copyright (c) 2018 https://www.skycaiji.com All rights reserved.
 |--------------------------------------------------------------------------
 | 使用协议  https://www.skycaiji.com/licenses
 |--------------------------------------------------------------------------
 */

namespace skycaiji\install\controller;
use skycaiji\common\controller\BaseController;
use think\Db;
use skycaiji\install\event\UpgradeDb;
use think\Config;
use skycaiji\common\model\User;
class Index extends BaseController{
	public function __construct(){
		parent::__construct();
		if(session_status()!==PHP_SESSION_ACTIVE){
			session_start();
		}
		
		$lockFile=config('root_path').'/data/install.lock';
		$lockFileOld=config('app_path').'/install/data/install.lock';
		$hasLockFile=null;
		if(file_exists($lockFile)){
		    
		    $hasLockFile=$lockFile;
		}elseif(file_exists($lockFileOld)){
		    $hasLockFile=$lockFileOld;
		}
		
		if($hasLockFile){
		    
		    $this->success('程序已安装，如需重新安装请删除文件：'.realpath($hasLockFile),'admin/index/index',[],10);
		}
	}
	public function indexAction(){
		return $this->fetch();
	}
	
	/*环境检测*/
	public function step1Action(){
	    try{
	        \util\Tools::clear_runtime_dir();
	    }catch(\Exception $ex){
	        
	    }
		
		$LocSystem=new \skycaiji\install\event\LocSystem();
		$setting=$LocSystem->environment();
		
		$this->assign('serverDataList',$setting['server']);
		$this->assign('phpModuleList',$setting['php']);
		$this->assign('pathFileList',$setting['path']);
		return $this->fetch();
	}
	/*数据安装表单*/
	public function step2Action(){
		if(request()->isPost()){
			
			$db_config=array(
				'db_host'=>input('db_host'),
				'db_port'=>input('db_port'),
				'db_name'=>input('db_name'),
				'db_user'=>input('db_user'),
				'db_prefix'=>trim(input('db_prefix'),'_')
			);
			foreach ($db_config as $k=>$v){
				if(empty($v)){
					$this->error(lang('empty_db',array('type'=>lang($k))));
				}
			}
			$db_config['db_type']='mysql';
			$db_config['db_pwd']=input('db_pwd');
			$db_config['db_prefix'].='_';
			
			
			$adminUser=array(
				'user_name'=>input('user_name'),
				'user_pwd'=>input('user_pwd'),
				'user_repwd'=>input('user_repwd'),
				'user_email'=>input('user_email')
			);
			if(empty($adminUser['user_name'])){
				$this->error('请输入创始人用户名');
			}
			$check=User::right_username($adminUser['user_name']);
			if(!$check['success']){
				$this->error('创始人'.$check['msg']);
			}
			if(empty($adminUser['user_pwd'])){
				$this->error('请输入创始人密码');
			}
			$check=User::right_pwd($adminUser['user_pwd']);
			if(!$check['success']){
				$this->error('创始人'.$check['msg']);
			}
			$check=User::right_repwd($adminUser['user_pwd'],$adminUser['user_repwd']);
			if(!$check['success']){
			    $this->error('创始人'.$check['msg']);
			}
			if(empty($adminUser['user_email'])){
				$this->error('请输入创始人邮箱');
			}
			$check=User::right_email($adminUser['user_email']);
			if(!$check['success']){
			    $this->error('创始人'.$check['msg']);
			}
	
			try {
				$dbConfig=$this->_get_db_config($db_config);
				$dbTables=Db::connect($dbConfig)->getTables();
			}catch(\Exception $ex){
				if(!empty($dbTables)){
					$this->error($ex->getMessage());
				}
			}
			$has_data = false; 
			
			if (! empty ( $dbTables )) {
				foreach ( $dbTables as $dbTable ) {
					if (stripos ( $dbTable, $db_config ['db_prefix'] ) === 0) {
						
						$has_data = true;
						break;
					}
				}
			}
			
			session('install_config',array('db'=>$db_config,'admin'=>$adminUser));
			
			$this->success('',null,array('has_data'=>$has_data));
		}else{
			return $this->fetch();
		}
	}
	/*执行数据安装*/
	public function step3Action(){
		echo $this->fetch();
		 
		$installConfig=session('install_config');
		if(empty($installConfig)){
			$this->error('请先安装数据','install/index/step2');
		}
		 
		$dbConfig=$installConfig['db'];
		 
		$installDataPath=config('app_path').'/install/data';
		 
		$sqlFile=$installDataPath.'/install_table';
		 
		if(!file_exists($sqlFile)){
			$this->error('sql安装文件不存在');
		}
		 
		$installSql=file_get_contents($sqlFile);
		
		
		 
		$installSql=preg_replace('/\s+`skycaiji_/i', ' `'.$dbConfig['db_prefix'], $installSql);
		 
		if(preg_match_all('/[\s\S]+?\;[\r\n]/',$installSql,$sqlList)){
			$sqlList=$sqlList[0];
		}else{
			$this->error('没有sql安装语句');
		}
		$msgError=false;
		
		try {
			$this->_html_js('正在安装...');
			$dbName=$dbConfig['db_name'];
			unset($dbConfig['db_name']);
			Config::set('database',$this->_get_db_config($dbConfig));
			$dbConn=Db::connect();
			$dbConn->execute('create database if not exists '.$dbName.' default character set utf8');
			$dbConn->execute('use '.$dbName);
			
			foreach($sqlList as $sql){
				$dbConn->execute($sql);
				$msg='';
				if(preg_match('/^\s*create\s+table\s+`'.$dbConfig['db_prefix'].'(?P<table>[^\s]+?)`/i',$sql,$tableName)){
					$msg=$dbConfig['db_prefix'].$tableName['table'].' 表创建成功！';
				}
				if($msg){
					$this->_html_js($msg);
				}
			}
			
			$createConfig=file_get_contents($installDataPath.'/config.php');
			foreach ($installConfig['db'] as $k=>$v){
				$createConfig=str_replace('{$'.strtoupper($k).'}', $v, $createConfig);
			}
			$createConfig=preg_replace('/\{\$db_([^\s]+?)\}/i', '', $createConfig);
			if(empty($createConfig)){
				$msgError='配置文件不能为空';
			}else{
				if(write_dir_file(config('root_path').'/data/config.php', $createConfig)===false){
					$msgError='配置文件创建失败';
				}else{
					$this->_html_js('配置文件创建成功！');
					
					
					$founderGid=$dbConn->table($dbConfig['db_prefix'].'usergroup')->where('founder',1)->value('id');
					
					$userSalt=User::rand_salt();
					$dbConn->table($dbConfig['db_prefix'].'user')->insert(array(
							'username'=>$installConfig['admin']['user_name'],
							'password'=>User::pwd_encrypt($installConfig['admin']['user_pwd'],$userSalt),
							'salt'=>$userSalt,
							'groupid'=>$founderGid,
							'email'=>$installConfig['admin']['user_email'],
							'regtime'=>time()
					));
					$this->_html_js('创始人账号'.$installConfig['admin']['user_name'].'添加成功！');
					
					$upgradeDb=new UpgradeDb();
					$upgradeResult=$upgradeDb->upgrade();
					if(!$upgradeResult['success']){
						$this->_html_js('数据库升级失败');
					}
			
					$this->_html_js('安装完成！');
			
					write_dir_file(config('root_path').'/data/install.lock', '1');
			
					$this->_html_js('<a href="'.url('admin/index/index').'" class="btn btn-lg btn-success">开始使用</a>');
					
					
					$LocSystem=new \skycaiji\install\event\LocSystem();
					$phpGdIsAllowed=$LocSystem->phpModuleIsAllowed('gd');
					if(empty($phpGdIsAllowed)){
					    
					    $mconfig=new \skycaiji\common\model\Config();
					    $mconfig->setVerifycode(false);
					}
				}
			}
		}catch (\Exception $ex){
			$this->error($ex->getMessage(),null,null,10);
		}
		if($msgError){
			$this->error($msgError);
		}
	}
	
	public function _html_js($msg){
		echo '<script type="text/javascript">echo_msg("'.addslashes($msg).'");</script>';
		ob_flush();
		flush();
	}
	public function _get_db_config($config){
		$dbConfig=array(
			'type'=>$config['db_type'],
			'hostname'=>$config['db_host'],
			'hostport'=>$config['db_port'],
			'database'=>$config['db_name'],
			'password'=>$config['db_pwd'],
			'username'=>$config['db_user'],
			'prefix'=>$config['db_prefix'],
			'params'=>[
				\PDO::ATTR_CASE => \PDO::CASE_LOWER,
				\PDO::ATTR_EMULATE_PREPARES => true
			]
		);
		return $dbConfig;
	}
}
