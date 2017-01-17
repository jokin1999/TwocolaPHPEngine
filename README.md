#TCE框架引擎

`Author: Jokin`

`联系方式：QQ 327928971 | EMAIL 327928971@qq.com`

---

<!-- 官方网站：[两杯可乐网](http://www.twocola.com) -->

<!-- 官方网站：[TCE引擎（公版）](http://www.twocola.top) -->

<!-- 官方网站：[TCE引擎（定制）未上线](http://www.twocola.top/cm) -->

<!-- 加入我们：[JoinUs](http://www.twocola.com/messenger/index/join) -->

---

###更新日志

#####Ver 3.1 [大规模升级]

> Notice:框架使用全新架构，3.0及以下版本不可直接进行升级。

[优化] 优化框架运行流程，框架所需类库全面实行懒加载。

[优化] 优化模板引擎，模板标签不再区分大小写。

[优化] 优化`路径解析引擎`，简化解析流程，增加运行效率。

[修改 | 重要] 设置项`SYSTEM_DEFAULT_MODULE`在全局范围内修改为`APP_DEFAULT`。

[修改 | 重要] 设置项`APP_MODULE`在全局范围内修改为`APP_GENERATE`。

[完善] `报错系统`功能。

[增加] `模板引擎`新增模板自定义魔法变量方法。

[优化] `模板引擎`不区分模板（魔法）标签大小写。

[优化] 对部分代码进行规范化，未完成规范的部分将在后续版本进行修改及优化。

[增加 | 优化] `OneAsMuiltiple`（OAM/窝群）功能。

---

#####Ver 3.0 [优化升级]

1、[修复] 设置项`APP_API_PARA`设置为空时，无法进入API模式的BUG。

2、[屏蔽] `SUBDOMAIN_BINDING`功能，重新启用可以在twocola目录下Layer文件夹中的PathInfo.php解除代码屏蔽。

3、[增加 | 概念] `框架自主升级系统`功能，可将升级文件置入服务器中，根据提示进行自动框架升级（本次仅提供升级接口，未内置升级文件）。

4、[增加] `报错系统`功能，使框架拥有完整的报错机制。

---
