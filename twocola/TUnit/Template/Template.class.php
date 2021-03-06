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
/**
* TCE模板引擎核心处理类
* @version: 2.0.0
**/
namespace TUnit\Template;
class Template {
  // 存储区
  /*
  ** 模板临时存储
  ** @param  string $content
  */
  static public $Content =  "";

  /*
  ** 变量传递临时存储
  ** @param  string $content
  */
  static public $assign  =  "";

  /*
  ** 显示处理完成的页面
  ** @param  string $title
  ** @return void
  */
  static public function show($title = false){
    $D = DIRECTORY_SEPARATOR;
    // 用户访问模板地址
    $tpl = ".".C("APP_PATH").$D.C("APP").$D."View".$D.C("CONTROLLER").$D.C("METHOD").C("TPL_EXT");
    // 检查是否存对应模板文件
    if( !is_file($tpl) ){
      ob_end_clean();
      // 不存在对应模板文件
      $path = C("TPL");
      if( isset($path['PageNotFound']) && $path['PageNotFound'] != false ){
        showUserTpl($path['PageNotFound']);  // 获取用户自定义模板
      }else{
        self::showError("E_S01_P0","模板文件不存在。");
      }
      return ;

    }else{
      // 载入用户访问模板文件
      $content = file_get_contents($tpl);
      if( $title!= false ){
        self::assign("TITLE",$title);   // 传递标题
      }
      self::ProcessTpl($content);       // 处理并临时存储模板文件
      include( self::GeneralCache() );  // 生成缓存并显示页面
      return ;
    }
  }

  /*
  ** 直接展示内容
  ** @param  string $content
  ** @return void
  */
  static public function showContent($content){
    self::ProcessTpl($content);
    include( self::GeneralCache() );
    return ;
  }

  static public function showError($errCode,$reason){
    if(APP_DEBUG == true){
      self::assign("errCode",$errCode);                  // 传递变量
      self::assign("error",$reason);
      $content = getPresetTpl("TUnit/Error/Default");    // 获取模板
    }else{
      // 默认应用错误
      $path = C("TPL");
      if( isset($path['Error']) && $path['Error'] != false ){
        showUserTpl($path['Error']); //获取用户自定义模板
      }else{
        $tpl = getPresetTpl("TUnit/Error/ErrorException_Secure");
      }
    }
    $content = self::ProcessTpl($content);             // 处理模板
    $content = self::GeneralCache(false,"_".$errCode); // 生成缓存并获取路径
    include $content;                                  // 展示页面
    return ;
  }

  static public function show404(){
    $path = C("TPL");
    if( isset($path['PageNotFound']) && $path['PageNotFound'] != false ){
      showUserTpl($path['PageNotFound']);
    }else{
      $content = getPresetTpl("APP/Error/PageNotFound");    // 获取模板
      $content = self::ProcessTpl($content);                // 处理模板
      $content = self::GeneralCache(false,"_404");          // 生成缓存并获取路径
      include($content);                                    // 展示页面
    }
    return ;
  }

  /*
  ** 传递变量
  ** @param  string $name  模板上的变量名
  ** @param  string $var   真实变量
  ** @return void
  */
  /* 替换变量，格式{$变量名} */
  static public function assign($name,$var){
    self::$assign .= "<?php \${$name}=".var_export($var,true)." ?>";
  }

  /*
  ** 输出生成缓存文件
  ** @param  string $content     要输出的内容
  ** @return string $filename    缓存文件路径
  */
  static public function GeneralCache($content=false,$filename=false){
    $D = DIRECTORY_SEPARATOR;
    $APP_PATH = ".".C("APP_PATH");
    $_filename = ($filename === false) ? C("APP")."_".C("CONTROLLER")."_".C("METHOD").C("CACHE_EXT") : $filename.C("CACHE_EXT");
    $_filename = $APP_PATH.$D.C("APP").$D."Runtime".$D."Cache".$D.$_filename;
    if( !is_dir(dirname($_filename)) ){
      $_filename = $APP_PATH.$D.C("APP_DEFAULT").$D."Runtime".$D."Cache".$D.$filename.C("CACHE_EXT");
    }
    $content  = ($content  === false) ? self::$Content : $content;
    \TUnit\Storage\StorageCore::Put($_filename,$content);
    return $_filename;
  }

