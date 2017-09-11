<?php
header( 'Content-Type:text/html;charset=utf-8 ');

require 'ScrumApp.php';
require 'ScrumCfg.php';

//echo "{$_SERVER['DOCUMENT_ROOT']},{$_SERVER['PHP_SELF']}";
$scriptAbsPath = get_scriptAbsPath();
$saveFilePath = "{$scriptAbsPath}/{$cfgQueueRelPath}";

$pageMethod=get_safePost('method');

$sprintTask = new SprintTask();

if($pageMethod == "submitTask") {
	save_taskInfo();
	post_kanban();
} 

class SprintTask {
	public $sprintId="";
	
	public $taskId="";
	public $taskProv="";
	public $taskType=""; 
	public $taskSize=""; 
	public $taskName="";
	public $taskProgress="";

	public $codePlanBeginDate = "";
	public $codePlanEndDate = "";

	public $testPlanBeginDate = "";
	public $testPlanEndDate = "";

	public $taskCoder = "";
	public $codeRealBeginDate = "";
	public $codeRealEndDate = "";

	public $taskTester = "";
	public $testRealBeginDate = "";
	public $testRealEndDate = "";
	
	public $operUser = "";
	public $onceAgainFlag = "";
	public $nextSprintFlag = "";
	
	function __construct() {
		$this->sprintId	= get_fixLenStr(calc_sprintId());
		$this->taskId	= get_fixLenStr(calc_taskId(false));
		
		$this->operUser= get_safeCookie("userName");
	}
}

function get_safePost($str){
	$val = !empty($_POST[$str]) ? $_POST[$str] : "";

	return $val;
}
function get_safeCookie($str){
	global $_COOKIE;
	$val = !empty($_COOKIE[$str]) ? $_COOKIE[$str] : "";

	return $val;
}

function calc_sprintId() {
	global $appSprintId;
	global $appTaskId;
	global $appNextSprintTaskId;
	
	$tmToday=strtotime(date("Y-m-d"));
	$iToday=intval(date("Ymd"));
	
	$tmBaseDay = strtotime("2017-07-31");
	$tmBaseDay2nd = strtotime("2017-09-01");
	$iBaseDay2nd = 20170901;
	
	$passDays=($tmBaseDay2nd-$tmBaseDay)/3600/24;
	$sprintId = floor($passDays/14)+1;
	
	$sprintId += ((floor($iToday/10000) - floor($iBaseDay2nd/10000)) * 12 + floor($iToday/100 - $iBaseDay2nd/100))*2;
	
	if(($iToday % 100)>15) {
		$sprintId += 1;
	}
	
	if($sprintId != $appSprintId) {
		$appSprintId = $sprintId;
		$appTaskId = $appNextSprintTaskId;
		$appNextSprintTaskId = 1;
		save_appConfFile();
	}
	
	return $sprintId;
}

function calc_taskId($increase) {
	global $appTaskId;
	$taskId = $appTaskId;
	
	if($increase) {
		$appTaskId ++;
		save_appConfFile();
	}
	
	return $taskId;
}

function calc_nextSprintTaskId($increase) {
	global $appNextSprintTaskId;
	$taskId = $appNextSprintTaskId;
	
	if($increase) {
		$appNextSprintTaskId ++;
		save_appConfFile();
	}
	
	return $taskId;
}

function save_appConfFile() {
	global $scriptAbsPath;
	
	global $appSprintId;
	global $appTaskId;
	global $appNextSprintTaskId;
	
	$scrumAppContent = "<?php\n\$appSprintId={$appSprintId};\n\$appTaskId={$appTaskId};\n\$appNextSprintTaskId={$appNextSprintTaskId};\n?>";
	
	file_put_contents("{$scriptAbsPath}/ScrumApp.php", $scrumAppContent);
}

