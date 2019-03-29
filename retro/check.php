<?php
header( 'Content-Type:text/html;charset=utf-8 ');
//echo "{$_SERVER['DOCUMENT_ROOT']},{$_SERVER['PHP_SELF']}";

$cfgQueueRelPath = "checkQueue";
$cfgSprintXlsFile = "check_all_#SprintId.txt";

$scriptAbsPath = get_scriptAbsPath();//D:Program FilesXAMPPhtdocs/htdocs/kanboard/retro

$saveFilePath = "{$scriptAbsPath}/{$cfgQueueRelPath}";//retro/checkQueue
init_sprintXlsName();

$pageMethod=get_safePost('method');
$scrumFeedback = new ScrumFeedback();

if(file_get_contents("{$saveFilePath}/{$cfgSprintXlsFile}")){
    $content = trim(file_get_contents("{$saveFilePath}/{$cfgSprintXlsFile}"));
    $scrumFeedback->iterSuggest = $content;
}

if($pageMethod == "submitSuggest") {
    save_FBInfo();
}

if($pageMethod == "showSuggest") {
    read_FBInfo();
}

class ScrumFeedback {
	public $sprintId = "";
    public $iterSuggest = "";
    public $feedBackContents = Array();

	function __construct() {
		$this->sprintId	= calc_sprintId();
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
	$sprintId = date("Ymd");
	return $sprintId;
}

function get_scriptName(){
	$scriptName=substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1);

	return $scriptName;
}

function get_scriptAbsPath() {
	$absPath = $_SERVER['DOCUMENT_ROOT'];//当前运行脚本所在的文档根目录
	$relPath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));//PHP_SELF正在执行脚本的文件名
    $absPath = "{$absPath}{$relPath}";

	return $absPath;
}

