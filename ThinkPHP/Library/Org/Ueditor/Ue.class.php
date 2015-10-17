<?php
namespace Org\Ueditor;
/**
 * ThinkPHP3.2+版本 Ueditor后台处理类 兼容SAE平台 ，以下简称UE
 * 一、配置方法：
 *   本类参考了TP上传类的配置方法。
 *   1.静态配置。
 *     在Ueditor的配置文件即config.json中配置
 *   2.动态配置。
 *     a.数组格式。键名对应配置文件中的配置项,此外还增加了'rootPath'和'saveExtend'两个配置项
 *       $config = array(
 *         "imageActionName" => "uploadimage",
 *         "imageFieldName"  => "upfile",
 *       );
 *       $Ue = new \Org\Ueditor\Ue($config);
 *       $Ue->run();
 *     b.对象。
 *       $Ue = new \Org\Ueditor\Ue();
 *       $Ue->imageActionName = 'uploadimage';
 *       $Ue->imageFieldName = 'upfile';
 *       $Ue->run();
 *       
 *   优先级：以上介绍的配置方法，后面的会覆盖之前的。
 *   
 * 二、上传根路径问题：
 *   1.请确保根目录文件夹存在或SAE中存在该domain
 *   2.大小写问题：由于SAE的domain名必须为小写，所以可能出现的情况是根路径在两种环境下名称不同，
 *     比如：我就是喜欢TP的命名规则，我就是想定义一个'./Uploads'，就讨厌小写滴。
 *     <背景音乐>除了是你跟我走，没有别的追求~~</背景音乐>
 *     →_→ 我表示非常赞同，本类中已经作了处理，SAE环境下已自动转换成小写。
 *
 * 三、文件保存扩展：
 *   增加这个参数的初衷是为了方便大家自定义文件保存规则。
 *   因为就UE的官方配置来讲，它相对来说是固定的，静态的，我不知道这样表述是否准确，总之就是为了增加灵活性。
 *   比如：
 *     "imagePathFormat": "/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}",
 *     这是图片保存规则。其中包括年、月、日、时间和随机数这几个替换量。
 *     请问哪一个是我们可以主动控制的？没有！
 *     为此，我增加了一个 {extend} 替换量，使用方法和其它几个替换量一致。
 *     载入配置以后，这个 {extend} 会替换为配置项'saveExtend'的值。比如：我们可以将这个值设置为用户id。
 *     
 * @version 1.0
 * @author Mr.Old <lostphper@sina.com> 2014-09-21 11:32
 */
class Ue {
	/**
	 * 默认UE配置
	 * @var array
	 */
	private $config = array(
    'rootPath'   => './Uploads', // 上传根路径
    'saveExtend' => '',         // 文件保存路径扩展
	);

  /**
   * 执行动作
   * @var string
   */
  private $action = '';

  /**
   * 当前动作配置
   * @var array
   */
  private $actionConfig = array();

	/**
   * 错误信息
   * @var string
   */
  private $error = ''; //错误信息

 	/**
   * UE驱动实例
   * @var Object
   */
  private $UE;

  /**
	 * 文件信息
	 * @var array
	 */
	private $finfo = array();
	 
  /**
   * 构造方法，用于构造UE实例，加载配置等。
   * @param array  $config 配置
   * @param string $save_extend 文件保存规则扩展
   * @param string $root_path 上传根路径 默认为：'./Uploads/'
   * @param string $config_path UE配置文件地址，相对于当前模块配置目录 MODULE_PATH.'Conf/',默认为 MODULE_PATH.'Conf/ueditor.json'
   * @param string $driver 要使用的UE驱动 LOCAL-本地驱动，SAE-SAE驱动
   */
  public function __construct($config = array(), $save_extend = '', $root_path = '', $config_path = '', $driver = ''){
    // 从配置文件加载配置
  	$this->getConfig($config_path);

  	// 设置根路径
  	if ($root_path) $this->config['rootPath'] = $root_path;

    //设置文件保存扩展
    if (null !== $save_extend && '' !== $save_extend) $this->config['saveExtend'] = $save_extend;

		// 获取动态配置
    $this->config = array_merge($this->config, $config);

    /* 驱动设置 */
    $this->setDriver($driver);

  }

 	/**
   * 使用 $this->name 获取配置
   * @param  string $name 配置名称
   * @return multitype    配置值
   */
  public function __get($name) {
    return $this->config[$name];
  }