  //-----模板处理

  /**
  * 获取处理完的模板
  * @param  void
  * @return string
  **/
  static public function GetProcessedTpl(){
    return self::$Content;
  }

  /**
  * 模板集合处理
  * @param  string $content
  * @return void
  **/
  static public function ProcessTpl($content){
    $content = self::ProtectContent($content); // 非解析区块
    $content = self::ClearComment($content);   // 清除注释
    $content = self::IncludeTpl($content);     // 引用模板
    $content = self::ProtectContent($content); // 非解析区块
    $content = self::ClearComment($content);   // 清除模板注释
    $content = self::Variable($content);       // 模板标签集合处理
    $content = self::MagicTag($content);       // 魔术标签
    $content = self::VarReference($content);   // 变量引用
    $content = self::ConReference($content);   // 常量引用
    $content = self::SVarReference($content);  // 特殊变量引用
    $content = self::CreateURL($content);      // 生成链接
    $content = self::$assign.$content;         // 传递变量并临时存储模板
    self::$assign  = "";                       // 清空变量传递临时存储区
    self::$Content = $content;
    return ;
  }

  /**
  * 自定义魔法变量
  * @param  string $content 初始内容
  * @param  string $pattern 正则表达式
  * @param  string $text    替换文本
  * @return string $content 结果内容
  **/
  static public function CustomizeVar($content,$pattern,$text){
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      for($i=0; $i<count($matches[0]); $i++){
        $origin  =  $matches[0][$i];
        $wt      =  $matches[1][$i];
        $content =  str_replace($origin,$wt,$content);
      }
    }
    return $content;
  }

  /**
   * 模板标签集合处理
   * @param  string  $content 内容
   * @return string  $content
  **/
  static public function Variable($content){
    $pattern = "/<else[\s]*(?:[\/]*)[\s]*>/iUm";
    $content = preg_replace($pattern, "<?php else: ?>", $content);
    $content = self::Tag_If($content);
    $content = self::Tag_Empty($content);
    $content = self::Tag_Volist($content);
    $content = self::Tag_NotEmpty($content);
    return $content;
  }

  /**
   * Volist标签
   * @param  string  $content 内容
   * @return string  $content
  **/
  static public function Tag_Volist($content){
    $content = str_ireplace("</volist>","<?php endforeach;endif; ?>",$content);
    // 处理Volist
    $pattern = "/<volist[\s\S]+>/iUm";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg == 0) return $content;
    for ($i=0; $i < $preg; $i++){
      // name
      $pat_name = "/name=(['|\"])(?<name>.+)\\1/iUm";
      $preg_name = preg_match($pat_name,$matches[0][$i],$match);
      if($preg_name != 0){
        $name = $match['name'];
      }else{
        continue;
      }
      // value
      $pat_value = "/value=(['|\"])(?<value>.+)\\1/iUm";
      $preg_value = preg_match($pat_value,$matches[0][$i],$match);
      if($preg_value != 0){
        $value = $match['value'];
      }else{
        continue;
      }
      // key
      $pat_key = "/key=(['|\"])(?<key>.+)\\1/iUm";
      $preg_key = preg_match($pat_key,$matches[0][$i],$match);
      if($preg_key != 0){
        $key = $match['key'];
      }else{
        $key = false;
      }
      if($value && $key){
        $content = str_replace($matches[0][$i],"<?php if(isset(\${$name}) && is_array(\${$name}) && !empty(\${$name})):foreach(\${$name} as \${$key}=>\${$value}): ?>",$content);
      }else{
        $content = str_replace($matches[0][$i],"<?php if(isset(\${$name}) && is_array(\${$name}) && !empty(\${$name})):foreach(\${$name} as \${$value}): ?>",$content);
      }
    }
    return $content;
  }

  /**
   * Empty标签
   * @param  string  $content 内容
   * @return string  $content
  **/
  static public function Tag_Empty($content){
    $content = str_ireplace("</empty>","<?php endif; ?>",$content);
    $pattern = "/<empty[\s]*name=(['|\"])(?<name>.+)\\1[\s\S]*>/iUm";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      for ($i=0; $i < $preg; $i++) {
        $content = str_replace($matches[0][$i],"<?php if(empty(\${$matches['name'][$i]})): ?>",$content);
      }
    }
    return $content;
  }

  /**
   * NotEmpty标签
   * @param  string  $content 内容
   * @return string  $content
  **/
  static public function Tag_NotEmpty($content){
    $content = str_ireplace("</notempty>","<?php endif; ?>",$content);
    $pattern = "/<notempty[\s]*name=(['|\"])(?<name>.+)\\1[\s]*>/iUm";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      for ($i=0; $i < $preg; $i++) {
        $content = str_replace($matches[0][$i],"<?php if(!empty(\${$matches['name'][$i]})): ?>",$content);
      }
    }
    return $content;
  }

  /**
   * IF标签
   * @param  string  $content 内容
   * @return string  $content
  **/
  static public function Tag_If($content){
    $content = str_ireplace("</if>","<?php endif; ?>",$content);
    $pattern = "/<if[\s]*condition=['|\"](.+)['|\"][\s]*(?:[\/]*)[\s]*>/iUm";
    $preg = preg_match_all($pattern,$content,$matches_font);
    if($preg!=0){
      for ($i=0; $i < $preg; $i++) {
        $content = str_replace($matches_font[0][$i],"<?php if({$matches_font[1][$i]}): ?>",$content);
      }
    }
    $pattern = "/<else[\s]*condition=['|\"](.+)['|\"][\s]*(?:[\/]*)[\s]*>/iUm";
    $preg = preg_match_all($pattern,$content,$matches_end);
    if($preg!=0){
      for ($i=0; $i < $preg; $i++) {
        $content = str_replace($matches_end[0][$i],"<?php elseif({$matches_end[1][$i]}): ?>",$content);
      }
    }
    return $content;
  }
  /**
  * 魔术标签
  * @param  string $content
  * @return string $content
  **/
  /* 支持模板符号__CSS:index__ */
  static public function MagicTag($content){
    $D = DIRECTORY_SEPARATOR;
    if( WEB_PATH == "/" ){
      $wp = "";
    }else{
      $wp = substr(WEB_PATH ,0 ,mb_strlen(WEB_PATH)-1);
    }
    $tpath = $wp.C("APP_PATH").$D.C("APP")."{$D}View$D";
    $tpath_p = $wp.C("APP_PATH").$D.C("APP")."{$D}View$D"."PUBLIC".$D;
    $pattern = "/__(?<filetype>CSS|JS|IMG|STATIC):(?<filename>.+)__/U";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg != 0){
      for($i=0; $i<count($matches[0]); $i++){
        // 文件名称算法
        $pat_name = "/(?<filename>.+)\?(?<get>.+)$/U";
        $preg_name = preg_match($pat_name,$matches['filename'][$i],$match);
        if( $preg_name != 0 ){
          $filename = $match['filename'];
          $get = "?".$match['get'];
        }else{
          $filename = $matches['filename'][$i];
          $get = "";
        }
        if( in_array($matches['filetype'][$i],array("CSS","JS","IMG")) ){
          $content = str_replace($matches[0][$i],$tpath.C("CONTROLLER")."/".strtolower($matches['filetype'][$i])."/".$filename.C($matches['filetype'][$i]."_EXT").$get,$content);
        }else if( in_array($matches['filetype'][$i],array("STATIC")) ){
          $content = str_replace($matches[0][$i],$tpath_p."static/".$filename.$get,$content);
        }
      }
    }
    return $content;
  }

  /**
   * 清除注释
   * @param  string  $content 内容
   * @param  boolean $lf      是否清除换行
   * @return string  $content
  **/
  static public function ClearComment($content,$lf=false){
    $pattern = "/<!--([\s\S]*)-->/iU";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      for($i=0;$i<count($matches[0]);$i++){
        $content = str_replace($matches[0][$i],"",$content);
      }
    }
    // 清除换行
    if($lf === true){
      $content = str_replace(PHP_EOL,"",$content);
    }
    return $content;
  }

  /**
  * 变量引用
  * @param  string  $content 内容
  * @return string  $content
  **/
  static public function VarReference($content){
    $pattern = "/[\{|\`][\$](.*)[\}|\`]/U"; //兼容 `|{}两种定界符号
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      for($i=0;$i<count($matches[0]);$i++){
        $content = str_replace($matches[0][$i],"<?php if(isset(\${$matches[1][$i]})){echo (\${$matches[1][$i]});} ?>",$content);
      }
    }
    return $content;
  }

  /**
  * 常量引用
  * @param  string  $content 内容
  * @return string  $content
  **/
  static public function ConReference($content){
    $pattern = "/{__(.+)__}/U";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      //去除重复
      $matches[0] = array_unique($matches[0]);
      $matches[1] = array_unique($matches[1]);
      $i = 0;
      $res = "";
      foreach($matches[0] as $match){
        $res[0][$i] = $match;
        $i++;
      }
      $i = 0;
      foreach($matches[1] as $match){
        $res[1][$i] = $match;
        $i++;
      }
      $matches = $res;
      for($i=0;$i<count($matches[0]);$i++){
        $content = str_replace($matches[0][$i],"<?php echo @C(\"{$matches[1][$i]}\"); ?>",$content);
      }
    }
    return $content;
  }

  /**
  * 特殊变量引用
  * @param  string  $content 内容
  * @return string  $content
  **/
  /* 支持模板标签：{!Cookie:xxx}等变量调用 */
  static public function SVarReference($content){
    $pattern = "/{!(.+):(.+)}/U";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      for($i=0;$i<count($matches[0]);$i++){
        $origin = $matches[0][$i];  //源代码
        $content = str_replace($origin,"<?php if(isset(\$_{$matches[1][$i]}['{$matches[2][$i]}'])){echo (\$_{$matches[1][$i]}['{$matches[2][$i]}']);} ?>",$content);
      }
    }
    return $content;
  }

  /**
  * 模板引用
  * @param  string  $content 内容
  * @return string  $content
  **/
  /* 支持模板标签：<include file='PUBLIC-header' type='autoheader' > */
  static public function IncludeTpl($content){
    $D = DIRECTORY_SEPARATOR;
    $P_APP_TPL = ".".C("APP_PATH").$D.C("APP").$D."View".$D;
    $P_APP_PUBLIC_TPL = $P_APP_TPL."PUBLIC".$D;
    $APP = C("APP");
    $METHOD = C("METHOD");
    $CONTROLLER = C("CONTROLLER");
    $JS = C("JS_EXT");
    $CSS = C("CSS_EXT");
    $TPL = C("TPL_EXT");
    $pattern = "/<include file=['|\"](.+)-(.+)['|\"][\s]*(?:type=['|\"]([\s\S]+)['|\"][\s]*|[\s]*)(?:[\/][\s]*|[\s]*)>/iU";
    $pattern = "/<include[\s\S]+>/iU";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg != 0){
      for($i=0; $i < $preg; $i++){
        $origin = $matches[0][$i];  //源代码
        // file
        $pat_file = "/file=(['|\"])(?<file>.+)\\1/iU";
        $preg_file = preg_match($pat_file, $origin, $match);
        if($preg_file == 0){
          continue;
        }else{
          $file = $match['file'];
        }
        // type
        $pat_type = "/type=(['|\"])(?<type>.+)\\1/iU";
        $preg_type = preg_match($pat_type, $origin, $match);
        if( $preg_type == 0 ){
          $type = false; // 默认不载入配套文件
        }else{
          $type = $match['type'];
        }
        // path
        $pat_path = "/path=(['|\"])(?<path>.+)\\1/iU";
        $preg_path = preg_match($pat_path, $origin, $match);
        if( $preg_path == 0 ){
          $path = false; // 当前应用View目录
        }else{
          $path = $match['path'];
        }
        // css-ver
        $pat_cv = "/css-ver=(['|\"])(?<cv>.+)\\1/iU";
        $preg_cv = preg_match($pat_cv, $origin, $match);
        if( $preg_cv == 0 ){
          $cv = false;
        }else{
          $cv = $match['cv'];
        }
        // js-ver
        $pat_jv = "/js-ver=(['|\"])(?<jv>.+)\\1/iU";
        $preg_jv = preg_match($pat_jv, $origin, $match);
        if( $preg_jv == 0 ){
          $jv = false;
        }else{
          $jv = $match['jv'];
        }
        // 兼容4.0及以下版本
        $ext = explode("-",$file);
        if( count($ext) != 1 ){
          $path = $ext[0];
          $file = $ext[1];
        }
        // 公共文件
        if(strtoupper($path) == "PUBLIC"){
          // 判断type
          if( in_array($type, array("auto","autoheader")) ){
            // 自动载入配套js/css
            $version = "";
            // 自动载入版本
            if(C("APP_AUTO_FILE_VERSION") == true){
              $version = "?ver=".C("APP_VERSION");
            }
            // css version
            $cv = (!$cv) ? $version : "?ver=".$cv;
            // js version
            $jv = (!$jv) ? $version : "?ver=".$jv;
            $extra = "";
            if(file_exists($P_APP_TPL.$CONTROLLER."{$D}css{$D}".$METHOD.$CSS)){
              $extra .= "<link rel=\"stylesheet\" href=\"__CSS:{$METHOD}{$cv}__\">";
            }
            if(file_exists($P_APP_TPL.$CONTROLLER."{$D}js{$D}".$METHOD.$JS)){
              $extra .= "<script type=\"text/javascript\" src=\"__JS:{$METHOD}{$jv}__\"></script>";
            }
            $text = \TUnit\Storage\StorageCore::Read($P_APP_PUBLIC_TPL."html{$D}{$file}{$TPL}");
            $content = (!$text) ? $content : str_replace($origin,"{$text}{$extra}",$content);
          }else{
            // 常规输出
            $text = \TUnit\Storage\StorageCore::Read($P_APP_PUBLIC_TPL."html{$D}{$file}{$TPL}");
            $content = (!$text) ? $content : str_replace($origin,$text,$content);
          }
        }else{
          // 自有文件
          $text = \TUnit\Storage\StorageCore::Read($P_APP_TPL."{$CONTROLLER}{$D}{$file}{$TPL}");
          $content = (!$text) ? $content : str_replace($origin,$text,$content);
        }
      }
    }
    return $content;
  }

  /**
  * 路径生成函数
  * @param  string $content 内容
  * @return string $content 内容
  **/
  static public function CreateURL($content){
    $pattern = "/{:U\(['|\"](.*)['|\"]\)}/iU";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      // 去除重复
      $matches[0] = array_reverse($matches[0]);
      $matches[1] = array_reverse($matches[1]);
      for($i=0;$i<count($matches[0]);$i++){
        $origin = $matches[0][$i];
        $paths = $matches[1][$i];
        $content = str_replace($origin,U($paths),$content);
      }
    }
    return $content;
  }

  /**
  * 非解析区块
  * @param  string $content 内容
  * @return string $content 内容
  **/
  static public function ProtectContent($content){
    $pattern = "/<Protected>([\s\S]*)<\/Protected>/iU";
    $preg = preg_match_all($pattern,$content,$matches);
    if($preg!=0){
      for($i=0;$i<count($matches[0]);$i++){
        $origin = $matches[0][$i];
        $text = $matches[1][$i];
        $text = self::Escaped($text); //转义
        $content = str_replace($origin,$text,$content);
      }
    }
    return $content;
  }

  /**
  * 特殊字符转义
  * @param  string $content 内容
  * @return string $content 内容
  **/
  static public function Escaped($content){
    $content = str_replace("<","&lt;",$content);
    $content = str_replace(">","&gt;",$content);
    return $content;
  }

}
?>