function init_sprintXlsName() {
	global $cfgSprintXlsFile;
	
	$cfgSprintXlsFile = str_replace("#SprintId", calc_sprintId(), $cfgSprintXlsFile);
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

function save_FBInfo() {
	global $_POST;
	global $scrumFeedback;
	global $saveFilePath;             //retro/checkQueue
    global $cfgSprintXlsFile;         //check_all_#SprintId.txt;
    $scrumFeedback->iterSuggest=$_POST['suggest'];
    $content = str_replace("\n",'',$scrumFeedback->iterSuggest);
    $content = str_replace("\r",'',$content);
    $content = str_replace(" ",'',$content);
    if ($content == "") {
        return;
    }

	$saveContent = "{$scrumFeedback->iterSuggest}\n\n";
	file_put_contents("{$saveFilePath}/{$cfgSprintXlsFile}",	$saveContent);
}

function read_FBInfo() {
    global $saveFilePath;         //retro/checkQueue
    global $cfgSprintXlsFile;     //check_all_#SprintId.txt;
    global $scrumFeedback;

    $fileName = $cfgSprintXlsFile;
    $content = trim(file_get_contents("{$saveFilePath}/{$fileName}"));
    $content = str_replace("\n\n",",",$content);
    $content = str_replace("\r\n",'<br/>',$content);
    $currDate = date("Ymd");

    $date = array();
    $msg =  array();
    $date = array($currDate);
    $msg =  array($content);
    for($x = 1;$x <=27; $x++)
    {
        $currDate = date("Ymd",(strtotime($currDate) - 3600*24));
        array_push($date,$currDate);
        $fileName = "check_all_" . $currDate . ".txt";
        if(filesize("{$saveFilePath}/{$fileName}") !== 0)
        {
            $content = trim(file_get_contents("{$saveFilePath}/{$fileName}"));
            $content = str_replace("\n\n",",",$content);
            $content = str_replace("\r\n",'<br/>',$content);
        }
        else
        {
            $content = "";
        }
        array_push($msg,$content);
    }
    $scrumFeedback->feedBackContents = array_combine($date,$msg);
}

?> 
 
<html>
<head>
	<title>信·敏捷规范考核</title>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0,maximum-scale=1.5, user-scalable=yes"/>
	<style type="text/css">
		table.gridtable {
			width:1015px;
			font-family: 微软雅黑; 
			font-size:22px;
			color:#333333;  
			border-width: 1px;  
			border-color: #666666;  
			border-collapse: collapse;  
		}  
		table.gridtable th {  
			font-family: 微软雅黑;  
			font-size:28px;
			border-width: 1px;  
			padding: 8px;  
			border-style: solid;  
			border-color: #666666;  
			background-color: #fcfcfc;  
		}
		table.gridtable td {  
			font-family: 微软雅黑; 
			font-size:20px;
			border-width: 1px;  
			padding: 8px;  
			border-style: solid;  
			border-color: #666666;  
			background-color: #ffffff;  
		}
	</style>  
	<script type="text/javascript">
	var theForm = null;
	var theInputObjBgnVal = null;
	var theFormAction = "<?php echo get_scriptName()?>";
	
	window.onload = doInitForm;
	
	function doInitForm() {
		theForm = document.scrumForm;
		if(!theForm) alert("1111");
		if(!theForm.elements) alert("1122");
	}


	function hasChineseChar(inputVal){	 
		var reg = new RegExp("[\\u4E00-\\u9FFF]+","g");
		if(reg.test(inputVal)) {   
			return true;
		} else {
		   return false;
		}
	}

	function doReset(confirmFlag) {
		if(confirmFlag && confirm("您确定要清空所有已输入内容吗？")) {
            theForm.reset();
            theForm.elements["suggest"].value = "";
		}
        return false;
	}
	
	function doSubmit() {
		theForm.action = theFormAction;
		theForm.elements["method"].value="submitSuggest";
		document.all("editSubmitBtn").disabled = true;
		theForm.submit();
	}

    function doShow() {
        theForm.action = theFormAction;
        theForm.elements["method"].value="showSuggest";
        document.all("editSubmitBtn").disabled = true;
        theForm.submit();
    }
	
	function doModifySubmit() {
		document.all("FeedbackViewObj").style.display = "none";
		document.all("FeedbackEditObj").style.display = "";
		doInitForm();
	}
	</script>
</head>

<body>
<?php
if($pageMethod == "submitSuggest") {
?>
	<div id="FeedbackViewObj">
	<center>
		<h1 style="text-align:center">恭喜，您的反馈已成功提交!</h1>
		<form name="viewForm" method="post">
		<br/>
			<input type="button" value="修改再录" onclick="doModifySubmit();"/>
		<br/>
        </form>

        <hr size="1" color="blue" width="1015px">
        <small>
            <input type="button" value="点击查看最近四周所有反馈" onclick="doShow();"/>
            <br/>
        </small>
        <br/>
	</center>
	</div>
<?php
}
?>

<?php
if($pageMethod == "showSuggest") {
?>
    <div id="FeedbackViewObj">
        <center>
            <table border="0" align="center" class="gridtable">
                <h1 style="text-align:center">反馈结果</h1>
                <?php foreach($scrumFeedback->feedBackContents as $key => $content) { ?>
                    <tr>
                        <th align="center" width="40"> <?php echo $key  ?></th> <td colspan="3" align="left"> <?php echo $content ?></td>
                    </tr>
                <?php } ?>
            </table>
            <br/><br/>
            <input type="button" value="返回录入" onclick="doModifySubmit();doReset(false);"/>
        </center>
    </div>
<?php
}
?>
	<div id="FeedbackEditObj" style="display:<?php if($pageMethod == "submitSuggest"  or $pageMethod == "showSuggest") echo "none"; ?>">
	<center>
		<b style="font-size:40px;font-family:微软雅黑">信·敏捷规范考核</b>
		<br/>
		<br/>
	</center>
	<form name="scrumForm" method="post">
	<input type="hidden" name="method" value="" />
	<table border="0" align="center" class="gridtable">
		<tr>
			<th colspan="2" align="left">反馈内容:</th>
		</tr>
		<tr>
			<td colspan="3" align="left">
                <textarea  name="suggest" style="width:1000px;height:300px;font-size:20px;padding:20px" value="<?=$scrumFeedback->iterSuggest?>"><?=$scrumFeedback->iterSuggest?></textarea>
            </td>
		</tr>
	</table>
	<table border="0" align="center">
		<tr>
			<td align="center" valign="bottom" colspan="3" height="50">
				<input type="button" value="提交" id="editSubmitBtn" onclick="doSubmit();"/>
				&nbsp;&nbsp;
				<input type="button" value="清空" onclick="doReset(true);"/>
			</td>
		</tr>
	</table>
    </form>

    <center>
        <hr size="1" color="blue" width="1015px">
        <small>
            <input type="button" value="点击查看最近四周所有反馈" onclick="doShow();"/>
            <br/>
        </small>
        <br/>
    </center>
	</div>

</body>
</html>