  public function __set($name,$value){
    if (isset($this->config[$name])) {
      $this->config[$name] = $value;
    }
  }

  public function __isset($name){
    return isset($this->config[$name]);
  }

  /**
   * UE 执行入口
   */
  public function run() {
    //头信息设置
    header("Content-Type: text/html; charset=utf-8");

    /* 修改catcherLocalDomain 配置项，以解决SAE环境下图片抓取的bug*/
    $domain = $this->UE->getDomainUrl($this->config['rootPath']);
    if ($domain) {
      $this->config['catcherLocalDomain'][] = $domain;
    }

    /* 获取执行动作action名称 */
    $action = I('get.action');

    /* 抽取当前操作配置 */
    $this->actionConfig = $this->autoConfig($action, $this->config);

    /* 请求动作类型 switch */
	  switch ($action) {
	  	/* 配置检测 */
	    case 'config':
	      $result =  $this->config;
	      break;

	    /* 上传图片 */ 
	    case 'uploadimage':
	    /* 上传视频 */
	    case 'uploadvideo':
	    /* 上传文件 */
	    case 'uploadfile':
	    	$result = $this->upload();
	    	break;

	    /* 上传涂鸦 */
	    case 'uploadscrawl':
	    	$result = $this->upBase64();
	    	break;

	    /* 列出图片 */
	    case 'listimage':
	    /* 列出文件 */
	    case 'listfile':
	      $result = $this->lister();
	      break;

	    /* 抓取远程文件 */
	    case 'catchimage':
	      $result =$this->catcher();
	      break;

	    default:
	      $result = array(
	        'state'=> '请求地址出错'
	      );
	      break;
		}

		/* 输出结果 */
		$this->ueReturn($result);
  }