function get_fixLenStr($intVal) {
	if($intVal < 10) {
		return "0{$intVal}";
	} else if ($intVal >= 100) {
		$modVal = $intVal%10;
		if ($intVal >= 100 && $intVal<110) {
			return "A{$modVal}";
		} else if ($intVal >= 110 && $intVal<120) {
			return "B{$modVal}";
		} else if ($intVal >= 120 && $intVal<130) {
			return "C{$modVal}";
		} else if ($intVal >= 130 && $intVal<140) {
			return "D{$modVal}";
		} else if ($intVal >= 140 && $intVal<150) {
			return "E{$modVal}";
		} else if ($intVal >= 150 && $intVal<160) { 
			return "F{$modVal}";
		} else if ($intVal >= 160 && $intVal<170) { 
			return "G{$modVal}";
		} else if ($intVal >= 170 && $intVal<180) { 
			return "H{$modVal}";
		} else if ($intVal >= 180 && $intVal<190) { 
			return "I{$modVal}";
		} else if ($intVal >= 190 && $intVal<200) { 
			return "J{$modVal}";
		} else {
			return "?{$intVal}?";
		}
	} else {
		return "{$intVal}";
	}
}

function get_scriptName(){
	$scriptName=substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1);

	return $scriptName;
}

function get_scriptAbsPath() {
	$absPath = $_SERVER['DOCUMENT_ROOT'];
	$relPath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
	$absPath = "{$absPath}{$relPath}";
	
	return $absPath;
}

function gen_arrSelectOption($optionArr) {
	$strHtml = "";
	foreach($optionArr as $key => $val) {
		$strHtml .= "\t\t\t\t\t<option value='{$key}'>{$val}</option>\n";
	}
	
	return $strHtml;
}

function gen_arrKeySelectOption($optionArr) {
	$strHtml = "";
	foreach($optionArr as $key => $val) {
		$strHtml .= "\t\t\t\t\t<option>".$key."</option>\n";
	}
	
	return $strHtml;
}

function join_taskTypeHint($typeInfoArr) {
	$strHtml = "";
	foreach($typeInfoArr as $key => $valArr) {
		$strHtml .= "{$key}: {$valArr[2]}\n";
	}
	
	return $strHtml;
	
}

function calc_userId($userName) {
	global $cfgCoderIdArr, $cfgTesterIdArr;
	foreach($cfgCoderIdArr as $key => $val) {
		if($userName == $key) return $val;
	}
	foreach($cfgTesterIdArr as $key => $val) {
		if($userName == $key) return $val;
	}
}

function save_taskInfo() {
	global $_POST;
	global $sprintTask;
	
	global $saveFilePath;
	global $cfgProvIdArr;
	global $cfgProgIdArr;
	
	global $cfgFullDayXlsFile;
	
	$sprintTask->sprintId 	= get_fixLenStr(calc_sprintId());
	$sprintTask->taskId 	= get_fixLenStr(calc_taskId(true));
	
	$sprintTask->taskProv=$_POST['prov'];
	$sprintTask->taskType=$_POST['type'];
	$sprintTask->taskName=$_POST['name'];
	$sprintTask->taskSize=$_POST['size'];
	$sprintTask->taskProgress=$_POST['progress'];
	
	$sprintTask->codePlanBeginDate = $_POST['codePlanBeginDate'];
	$sprintTask->codePlanEndDate = $_POST['codePlanEndDate'];
	
	$sprintTask->testPlanBeginDate = $_POST['testPlanBeginDate'];
	$sprintTask->testPlanEndDate = $_POST['testPlanEndDate'];
	
	$sprintTask->taskCoder = $_POST['coder'];
	$sprintTask->codeRealBeginDate = $_POST['codeRealBeginDate'];
	$sprintTask->codeRealEndDate = $_POST['codeRealEndDate'];
	
	$sprintTask->taskTester = $_POST['tester'];
	$sprintTask->testRealBeginDate = $_POST['testRealBeginDate'];
	$sprintTask->testRealEndDate = $_POST['testRealEndDate'];
	
	$postUser = $_POST['userName'];
	if($postUser != $sprintTask->operUser) {
		$sprintTask->operUser = $postUser;
		setcookie("userName", $postUser, time()+99*365*24*3600);
	}
	$sprintTask->onceAgainFlag = get_safePost("onceAgainFlag");
	$sprintTask->nextSprintFlag = get_safePost("nextSprintFlag");
	
	$saveDateTime = date("Ymd_His", time());
	
	$postUserId = calc_userId($postUser);
	$saveFileName = "{$saveDateTime}_task_user_{$postUserId}.csv";
	
	$saveContent = "{$sprintTask->sprintId}\t{$sprintTask->taskId}\t{$sprintTask->taskProv}";
	$saveContent = "{$saveContent}\t{$sprintTask->taskType}\t{$sprintTask->taskName}\t{$sprintTask->taskProgress}";
	$saveContent = "{$saveContent}\t{$sprintTask->operUser}\t{$sprintTask->taskSize}";
	$saveContent = "{$saveContent}\t{$sprintTask->codePlanBeginDate}\t{$sprintTask->codePlanEndDate}"; 
	$saveContent = "{$saveContent}\t{$sprintTask->testPlanBeginDate}\t{$sprintTask->testPlanEndDate}";
	$saveContent = "{$saveContent}\t{$sprintTask->taskCoder}\t{$sprintTask->codeRealBeginDate}\t{$sprintTask->codeRealEndDate}";
	$saveContent = "{$saveContent}\t{$sprintTask->taskTester}\t{$sprintTask->testRealBeginDate}\t{$sprintTask->testRealEndDate}\n";
	
	//$saveContent = iconv('UTF-8', 'GBK', $saveContent);

	file_put_contents("{$saveFilePath}/ins/{$saveFileName}", $saveContent);
	file_put_contents("{$saveFilePath}/{$cfgFullDayXlsFile}",	$saveContent, FILE_APPEND);
}

