<?php
namespace Controller\Behavior;
use Controller\Common\BehaviorCommon;
class indexBehavior extends BehaviorCommon{
  /*BehaviorCommon函数可以在这里直接调用*/
  public function _index_index(){
    $this->showContent(getPresetTpl("TUnit/TCEngine"));
  }
}
?>