  /**
   * 输出返回给UE前端的必要信息
   * @param array $result 返回结果数组
   */
  private function ueReturn($result) {
    /* 错误捕捉 */
    if (empty($result)) {
      $result = array(
        'state' => $this->getError(),
      );
    } 
    
  	/* json处理 */
  	$result = json_encode($result);

    /* callback实现 */
		if (isset($_GET["callback"])) {
	    if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
	      echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
	    } else {
        echo json_encode(array(
          'state'=> 'callback参数不合法'
        ));
	    }
		} else {
		  echo $result;
		}
  }

  /**
   * 上传文件
   * @param 文件信息数组 $files ，通常是 $_FILES数组
   * @return multitype
   */
  private function upload($files='') {
    /* 获取上传配置 */
    $config = $this->actionConfig;
    if (empty($config)) {
      $this->error = "获取上传配置失败！";
      return false;
    }

    /* 获取上传文件 */
    if ('' === $files) {
      $field_name = $config['fieldName'];
      $file  =   $_FILES[$field_name];
    }
    if (empty($file)) {
      $this->error = '没有上传的文件！';
      return false;
    }

    /* 检查上传根目录 */
    $root_path = $this->config['rootPath'];
    if (!$this->checkRoot($root_path)) return false;

    /* 文件处理 */
    //原文件名
    $file['name'] = strip_tags($file['name']);

    //获取上传文件后缀
    $file['ext']    =   pathinfo($file['name'], PATHINFO_EXTENSION);

    //重命名文件
    $full_name = $this->parseFormat($config['pathFormat'], $file['name']); //按照文件保存规则进行解析
    if ($full_name !== null && $full_name !== '') {
      $full_name = rtrim($full_name) . '.' . $file['ext'];
    } else {
      $this->error = '文件重命名出错！';
      return false;
    }

    //获取新文件名
    $file['save_name'] = pathinfo($full_name, PATHINFO_BASENAME);

    //获取文件上传目录
    $save_path = dirname($full_name);
    if ($save_path) $save_path .= '/';

    //检查上传目录
    if (!$this->checkSavePath($save_path)) return false;

    $file['save_path'] = $save_path;
    
    //上传文件检测
    if (!$this->check($file)) return false;

    /* 对图像文件进行严格检测 */
    $ext = strtolower($file['ext']);
    if(in_array($ext, array('gif','jpg','jpeg','bmp','png','swf'))) {
      $imginfo = getimagesize($file['tmp_name']);
      if(empty($imginfo) || ($ext == 'gif' && empty($imginfo['bits']))){
        $this->error = '非法图像文件！';
        return false;
      }
    }

    /* 保存文件 并记录保存成功的文件 */
    if ($this->UE->save($file, false)) {
      unset($file['error'], $file['tmp_name']);
      $this->finfo[] = $file;
      return array(
        "state" => "SUCCESS",          //上传状态，上传成功时必须返回"SUCCESS"
        "url" => $file['url'],            //返回的地址
        "title" => $file['save_name'],          //新文件名
        "original" => $file['name'],       //原始文件名
        "type" => $file['ext'],            //文件类型
        "size" => $file['size'],           //文件大小
      );
    } else {
      $this->error = $this->UE->getError();
    }
  }

  /**
   * base64 stream上传
   * @return multitype
   */
  private function upBase64() {
    /* 获取上传配置 */
    $config = $this->actionConfig;
    if (empty($config)) {
      $this->error = "获取上传配置失败！";
      return false;
    }

    /* 获取图片数据 */
    $base64Data = I('post.'.$config['fieldName']);
    $img = base64_decode($base64Data);
    if (empty($img)) {
      $this->error = '涂鸦图片不存在！';
      return false;
    }

    $file['name'] = $config['oriName']; //原始文件名
    $file['size'] = strlen($img);  //大小
    $file['ext']   = pathinfo($file['name'], PATHINFO_EXTENSION);  //后缀

    //重命名文件
    $full_name = $this->parseFormat($config['pathFormat'], $file['name']); //按照文件保存规则进行解析
    if ($full_name !== null && $full_name !== '') {
      $full_name = rtrim($full_name) . '.' . $file['ext'];
    } else {
      $this->error = '文件重命名出错！';
      return false;
    }

    /* 检查上传根目录 */
    $root_path = $this->config['rootPath'];
    if(!$this->checkRoot($root_path)) return false;

    //获取文件上传目录
    $save_path = dirname($full_name);
    if ($save_path) $save_path .= '/';

    //检查上传目录
    if (!$this->checkSavePath($save_path)) return false;
    $file['save_path'] = $save_path;

    //获取新文件名
    $file['save_name'] = pathinfo($full_name, PATHINFO_BASENAME);

    //检查文件大小是否超出限制
    if (!$this->checkSize($file['size'])) {
      $this->error = "上传文件大小不符!";
      return false;
    }

    //检查文件类型是否合法
    if (!$this->checkExt($file['ext'])) {
      $this->error = "涂鸦图片类型不允许！";
      return false;
    }

    // 保存文件并记录保存成功的文件
    if ($this->UE->put($file, $img, false)) {
      $this->finfo[] = $file;
      return array(
        "state" => "SUCCESS",          //上传状态，上传成功时必须返回"SUCCESS"
        "url" => $file['url'],            //返回的地址
        "title" => $file['save_name'],          //新文件名
        "original" => $file['name'],       //原始文件名
        "type" => $file['ext'],            //文件类型
        "size" => $file['size'],           //文件大小
      );
    } else {
      $this->error = $this->UE->getError();
    }
  }

  /**
   * 远程图片抓取
   * @return multitype 
   */
  private function catcher() {
    $config = $this->actionConfig;
    /* 获取图片链接 */
    $field = $config['fieldName'];
    if (isset($_POST[$field])) {
      $source = $_POST[$field];
    } else {
      $source = $_GET[$field];
    }

    /* 逐个抓取 */
    $list = array();
    foreach ($source as $key => $url) {
      $list[] = $this->saveRemote($url, $config);
    }

    /* 返回抓取数据 */
    return array(
      'state'=> count($list) ? 'SUCCESS':'ERROR',
      'list'=> $list
    );
  }

  /**
   * 列出文件
   * @return [type] [description]
   */
  private function lister() {
    /* 获取操作配置 */
    $config = $this->actionConfig;
    if (empty($config)) {
      $this->error = "获取配置失败！";
      return false;
    }
    $allow_files = $config['allowFiles'];  //允许列出文件类型
    $list_size   = $config['listSize'];    //每次列出文件数量
    $path        = $config['pathFormat'];  //列出文件的目录

    /* 检查根目录 */
    $root_path = $this->config['rootPath'];
    if(!$this->checkRoot($root_path)) return false;

    /* 解析目录 主要是替换文件保存规则中的扩展 */
    $path = $this->parseFormat($path);

    /* 获取参数 */
    $size  = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $list_size;
    $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
    $end   = $start + $size;

    /* 获取文件列表 */
    $files = $this->UE->listFile($path, $allow_files);
    if (!count($files)) {
      return array(
        "state" => "no match file",
        "list" => array(),
        "start" => $start,
        "total" => count($files)
      );
    }

    /* 获取指定范围的列表 */
    $len = count($files);
    for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
      $list[] = $files[$i];
    }
    //倒序
    //for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
    //    $list[] = $files[$i];
    //}

    /* 返回数据 */
    $result = array(
      "state" => "SUCCESS",
      "list" => $list,
      "start" => $start,
      "total" => count($files)
    );

    return $result;
  }

  /**
   * 拉取远程图片
   * @param string $img_url 目标图片链接
   * @param array $config 配置数组
   * @return array
   */
  private function saveRemote($img_url, $config) {
    /* 获取上传配置 */
    if (empty($config)) {
      return array(
        'state' => $this->error = "获取上传配置失败！",
      );
    }

    /* 链接非法字符处理 */
    $img_url = htmlspecialchars($img_url);
    $img_url = str_replace("&amp;", "&", $img_url);

    /* http开头验证 */
    if (strpos($img_url, "http") !== 0) {
      return array(
        'state' => $this->error = '链接不是http链接！',
      );
    }

    /* 获取请求头并检测死链 */
    $heads = $this->UE->getHeaders($img_url);
    if (false === stripos($heads[0], "200") && false === stripos($heads[0], "OK")) {
      return array(
        'state' => $this->error = '链接不可用！',
      );
    }

    /* 格式验证(扩展名验证和Content-Type验证) */
    $fileType = strtolower(strrchr($img_url, '.'));
    if (!in_array($fileType, $config['allowFiles']) || false === stripos($heads['Content-Type'], "image")) {
      return array(
        'state' => $this->error = '链接contentType不正确！',
      );
    }

    /* 打开输出缓冲区并获取远程图片 */
    ob_start();
    $context = stream_context_create(
      array(
        'http' => array(
          'follow_location' => false, // don't follow redirects
        )
      )
    );
    readfile($img_url, false, $context);
    $img = ob_get_contents();
    ob_end_clean();
    preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $img_url, $m);

    $file['name'] = $m ? $m[1] : '';  //原始文件名
    $file['name'] = htmlspecialchars($file['name']);
    $file['size'] = strlen($img);                     //大小
    $file['ext']  = pathinfo($file['name'], PATHINFO_EXTENSION);  //后缀

    //重命名文件
    $full_name = $this->parseFormat($config['pathFormat'], $file['name']); //按照文件保存规则进行解析
    if ($full_name !== null && $full_name !== '') {
      $full_name = rtrim($full_name) . '.' . $file['ext'];
    } else {
      return array(
        'state' => $this->error = '文件重命名出错！',
      );
    }

    /* 检查上传根目录 */
    $root_path = $this->config['rootPath'];
    if(!$this->checkRoot($root_path)) {
      return array(
        'state' => $this->error,
      );
    }

    //获取文件上传目录
    $save_path = dirname($full_name);
    if ($save_path) $save_path .= '/';

    //检查上传目录
    if (!$this->checkSavePath($save_path)) {
      return array(
        'state' => $this->error,
      );
    }
    $file['save_path'] = $save_path;

    //获取新文件名
    $file['save_name'] = pathinfo($full_name, PATHINFO_BASENAME);

    //检查文件大小是否超出限制
    if (!$this->checkSize($file['size'])) {
      return array(
        'state' => $this->error = "上传文件大小不符!",
      );
    }

    //检查文件类型是否合法
    if (!$this->checkExt($file['ext'])) {
      return array(
        'state' => $this->error = '图片类型不允许!',
      );
    }

    // 保存文件并记录保存成功的文件
    if ($this->UE->put($file, $img, false)) {
      $this->finfo[] = $file;
      return array(
        "state"    => "SUCCESS",            //上传状态，上传成功时必须返回"SUCCESS"
        "url"      => $file['url'],         //返回的地址
        "title"    => $file['save_name'],   //新文件名
        "original" => $file['name'],        //原始文件名
        "type"     => $file['ext'],         //文件类型
        "size"     => $file['size'],        //文件大小
        "source"   => $img_url,             //图片抓取链接
      );
    } else {
      return array(
        'state' => $this->error = $this->UE->getError(),
      );
    }
  }


  /**
   * 获取错误信息
   * @return string 错误信息
   */
  public function getError () {
  	return $this->error;
  }
  /**
   * 获取文件上传信息，用于数据库保存之类的
   * @return array 
   */
  public function getInfo() {
  	return $this->finfo;
  }

  /**
   * 加载配置文件
   * @param string $config_path 配置文件相对于当前模块配置目录的路径
   */
  private function getConfig($config_path = null) {
  	$name = $config_path ? ltrim('./', $config_path) : 'ueditor.json';
  	$config_path = MODULE_PATH . 'Conf/' . $name;
  	if (is_file($config_path)) {
  		// $this->config = load_config($config_path); 这种方法加载失败，难道注释影响？ !--
  		$config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($config_path)), true);
  		$this->config = array_merge($this->config, $config);
  	} else {
  		echo json_encode(array(
        'state'=> '配置文件加载失败!'
      ));
      exit;
  	}
  } 

  /**
   * 抽取当前操作配置
   * @param string $action 执行动作名
   * @param array $config 配置数组
   * @return  multitype 
   */
  private function autoConfig($action, $config) {
    switch ($action) {
      //上传图片
      case 'uploadimage':
        $actionConfig = array(
          "pathFormat" => $config['imagePathFormat'],  // 上传文件保存路径规则
          "maxSize"    => $config['imageMaxSize'],     // 上传文件大小  最大值限制
          "allowFiles" => $config['imageAllowFiles'],  // 允许上传的文件类型 后缀限制
          'fieldName'  => $config['imageFieldName'],   // 上传文件的表单名
        );
        break;

      //上传视频      
      case 'uploadvideo':
        $actionConfig = array(
          "pathFormat" => $config['videoPathFormat'],
          "maxSize"    => $config['videoMaxSize'],
          "allowFiles" => $config['videoAllowFiles'],
          "fieldName"  => $config['videoFieldName'],
        );
        break;

      //上传文件
      case 'uploadfile':
        $actionConfig = array(
          "pathFormat" => $config['filePathFormat'],
          "maxSize"    => $config['fileMaxSize'],
          "allowFiles" => $config['fileAllowFiles'],
          "fieldName"  => $config['fileFieldName'],
        );
        break;

      //上传涂鸦
      case 'uploadscrawl':
        $actionConfig = array(
          "pathFormat" => $config['scrawlPathFormat'],
          "maxSize"    => $config['scrawlMaxSize'],
          "allowFiles" => $config['scrawlAllowFiles'],
          "fieldName"  => $config['scrawlFieldName'],
          "oriName"    => "scrawl.png"  // 默认原始文件名
        );
        break;

      //列出文件
      case 'listfile':
        $actionConfig = array(
          'allowFiles' => $config['fileManagerAllowFiles'],  // 允许列出的文件类型  后缀限制
          'listSize'   => $config['fileManagerListSize'],    // 允许列出的文件大小  最大值限制
          'pathFormat' => $config['fileManagerListPath'],    // 文件列出的路径
        );
        break;
      //列出图片  
      case 'listimage':
        $actionConfig = array(
          'allowFiles' => $config['imageManagerAllowFiles'],
          'listSize'   => $config['imageManagerListSize'],
          'pathFormat' => $config['imageManagerListPath'],
        );
        break;
      // 远程抓取图片
      case 'catchimage':
        $actionConfig = array(
          "pathFormat" => $config['catcherPathFormat'],
          "maxSize"    => $config['catcherMaxSize'],
          "allowFiles" => $config['catcherAllowFiles'],
          "fieldName"  => $config['catcherFieldName'],      //  
          "oriName"    => "remote.png",                           // 原始文件名
        );
        break;
    }

    //返回当前操作配置
    return $actionConfig;
  }

  /**
   * 设置UE驱动
   * @param string $driver 驱动名称
   * @param array $config 上传配置
   */
  private function setDriver($driver = null){ 
  	$driver =  $driver ? : ('Sae' == STORAGE_TYPE ? "Sae" : "Local") ;
  	$class = strpos($driver,'\\')? $driver : 'Org\\Ueditor\\Driver\\'.ucfirst(strtolower($driver));
    $this->UE = new $class();
    if (!$this->UE) {
      echo json_encode(array(
        'state'=> "不存在驱动：{$class}",
      ));
      exit;
    }
  }

  /**
   * 获取错误代码信息
   * @param string $errorNo  错误号
   */
  private function error($errorNo) {
    switch ($errorNo) {
      case 1:
        $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
        break;
      case 2:
        $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
        break;
      case 3:
        $this->error = '文件只有部分被上传！';
        break;
      case 4:
        $this->error = '没有文件被上传！';
        break;
      case 6:
        $this->error = '找不到临时文件夹！';
        break;
      case 7:
        $this->error = '文件写入失败！';
        break;
      default:
        $this->error = '未知上传错误！';
    }
  }

  /**
   * 根目录检查
   * @param string $root_path 根目录
   * @return bool 
   */
  private function checkRoot($root_path) {
    if (!$this->UE->checkRoot($root_path)) {
      $this->error = $this->UE->getError();
      return false;
    } else {
      return true;
    }
  }

  /**
   * 保存目录检查
   * @param  string $save_path 保存目录
   * @return bool 
   */
  private function checkSavePath($save_path) {
    if (!$this->UE->checkSavePath($save_path)) {
      $this->error = $this->UE->getError();
      return false;
    } else {
      return true;
    }
  }

  /**
   * 检查上传的文件
   * @param array $file 文件信息
    */
  private function check($file) {
    /* 文件上传失败，捕获错误代码 */
    if ($file['error']) {
      $this->error($file['error']);
      return false;
    }

    /* 无效上传 */
    if (empty($file['name'])){
      $this->error = '未知上传错误！';
    }

    /* 检查是否合法上传 */
    if (!is_uploaded_file($file['tmp_name'])) {
      $this->error = '非法上传文件！';
      return false;
    }

    /* 检查文件大小 */
    if (!$this->checkSize($file['size'])) {
      $this->error = '上传文件大小不符！';
      return false;
    }

    /* 检查文件后缀 */
    if (!$this->checkExt($file['ext'])) {
      $this->error = '上传文件后缀不允许'.$file['ext'];
      return false;
    }

    /* 通过检测 */
    return true;
  }

  /**
   * 检查文件大小是否合法
   * @param integer $size 数据
   */
  private function checkSize($size) {
    return !($size > $this->actionConfig['maxSize']) || (0 == $this->actionConfig['maxSize']);
  }

  /**
   * 检查上传的文件后缀是否合法
   * @param string  后缀
   */
  private function checkExt($ext) {
    $ext = '.' . $ext;
    return empty($this->actionConfig['allowFiles']) ? true : in_array(strtolower($ext), $this->actionConfig['allowFiles']);
  }

  /**
   * 解析文件保存规则
   * @param string $format 规则字符串
   * @param string $file_name 文件名
   * @return multitype (string) path | (bool) false
   */
  private function parseFormat($format, $file_name = '') {
    if ($format !== null && '' !== $format) {
      //替换保存扩展
      $extend = $this->config['saveExtend'];
      if ($extend !== null && '' !== $extend) {
        $format = str_replace('{extend}', $extend, $format, $i);
        //如果设置了保存扩展，但是没有在配置中添加{extend}替换量，则默认添加在保存规则前面
        if (!$i) {
          $format = '/'.$extend . $format;
        }
      }

      //替换日期 时间
      $t = time();
      $d = explode('-', date("Y-y-m-d-H-i-s"));
      $format = str_replace("{yyyy}", $d[0], $format);
      $format = str_replace("{yy}", $d[1], $format);
      $format = str_replace("{mm}", $d[2], $format);
      $format = str_replace("{dd}", $d[3], $format);
      $format = str_replace("{hh}", $d[4], $format);
      $format = str_replace("{ii}", $d[5], $format);
      $format = str_replace("{ss}", $d[6], $format);
      $format = str_replace("{time}", $t, $format);

      //过滤文件名的非法字符,并替换文件名
      //$oriName = substr($file_name, 0, strrpos($file_name, '.'));  这是第一种方法
      $oriName = substr(pathinfo("_{$file_name}", PATHINFO_FILENAME), 1); //这是第二种方法，解决中文名bug
      $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
      $format  = str_replace("{filename}", $oriName, $format);

      //替换随机字符串
      if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
        $chars= str_repeat('0123456789',3);
        $len = $matches[1]; //长度
        if($len>10 ) {  //位数过长重复字符串一定次数
          str_repeat($chars,$len);
        }
        $chars   = str_shuffle($chars);  //打散
        $chars   = mt_rand(1,9) . $chars;  //为了达到模拟随机整数的效果，设置数字首位为非零个位数
        $randNum = substr($chars,0,$len);  //得到随机数字
        $format  = preg_replace("/\{rand\:[\d]*\}/i", $randNum, $format);  //替换
      }
      return $format;
    } else {
      return false;
    }
  }
}