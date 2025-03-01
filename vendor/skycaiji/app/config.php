<?php
use think\Request;
return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用调试模式
    'app_debug'              => false,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => 'default_filter_func',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => 'Action',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => '',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => true, //必须true
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,
    // 全局请求缓存排除规则
    'request_cache_except'   => [],

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str' => [
    	'__ROOT__'=>rtrim(preg_replace('/\/index\.php.*/i','',Request::instance()->root()),'\/\\'),
    	'__PUBLIC__'=>rtrim(preg_replace('/\/index\.php.*/i','',Request::instance()->root()),'\/\\').'/public'
    ],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => 'common@common:success',
    'dispatch_error_tmpl'    => 'common@common:error',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => APP_PATH.'public/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => true,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => '\util\Log',//使用自定义的日志驱动
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => ['error'],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'skycaiji',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'p',
        'list_rows' => 15,
    ],
    
    
    /**********************************自定义配置*********************************************/
    'cli_cache_config'=>array('view_replace_str','root_path','app_path','apps_path','plugin_path','vendor_path','runtime_path','root_url','root_website'),//cli模式下需要缓存的配置，否则会失效引起程序bug
    
	'html_v'=>'',//css和js版本,在init.php中设置
	
    'root_path'=>realpath(SKYCAIJI_PATH),//站点根目录
	'app_path'=>realpath(APP_PATH),//vendor/skycaiji/app项目目录
    'apps_path'=>realpath(SKYCAIJI_PATH.'app'),//应用程序目录
    'plugin_path'=>realpath(SKYCAIJI_PATH.'plugin'),//插件目录
    'vendor_path'=>realpath(VENDOR_PATH),//vendor目录
    'runtime_path'=>realpath(RUNTIME_PATH),//runtime目录
	'root_url'=>rtrim(preg_replace('/\/index\.php.*/i','',Request::instance()->root()),'\/\\'),//网址根目录
	'root_website'=>(Request::instance()->isSsl()?'https':'http').'://'.trim(Request::instance()->host(),'\/\\').rtrim(preg_replace('/\/index\.php.*/i','',Request::instance()->root()),'\/\\'),//带域名网站根目录，去掉index.php，结尾不带/
	
	'allow_coll_modules'=>array('pattern'),//允许的采集器模块
	'release_modules'=>array('cms','db','file','toapi','api','diy'),//发布模块
	'yzm_expire'=>1200, //邮箱验证码过期时间(秒)
	
	'allow_origins'=>array('http://www.skycaiji.com','https://www.skycaiji.com'),//默认允许的官方平台网址
	
	'allow_process_func'=>array(
    	'strtotime'=>'日期转时间戳',
    	'strtolower'=>'全部小写',
    	'strtoupper'=>'全部大写',
    	'ucfirst'=>'首字母大写',
    	'ucwords'=>'每个单词首字母大写',
    	'base64_encode'=>'编码',
    	'md5'=>'加密',
    ),//数据处理允许的函数

    'allow_process_if'=>array(
        'empty'=>'是空值',
        'is_numeric'=>'是数字',
    ),//数据处理>条件判断允许的函数
    /*******************************************************************************/
];
