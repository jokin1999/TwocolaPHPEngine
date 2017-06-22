<?php
// +----------------------------------------------------------------------
// | Twocola PHP Engine [ DO IT　EASY ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 Twocola Studio All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Jokin <327928971@qq.com>
// +----------------------------------------------------------------------
/*
** APP核心类
** Version: 1.0.6.2101
*/
namespace TUnit;
class App {

  /**
   * 运行应用
   * @param  void
   * @return void
  **/
  static public function run(){
    $APP        =  C("APP");
    $CONTROLLER =  C("CONTROLLER");
    $METHOD     =  C("METHOD");
    // 判断配置是否正确
    $D  = DIRECTORY_SEPARATOR;
    $CP = ".".C("APP_PATH").$D.$APP."{$D}Controller{$D}";
    if(file_exists($CP."Displayer{$D}".C("CONTROLLER")."Displayer" .C("CLASS_EXT") )&&
      file_exists( $CP."Behavior{$D}" .C("CONTROLLER")."Behavior"  .C("CLASS_EXT") )&&
      file_exists( $CP."Common{$D}BehaviorCommon"                  .C("CLASS_EXT") )){
      // 载入应用
      require_once($CP."Common{$D}BehaviorCommon"                  .C("CLASS_EXT") );
      require_once($CP."Behavior{$D}" .C("CONTROLLER")."Behavior"  .C("CLASS_EXT") );
      require_once($CP."Displayer{$D}".C("CONTROLLER")."Displayer" .C("CLASS_EXT") );
      $ClassName = "\\Controller\\Displayer\\".$CONTROLLER."Displayer";
      $template = new $ClassName();
      if(method_exists($template,C("METHOD"))){
        $template->$METHOD();
      }else{
        Template\Template::show404();
        exit();
      }
    }else{
      Template\Template::show404();
      exit();
    }
  }

  /**
   * 判断应用是否存在
   * @param  void
   * @return void
  **/
  static public function AppExist($app){
    $path = ".".TLaungher::GetRealPath(APP_PATH);
    if(Storage\StorageCore::FolderExist($path.DIRECTORY_SEPARATOR.$app)){
      return true;
    }else{
      return false;
    }
  }

  /**
   * 获取应用一览
   * @param  void
   * @return void
  **/
  static public function AppList($path = APP_PATH){
    $path = ".".TLaungher::GetRealPath($path);
    // 判断目录是否存在
    if( !is_dir($path) ){
      return false;
    }
    $dh = opendir($path);
    $dir = array();  // 初始化
    while ( ($t_dir = readdir($dh)) !== false ) {
      if($t_dir != "." && $t_dir != ".."){
        $dir[] = $t_dir;
      }
    }
    if( !empty($dir) ){
      return $dir;
    }else{
      return false;
    }
  }

}
?>