/*
curl -u "cfgJsonRpcCallUserName:cfgJsonRpcCallPassword" 
	-d '{
			"jsonrpc": "2.0", "method": "createTask", "id": 1300,"params": {
				"owner_id": 14, "creator_id": 0, "date_due": "", "description": "", "category_id": 1, "score": 0, "title": "Test", "project_id": 1, "color_id": "red", "column_id": 3, "recurrence_status": 0, "recurrence_trigger": 0, "recurrence_factor": 0, "recurrence_timeframe": 0, "swimlane_id": 2
		}
	}' http://kanban.xxx.com/jsonrpc.php -v
*/

function post_kanban() {
	global $sprintTask;
	global $cfgProvIdArr;
	global $cfgProgIdArr;
	global $cfgTypeInfoArr;
	global $cfgKanbanUrl;
	global $cfgCoderProgArr;
	global $cfgJsonRpcCallUserName;
	global $cfgJsonRpcCallPassword;
	
	$url = "{$cfgKanbanUrl}/jsonrpc.php";
	$userpwd = "{$cfgJsonRpcCallUserName}:{$cfgJsonRpcCallPassword}";
	
	$data = '{"jsonrpc": "2.0", "method": "createTask", "params": {"creator_id": 10, "project_id": 1, ';

	$owner = "";
	
	if (in_array($sprintTask->taskProgress, $cfgCoderProgArr)) {
		$owner = $sprintTask->taskCoder;
	} else {
		if ($sprintTask->taskTester != "") {
			$owner = $sprintTask->taskTester;
		} else {
			$owner = $sprintTask->taskCoder;
		}
	}
	if ($owner != "") {
		$data = $data . '"owner_id": ' . calc_userId($owner) . ', ';
	}
	
	$data = $data . '"category_id": ' . $cfgTypeInfoArr[$sprintTask->taskType][0]. ', ';
	$titleName =  $sprintTask->taskName. '(№' . $sprintTask->sprintId . $sprintTask->taskId . ')';
	//$titleName = iconv('GBK', 'UTF-8', $titleName);
	$data = $data . '"title": "' . $titleName . '", ';
	$data = $data . '"column_id": ' . $cfgProgIdArr[$sprintTask->taskProgress] . ', ';
	$data = $data . '"swimlane_id": ' . $cfgProvIdArr[$sprintTask->taskProv] .'}}';
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	// post数据
	curl_setopt($ch, CURLOPT_POST, 1);
	// post的变量
	curl_setopt($ch, CURLOPT_USERPWD,$userpwd);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$output = curl_exec($ch);
	curl_close($ch);

	//print_r($output);		//打印获得的数据
}
?> 
 
