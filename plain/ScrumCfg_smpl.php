<?php
$_todayDate = date("Ymd", time());

$cfgQueueRelPath = "queue";
$cfgKanbanUrl = "http://kanban.xxx.com";
$cfgFullDayXlsFile = "{$_todayDate}_task_user_all.xls";

$cfgJsonRpcCallUserName = "jsonrpc";
$cfgJsonRpcCallPassword = "78b7bb385eedec22067c996efftfdcc67551a2cc5d5df0a4541d122ab5e7";

$cfgCoderIdArr = array(
		"陈谷子" => 11,
		"兰之玛" => 12
);
$cfgTesterIdArr = array(
		"张山雨" => 21,
		"李志石" => 22
);

$cfgTypeInfoArr = array(
		"优化"   => array("1", "J", "研发自发的针对产品的代码层级的修改"),
		"需求"   => array("2", "-", "现场发起的业务需求"),
		"缺陷"   => array("3", "i", "由QA提出的产品bug"),
		"问题"   => array("4", "s", "现场发起的针对某个业务的疑问"),
		"故障"   => array("5", "r", "现场发起的，生产环境的故障处理"),
		"改进"   => array("9", "@", "以提高效率为目的的过程改进，无产品代码改动"),
		"协助"   => array("8", "_", "协助PSO，其他部门人员的咨询，指导"),
		"学习"   => array("7", "Z", "业务学习、技术学习")
);

$cfgCoderProgArr = array("待开发", "开发中");

$cfgProgIdArr = array(
		"待开发" => 1,
		"待测试" => 1,
		"开发中" => 2,
		"测试中" => 3,
		"发布中" => 4,
		"已完成" => 5
);

$cfgProvIdArr = array(
		"共性"   => 1,
		"北京"   => 2,
		"辽宁"   => 3,
		"河南"   => 4,
		"内蒙"   => 5,
		"南基"   => 6,
		"贵州"   => 7,
		"总部"   => 8
);

$cfgTaskSizeArr = array(
		"Zero(0点)"	=>	"Zero (0点)",
		"XXS(½点)"	=>	"XXS  (½点)",
		"XS(1点)"	=>	"XS  (1点)",
		"S(2点)"	=>	"S  (2点)",
		"M(3点)"	=>	"M  (3点)",
		"L(5点)"	=>	"L  (5点)",
		"XL(8点)"	=>	"XL  (8点)",
		"XXL(13点)"	=>	"XXL  (13点)",
		"XXXL(20点)"=>	"XXXL  (20点)"
);
?>