<html>
<head>
	<title>敏捷帐处任务信息</title>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0,maximum-scale=1.5, user-scalable=yes"/>
	<style type="text/css">
		table.gridtable {
			width:325px;
			font-family: 微软雅黑; 
			font-size:12px;  
			color:#333333;  
			border-width: 1px;  
			border-color: #666666;  
			border-collapse: collapse;  
		}  
		table.gridtable th {  
			font-family: 微软雅黑;  
			font-size:12px;  
			border-width: 1px;  
			padding: 8px;  
			border-style: solid;  
			border-color: #666666;  
			background-color: #fcfcfc;  
		}
		table.gridtable td {  
			font-family: 微软雅黑; 
			font-size:12px;  
			border-width: 1px;  
			padding: 8px;  
			border-style: solid;  
			border-color: #666666;  
			background-color: #ffffff;  
		}
		
		table.subtable {  
			font-family: 微软雅黑; 
			font-size:12px;  
			color:#333333;  
			width:300px;
			border-width: 0px;  
			border-color: #666666;  
			border-collapse: collapse;  
		}  
		table.subtable th {  
			font-family: 微软雅黑;  
			font-size:12px;  
			border-width: 0px;  
			padding: 0px;  
			border-style: solid;  
			border-color: #666666;  
			background-color: #fcfcfc;  
		}
		table.subtable td {  
			font-family: 微软雅黑; 
			font-size:12px;  
			border-width: 0px;  
			padding: 0px;  
			border-style: solid;  
			border-color: #666666;  
			background-color: #ffffff;  
		}
	</style>  
	<script type="text/javascript">
	var theForm = null;
	var theInputObjBgnVal = null;
	var theFormAction = "<?=get_scriptName()?>";
	
	window.onload = doInitForm;
	
	function doInitForm() {
		theForm = document.scrumForm;
		if(!theForm) alert("1111");
		if(!theForm.elements) alert("1122");
		if(theForm && theForm.elements) {
			theForm.elements["prov"].focus();
			
			initSelect(theForm.elements["prov"]);
			initSelect(theForm.elements["type"]);
			initSelect(theForm.elements["size"]);
			initSelect(theForm.elements["progress"]);
			initSelect(theForm.elements["coder"]);
			initSelect(theForm.elements["tester"]);
		}
	}
	
	function initSelect(selectObj) {
		for(i=0; i<selectObj.options.length; i++) {
			if(selectObj.options[i].value == selectObj.getAttribute("_value")) {
				selectObj.options[i].selected = true;
			}
		}
	}
	
	function onKeyDown() {
		if(event.srcElement.tagName == "SELECT") {
			if(event.keyCode == 13) {
				event.cancleBubble = true;
				event.returnValue  = false;
				return false;
			}
		}
		
		return true;
	}
	
	function onKeyBgnCheckMM(inputObj) {
		theInputObjBgnVal = inputObj.value;
	}
	
	function onKeyEndCheckMM(inputObj) {
		if(inputObj.value<1 || inputObj.value>12) {
			inputObj.value = theInputObjBgnVal;
		}
	}
	
	function onKeyBgnCheckDD(inputObj) {
		theInputObjBgnVal = inputObj.value;
		
	}
	
	function onKeyEndCheckDD(inputObj) {
		if(inputObj.value<1 || inputObj.value>31) {
			inputObj.value = theInputObjBgnVal;
		}
	}
	
	function jumpInput(prevInputName, nextInputName) {
		if(event.srcElement.tagName == "SELECT") { 
			if(event.keyCode == 13) {
				theForm.elements[nextInputName].focus();
			}
		} else {
			if(event.keyCode == 38) {
				theForm.elements[prevInputName].focus();
			} else if(event.keyCode == 13 || event.keyCode==40) {
				theForm.elements[nextInputName].focus();
			}
		}
	}
	function hasChineseChar(inputVal){	 
		var reg = new RegExp("[\\u4E00-\\u9FFF]+","g");
		if(reg.test(inputVal)) {   
			return true;
		} else {
		   return false;
		}
	}
	
	function isValidDate(inputDate) {
		return true;
	}
	
	function isValidUserName(inputStr) {
		if(inputStr=="") return false;
		
		var coderInputObj = theForm.elements["coder"];
		for(var i=0; i<coderInputObj.options.length; i++) {
			if(inputStr == coderInputObj.options[i].value) return true;
		}
		
		var testerInputObj = theForm.elements["tester"];
		for(var i=0; i<testerInputObj.options.length; i++) {
			if(inputStr == testerInputObj.options[i].value) return true;
		}
		
		return false;
	}
	
	function inputUserName() {
		userName = window.prompt("请输入您自己的中文全名", theForm.elements["userName"].value);
		
		while(userName != null && (userName == "" || !isValidUserName(userName))) {
			userName = window.prompt("别闹了，请输入您自己的中文全名", theForm.elements["userName"].value);
		}
		
		if(userName != null) {
			theForm.elements["userName"].value = userName;
			document.all("operUserNameObj").innerHTML = userName;
			return true;
		}
		
		return false;
	}
	
	function doReset() {
		if(confirm("您确定要清空所有已输入数据吗？")) {
			theForm.reset();
		}
	}
	
	function doSubmit() {
		if(theForm.elements["prov"].value == "") {
			alert("来源省份不能为空");
			return false;
		}
		if(theForm.elements["type"].value == "") {
			alert("任务分类不能为空");
			return false;
		}
		if(theForm.elements["name"].value == "") {
			alert("任务名称不能为空");
			return false;
		}
		if(theForm.elements["size"].value == "") {
			alert("预估点数不能为空");
			return false;
		} 
		
		if(theForm.elements["progress"].value == "") {
			alert("任务进展不能为空");
			return false;
		} 
		
		if(!isValidDate(theForm.elements["codePlanBeginDate"])) {
			alert("计划开发开始日期不正确");
			return false;
		}
		if(!isValidDate(theForm.elements["codePlanEndDate"])) {
			alert("计划开发完成日期不正确");
			return false;
		}
		if(!isValidDate(theForm.elements["testPlanBeginDate"])) {
			alert("计划测试开始日期不正确");
			return false;
		}
		if(!isValidDate(theForm.elements["testPlanEndDate"])) {
			alert("计划测试完成日期不正确");
			return false;
		}
		
		if(!isValidDate(theForm.elements["codeRealBeginDate"])) {
			alert("开发实际日期不正确");
			return false;
		}
		if(!isValidDate(theForm.elements["testRealBeginDate"])) {
			alert("测试实际日期不正确");
			return false;
		}
		
		if(theForm.elements["userName"].value == "" 
				|| !isValidUserName(theForm.elements["userName"].value)) {
			if(!inputUserName()) return false;
		}
		
		theForm.action = theFormAction;
		theForm.elements["method"].value="submitTask";
		document.getElementByIdx("editSubmitBtn").disabled = true;
		theForm.submit();
	}
	
	function doModifySubmit() {
		document.all("taskViewObj").style.display = "none";
		document.all("taskEditObj").style.display = "";
		doInitForm();
	}
	</script>
</head>

<body>
<?php
if($pageMethod == "submitTask") {
?>
	<div id="taskViewObj">
	<center>
		<h2 style="text-align:center">恭喜，您的信息已成功提交!</h2>
		<form name="viewForm" method="post">
		<table border="0" align="center" class="gridtable">
			<tr>
				<td colspan="3">
					<table border="0" align="center" class="subtable">
						<tr>
							<td align="left" width="80"><big><b>№<?="{$sprintTask->sprintId}{$sprintTask->taskId}" ?></b></big></td>
							<td align="center" width="80">
								<span style="font-size:15px;font-family:webdings;"><?=$cfgTypeInfoArr[$sprintTask->taskType][1]?></span>
								<span style="font-size:15px;font-family:微软雅黑;"><b><?=$sprintTask->taskType ?></b></span>
								<span style="font-size:15px;font-family:webdings;"><?=$cfgTypeInfoArr[$sprintTask->taskType][1] ?></span>
							</td>
							<td align="right" width="80"><big><b><?="$sprintTask->taskProv" ?></b></big></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
			  <th width="80">角色</th>
			  <th width="80">计划开始</th>
			  <th width="80">计划结束</th>
			</tr>
			<tr>
			  <th width="80"><big>开发</big></td>
			  <td align="center" width="80"><?="{$sprintTask->codePlanBeginDate}"?></td>
				<td align="center" width="80"><?="{$sprintTask->codePlanEndDate}"?></td>
			</tr>
			<tr>
			  <th width="80"><big>测试<big></td>
			  <td align="center" width="80"><?php echo "$sprintTask->testPlanBeginDate" ?></td>
			  <td align="center" width="80"><?php echo "$sprintTask->testPlanEndDate" ?></td>
			</tr>
			<tr>
				<td colspan="3" align="center" width="240">
					<table border="0" align="center" class="subtable">
						<tr>
							<td width="5" height="150">&nbsp;</td>
							<td align="center" valign="middle" colspan="3">
								<b>
								<span style="font-family:微软雅黑; font-size:40px;"><?php echo "{$sprintTask->taskName}" ?></span>
								</b>
							</td>
							<td width="5">&nbsp;</td>
						</tr>
						<tr>
						  <td align="left" colspan="2" width="80">预估点数:<?php echo "$sprintTask->taskSize" ?></td>
						  <td align="center" width="80"><b>&nbsp;</b></td>
						  <td align="right" colspan="2" width="80">录_入_人:<?php echo "$sprintTask->operUser" ?></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
			  <th width="80">人员</th>
			  <th width="80">实际开始</th>
			  <th width="80">实际结束</th>
			</tr>
			<tr>
			  <td align="center" width="80"><big><b><?php echo "$sprintTask->taskCoder" ?></b></big>&nbsp;</td>
			  <td align="center" width="80"><?php echo "$sprintTask->codeRealBeginDate" ?>&nbsp;</td>
			  <td align="center" width="80"><?php echo "$sprintTask->codeRealEndDate" ?>&nbsp;</td>
			</tr
			<tr>
			  <td align="center" width="80"><big><b><?php echo "$sprintTask->taskTester" ?></b></big>&nbsp;</td>
			  <td align="center" width="80"><?php echo "$sprintTask->testRealBeginDate" ?>&nbsp;</td>
			  <td align="center" width="80"><?php echo "$sprintTask->testRealEndDate" ?>&nbsp;</td>
			</tr>
			<tr>
			  <td align="center" width="80">&nbsp;</td>
			  <td align="center" width="80">&nbsp;</td>
			  <td align="center" width="80">&nbsp;</td>
			</tr>
		</table> 
		<br/>
			<input type="button" value="全新再录" onclick="theForm.reset(); doModifySubmit();"/>
			&nbsp;&nbsp;
			<input type="button" value="修改再录" onclick="doModifySubmit();"/>
		<br/>
	</center>
	</form>
	</div>
<?php
}

$sprintTask->taskId = get_fixLenStr(calc_taskId(false));
?> 
	<div id="taskEditObj" style="display:<?php if($pageMethod == "submitTask") echo "none"; ?>">
	<center>
		<b style="font-size:18px;font-family:微软雅黑">敏捷帐处任务单信息</b>
		<br/>
		<br/>
	</center> 
	<form name="scrumForm" method="post">
	<input type="hidden" name="method" value="" />
	<input type="hidden" name="taskId" value="" _value="<?=$sprintTask->taskId?>"/>
	<input type="hidden" name="userName" value="<?php echo "{$sprintTask->operUser}"?>" />
	<table border="0" align="center" class="gridtable">
		<tr>
		  <td colspan="3">
			<table border="0" align="center" class="subtable">
				<tr>
				  <td align="left" width="50">
						<b><span>№<?="{$sprintTask->sprintId}"?></span><span id="taskIdObj"><?="{$sprintTask->taskId}"?></span></b>
						<!--input type="checkbox" name="" value="true"/>PB-->
					</td>
					<td align="center" width="150">
						<!--
						<label><input type="checkbox" name="onceAgainFlag" value="true" <?=($sprintTask->onceAgainFlag=="true"?"checked":"")?>/>重录任务</label>
						<label><input type="checkbox" name="nextSprintFlag" value="true" <?=($sprintTask->nextSprintFlag=="true"?"checked":"")?>/>下个迭代</label>
						-->
					</td>
				  <td align="right" width="70">
						<b>录入</b>:<a href="#" onclick="javascript:inputUserName(); return false;"><b id="operUserNameObj"><?php echo "{$sprintTask->operUser}" ?></b>
						</a>
					</td>
				</tr>
			</table>
		  </td>
		</tr>
		<tr>
			<th colspan="2" align="center" width="160">来源省份:</th>
			<td width="165">
				<select name="prov" style="width:125px" _value="<?=$sprintTask->taskProv?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('testRealBeginDate', 'type');">
					<option></option>
					<?=gen_arrKeySelectOption($cfgProvIdArr)?>
				</select>
				*
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center" title="<?=join_taskTypeHint($cfgTypeInfoArr)?>">任务分类:</th>
			<td>
				<select name="type" style="width:125px" _value="<?=$sprintTask->taskType?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('prov', 'name');">
					<option></option>
					<?=gen_arrKeySelectOption($cfgTypeInfoArr)?>
				</select>
				*
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center">任务名称:</th>
			<th align="left">
				建议20汉字内(最多32)
			</th>
		</tr>
		<tr>
			<td colspan="3" align="left">
				<input type="text" name="name" style="width:292px" value="<?=$sprintTask->taskName?>" onkeyup="jumpInput('type', 'size');"/>
				*
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center">估算大小:</th>
			<td>
				<select name="size" style="width:125px;" _value="<?=$sprintTask->taskSize?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('name', 'progress');">
					<option></option>
					<?=gen_arrSelectOption($cfgTaskSizeArr)?>
				</select>
				*
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center">任务进展:</th>
			<td>
				<select name="progress" style="width:125px" _value="<?=$sprintTask->taskProgress?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('size', 'codePlanBeginDate');">
					<option></option>
					<?=gen_arrKeySelectOption($cfgProgIdArr)?>
				</select>
				*
			</td>
		</tr>
		<tr>
			<th rowspan="2" valign="middle" align="center" width="65">任务计划</th>
			<th align="center" width="70">开发计划:</th>
			<td>
				<input name="codePlanBeginDate" type="date" style="width:125px" value="<?=$sprintTask->codePlanBeginDate?>" 
						onkeydown="onKeyDown()" onkeyup="jumpInput('progress', 'codePlanEndDate');"/>
				&nbsp;
				<input name="codePlanEndDate" type="date" style="width:125px" value="<?=$sprintTask->codePlanEndDate?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('codePlanBeginDate', 'testPlanBeginDate');"/>
			</td>
		</tr>
		<tr>
			<th align="center">测试计划:</th>
			<td>
				<input name="testPlanBeginDate" type="date" style="width:125px" value="<?=$sprintTask->testPlanBeginDate?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('codePlanEndDate', 'testPlanEndDate');"/>
				&nbsp;
				<input name="testPlanEndDate" type="date" style="width:125px" value="<?=$sprintTask->testPlanEndDate?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('testPlanBeginDate', 'coder');"/>
			</td>
		</tr>
		<tr>
			<th rowspan="2" valign="middle" align="center">开_发_侧</th>
			<th align="center">责_任_人:</th>
			<td>
				<select name="coder" style="width:125px" _value="<?=$sprintTask->taskCoder?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('testPlanEndDate', 'codeRealBeginDate');">
					<option></option>
					<?=gen_arrKeySelectOption($cfgCoderIdArr)?>
				</select>
			</td>
		</tr>
		<tr>
			<th align="center">实际日期:</th>
			<td>
				<input name="codeRealBeginDate" type="date" style="width:125px" value="<?=$sprintTask->codeRealBeginDate?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('coder', 'codeRealEndDate');"/>
				&nbsp;
				<input name="codeRealEndDate" type="date" style="width:125px" value="<?=$sprintTask->codeRealEndDate?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('codeRealBeginDate', 'tester');"/>
			</td>
		</tr>
		<tr>
			<th rowspan="2" valign="middle" align="center">测_试_侧</th>
			<th align="center">责_任_人:</th>
			<td>
				
				<select name="tester" style="width:125px" _value="<?=$sprintTask->taskTester?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('codeRealBeginDate', 'testRealBeginDate');">
					<option></option>
					<?=gen_arrKeySelectOption($cfgTesterIdArr)?>
				</select>
			</td>
		</tr>
		<tr>
			<th align="center">实际日期:</th>
			<td>
				<input name="testRealBeginDate" type="date" style="width:125px" value="<?=$sprintTask->testRealBeginDate?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('tester', 'testRealEndDate');"/>
				&nbsp;
				<input name="testRealEndDate" type="date" style="width:125px" value="<?=$sprintTask->testRealEndDate?>"
						onkeydown="onKeyDown()" onkeyup="jumpInput('testRealBeginDate', 'prov');"/>
			</td>
		</tr>
	</table>
	<table border="0" align="center">
		<tr>
			<td align="center" valign="bottom" colspan="3" height="50">
				<input type="button" value="提交" id="editSubmitBtn" onclick="doSubmit();"/>
				&nbsp;&nbsp;
				<input type="button" value="清空" onclick="doReset();"/>
			</td>
		</tr>
	</table>
	</div>
	</form>
	<center>
		<hr size="1" color="blue" width="325px">
		<small>
		打印机在线时间：[09:15,10:00]，[17:30,18:00]
		<br/>
		任务单信息随时可提交，记录到后台 
		<br/>
		<a href="<?="./{$cfgQueueRelPath}/{$cfgFullDayXlsFile}"?>">点击下载当天所有任务单</a>
		<br/>
		</small>
		<br/>
	</center>
</body>
</html>