<?php
$ps_path = __FILE__;
define('PHPSHELL_EOF_MARK','---EOF');
$REGISTERED_FUNCTIONS = array();
$PC = array(
'MOTD' =>
"Thanks for using PHP-Shell.\n".
"Consider taking a look at the phpshell-config.php\n".
"Run the build script to make a condensed single-file version of PHPShell.\n\n".
'More info: <a href="https://github.com/EJTH/php-shell">https://github.com/EJTH/php-shell</a>',
'PHP_PATH' => '',
'MODE' => 'interactive-stdin',
'WIN_PROMPT' => '%cwd%> ', //Classic DOS style
'NIX_PROMPT' => '%user%@%hostname%:%cwd% #',
'USE_AUTH' => true,
'AUTH_USERNAME' => 'phpshell',
'AUTH_PASSWORD' => 'phpshell'
);
$args=PS::getArgvAssoc();
if(@$PC['USE_AUTH'] && !isset($args['proc'])){
if (!isset($_SERVER['PHP_AUTH_USER'])) {
header('WWW-Authenticate: Basic realm="My Realm"');
header('HTTP/1.0 401 Unauthorized');
echo 'Authentication required.';
exit;
}
if($_SERVER['PHP_AUTH_USER'] != @$PC['AUTH_USERNAME']
|| $_SERVER['PHP_AUTH_PW'] != @$PC['AUTH_PASSWORD'])
{
header('WWW-Authenticate: Basic realm="My Realm"');
header('HTTP/1.0 401 Unauthorized');
echo 'Authentication required.';
exit;
}
}
function rC($f,$cmd,$help=''){
$GLOBALS['REGISTERED_FUNCTIONS'][$cmd] = array('function'=>$f,'help'=>$help);
}
class PS {
public function __construct(){
@ob_clean();
$args=$this->getArgvAssoc();
if(isset($args['proc'])){
print_r($args);
print_r($GLOBALS['argv']);
chdir(realpath($args['cwd']).DIRECTORY_SEPARATOR);
$args['proc'] = $args['proc'];
$this->spawnProcess($args['proc'],$args['handle']);
exit;
}
if(isset($_POST['cwd'])){
chdir($_REQUEST['cwd']);
}
if(isset($_POST['action'])){
switch($_POST['action']){
case 'exec':
$this->runCommand($_POST['cmd'],@$_POST['mode']);
break;
case 'suggest':
$this->tabSuggest($_POST['input']);
break;
case 'proc':
$this->readProc($_POST['handle']);
break;
case 'stdin':
$this->stdinToProc($_POST['stdin'],$_POST['handle']);
break;
default:
echo json_encode(array('error' => 'Unknown action.'));
break;
}
} else {
$__CSS = $GLOBALS['__CSS'];
$__JS = $GLOBALS['__JS'];
$__PC = $GLOBALS['PC'];
unset($__PC['AUTH_USERNAME']);
unset($__PC['AUTH_PASSWORD']);
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>PHPShell</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-2.0.3.min.js"></script>
<script type="text/javascript">
var SI = <?php echo json_encode($this->getShellInfo());?>;
</script>
<script type="text/javascript">
<?php   ?>$(function(){
var input = '';
var history = [];
var currentHistory = 0;
var $pre = $('<pre>');
var mode = 'interactive-stdin';
var supportedModes = ['interactive-stdin','shell_exec'];
$('body').append($pre);
var $output = $('<span class="output"></span>');
$pre.append($output);
var $input = $('<input class="input">');
$pre.append($input);
$('body').append($('<div class="bg"></div>'));
$input.focus();
$input.focusout(function(){
setTimeout(function(){$input.focus();},1000);
});
var procStdIn = [];
var currentHandle = false;
var suggestions = [];
var currentSuggestion = 0;
var lastKey = 0;
var echoKeyboard = true;
$input.keyup(function(e){
if(currentHandle != false){
var strIn = $input.val();
if(strIn.length > 0){
procStdIn.push(strIn);
$input.val('');
}
if(e.which == 13||e.which==10){
procStdIn.push(String.fromCharCode(10));
}
sendStdIn();
}
});
$input.keydown(function(e){
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
cwd:SI.cwd,
input:$input.val()
}
}).done(function(data){
suggestions = data.suggestions;
$input.val(suggestions[currentSuggestion]);
$input.css('width',12+($input.val().length*12));
});
currentSuggestion = 0;
} else if(suggestions.length > 0) {
currentSuggestion++;
if(currentSuggestion >= suggestions.length) currentSuggestion = 0;
else $input.val(suggestions[currentSuggestion]);
$input.val(suggestions[currentSuggestion]);
$input.css('width',12+($input.val().length*12));
}
lastKey = 9;
e.preventDefault();
return false;
break;
case 38:
currentHistory++;
if(currentHistory > history.length){
currentHistory = 0;
$input.val('');
} else
$input.val(history[history.length-currentHistory]);
break;
case 40:
currentHistory--;
if(history < 0){
history = 0;
$input.val('');
} else
$input.val(history[history.length-currentHistory]);
break;
case 13:
runStatement();
break;
default: console.log(e.which);
break;
}
lastKey = e.which;
$input.css('width',12+($input.val().length*12));
});
function animateCursor(){
$("html, body").stop();
$("html, body").animate({ scrollTop: $(document).height() }, 2500);
}
function write(s){
$output.append(s);
}
function writeln(s){
$output.append(s+'\n');
}
function writeCwdLine(){
var cwdStr = SI.prompt_style;
var cwdVars = {
cwd : SI.cwd,
hostname : SI.hostname,
user : SI.user
};
$.each(cwdVars,function(i){
cwdStr = cwdStr.replace('%'+i+'%',this);
});
write('\n<span class="cwd">'+cwdStr+'</span>');
}
function readProc(){
$.post(window.location.href,{action:'proc',handle:currentHandle,cwd:SI.cwd}, function(response){
if(response.stdin !== "")
write(response.stdin);
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
var statement = $input.val();
history.push(statement);
currentHistory = 0;
$input.val('');
writeln(statement);
if(statement == "clear" || statement == "cls"){
$output.html('');
writeCwdLine();
return;
}
var setMode = statement.match(/xsetmode *([a-z-_]*)/);
if(setMode && setMode.length == 2){
if($.inArray(setMode[1],supportedModes) > -1){
mode = setMode[1];
writeln('PHPShell mode set to '+mode);
} else {
writeln('"'+setMode[1]+'" is not a valid option.\nSupported modes: '+supportedModes.join(', '));
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
$.post(window.location.href,{action:'exec',cwd:SI.cwd,cmd:statement,mode:mode},function(response){
if(mode == "interactive-stdin" && response.handle){
currentHandle = response.handle;
procStdIn = [];
readProc();
} else {
write(response.output);
SI.cwd = response.cwd;
writeCwdLine();
animateCursor();
}
},"JSON");
}
$('body').click(function(){
$input.focus();
});
writeln(SI.motd);
writeCwdLine();
});<?php ?>
<?php echo @$__JS; ?>
</script>
<style>
<?php echo $__CSS; ?>
.input {
border:none;
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
private $proc;
private $pipes;
private $handle;
private function startAsyncProc($cmd){
$handle=md5($cmd.time());
$this->handle = $handle;
file_put_contents($this->getTmpFile('stdout'), '');
$c = $this->getPhpPath()." ".$GLOBALS['ps_path']." -handle $handle -proc \"$cmd\" -cwd \"".getcwd()."\"";
echo json_encode(array('handle'=>$handle,'cwd'=>getcwd()));
chdir(dirname($GLOBALS['ps_path']));
if(PS::iW()){
pclose(popen("start /MIN $c", "r"));
} else {
shell_exec("nohup $c > /dev/null 2>/dev/null &");
}
return $handle;
}
private function getPhpPath(){
$pathTests = array();
$cachedResultFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'phpshell-phpbin-path';
if(@isset($GLOBALS['PC']['PHP_PATH'])
&& file_exists($GLOBALS['PC']['PHP_PATH'])){
return $GLOBALS['PC']['PHP_PATH'];
}
if(file_exists($cachedResultFile)){
return file_get_contents($cachedResultFile);
} else {
if(PS::iW()){
$pathTests = array(
'php',
'c:\php\php.exe',
'c:\php5\php.exe',
'c:\xampp\php\php.exe',
'c:\wamp\php\php.exe',
'c:\program files\php\php.exe'
);
} else {
$pathTests = array(
'php',
'php-cli',
'/bin/php',
'/bin/php-cli',
'/usr/bin/php',
'/usr/bin/php-cli'
);
}
foreach($pathTests as $p){
$r = shell_exec($p.' -v');
if($r){
file_put_contents($cachedResultFile, $p);
return $p;
}
}
}
file_put_contents($this->getTmpFile('stdout'), "\nERROR: Could not find php executable.".
"\nIf you now where it is you can specify it in phpshell-config.php or with the xphppath command\n".
PHPSHELL_EOF_MARK);
return 'php';
}
private function stdinToProc($stdin,$handle){
$this->handle = $handle;
$stdinStr = '';
foreach($stdin as $n){
if($n == "\r"){
$n = "\n";
}
$stdinStr .= $n;
}
file_put_contents($this->getTmpFile('stdin'), $stdinStr,FILE_APPEND);
}
public static function getArgvAssoc(){
global $argv,$argc;
$arguments = array();
for($i=0; $i < $argc; $i++){
$nextIsArg = strpos($argv[$i+1],'-') === 0;
if(strpos($argv[$i],'-') === 0){
$arguments[trim($argv[$i],'-')] = (isset($argv[$i+1]) && !$nextIsArg)
? $argv[$i+1] : true;
if(!$nextIsArg) $i++;
}
}
return $arguments;
}
private function getTmpFile($pipe){
return dirname(__FILE__).DIRECTORY_SEPARATOR.$this->handle.'-'.$pipe;
}
private function spawnProcess($cmd,$handle){
$this->handle = $handle;
file_put_contents($this->getTmpFile('stdout'), '');
file_put_contents($this->getTmpFile('stdin'),'');
$descriptors = array(
0 => array("pipe", "r"),
1 => array("file", $this->getTmpFile('stdout'),"a"), // stdout is a pipe that the child will write to
2 => array("file", $this->getTmpFile('stdout'),"a"), // stderr is a file to write to
);
$this->proc = proc_open($cmd, $descriptors, $this->pipes, getcwd());
usleep(250000);
$pinfo = proc_get_status($this->proc);
$terminate = false;
while($pinfo['running']){
echo '.';
//first clear statcache if long enough time has passed since the last stat
if(time() - filemtime($this->getTmpFile('stdin')) > 60){
clearstatcache(true, $this->getTmpFile('stdin'));
}
if(time() - filemtime($this->getTmpFile('stdin')) > 60){
$terminate = true;
passthru('pause');
break;
}
$stdin = @file_get_contents($this->getTmpFile('stdin'));
if($stdin !== '' && $stdin !== false){
file_put_contents($this->getTmpFile('stdin'),'');
fwrite($this->pipes[0],$stdin);
} else {
usleep(250000);
}
$pinfo = proc_get_status($this->proc);
}
proc_close($this->proc);
if($terminate){
//the client timed out.
@unlink($this->getTmpFile('stdout'));
} else {
file_put_contents($this->getTmpFile('stdout'),PHPSHELL_EOF_MARK,FILE_APPEND);
}
@unlink($this->getTmpFile('stdin'));
}
public function readProc($handle){
$this->handle = $handle;
$data = "";
$eof = false;
$touchCounter = 0;
while($data === ""){
$data = @file_get_contents($this->getTmpFile('stdout'));
if($data)
file_put_contents($this->getTmpFile('stdout'), '');
usleep (500000);
//to the process running that the client is still alive.
if($touchCounter == 0){
$touchCounter = 30;
touch($this->getTmpFile('stdin'));
}
$touchCounter--;
}
if(strpos($data, PHPSHELL_EOF_MARK) !== false){
$data = str_replace(PHPSHELL_EOF_MARK,'',$data);
@unlink($this->getTmpFile('stdout'));
@unlink($this->getTmpFile('stdin'));
$eof=true;
}
$detectedEncoding = mb_detect_encoding($data,mb_list_encodings(),true);
if($detectedEncoding != 'UTF-8'){
$data = iconv($detectedEncoding, 'UTF-8//TRANSLIT//IGNORE', $data);
}
echo json_encode(array('stdin' => htmlentities($data,null,'UTF-8'),'eof'=>$eof));
}
private function runCommand($cmd,$mode="shell_exec"){
$output = $this->processInternalCommand($cmd);
$pid = 0;
if($output === null && ($mode=="shell_exec" || !$mode) ){
$output = shell_exec($_REQUEST['cmd'].' 2>&1');
if($output === null) $output = 'Command not found';
else $output = htmlspecialchars ($output,null,'UTF-8');
} elseif($mode == "interactive-stdin" && $output === null) {
$this->startAsyncProc($cmd);
exit;
}
$detectedEncoding = mb_detect_encoding($output, mb_list_encodings(),true);
if($detectedEncoding != 'UTF-8'){
$output = iconv($detectedEncoding, 'UTF-8//TRANSLIT//IGNORE', $output);
}
echo json_encode(array(
'output' => $output,
'cwd' => getcwd()
));
exit;
}
private function tabSuggest($i){
$suggestions = array();
$input = explode(' ',$i);
$cmd = '';
if(count($input) > 1){
$search = $input[count($input)-1];
unset($input[count($input)-1]);
$cmd = implode(' ',$input).' ';
} else {
$search = $input[0];
}
foreach(glob("$search*") as $f){
$suggestions[] = $cmd.$f;
}
$suggestions[] = $input;
echo json_encode(array('suggestions'=>$suggestions));
exit;
}
private function processInternalCommand($statement){
global $REGISTERED_FUNCTIONS;
$statement = explode(' ', $statement,2);
$cmd = $statement[0];
$args = @$statement[1];
if(isset($REGISTERED_FUNCTIONS[$cmd])){
ob_start();
$commandFunc = $REGISTERED_FUNCTIONS[$cmd]['function'];
$commandFunc($args);
return ob_get_clean();
}
return null;
}
private function getShellInfo(){
return array(
'cwd' => getcwd(),
'motd' => $this->getMotd(),
'user' => function_exists('posix_getlogin')
? posix_getlogin()
: (
(function_exists('shell_exec') && PS::iW())
? trim(shell_exec('echo %USERNAME%'))
: '?'
),
'hostname' => gethostname(),
'prompt_style' => $GLOBALS['PC'][PS::iW()?'WIN_PROMPT':'NIX_PROMPT']
);
}
private function getMotd(){
$motd = 'PHP-Shell - '.php_uname();
if(isset($GLOBALS['PC']['MOTD']))
$motd .= "\n\n".$GLOBALS['PC']['MOTD']."\n";
$motd .= "\nEnter 'help' to get started.";
return $motd;
}
public static function iW(){
return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}
}
?>
<?php
function ps_cd($args){
if(file_exists($args))
chdir($args);
elseif(file_exists(getcwd().'/'.$args))
chdir(getcwd().'/'.$args);
echo 'Changed directory to: '.getcwd();
}
rC('ps_cd','cd','Change directory');
function ps_dl($args){
$basename = pathinfo($args,PATHINFO_BASENAME);
file_put_contents($basename, file_get_contents($args));
}
rC('ps_dl','dl','Download file from http or ftp');
function ps_eval($args){
if(preg_match('#;|echo\b|print[ (]|return#', $args))
eval($args);
else
var_dump(eval("return $args;"));
}
rC('ps_eval','eval','PHP eval()');
function ps_help($args){
foreach($GLOBALS['REGISTERED_FUNCTIONS'] as $cmd => $info){
echo str_pad($cmd, 25);
echo $info['help'];
echo "\n";
}
}
rC('ps_help','help','The command you just called');
function ps_lc($args){
echo count(file($args));
}
if(PS::iW())
rC('ps_lc','lc','line count');
function ps_prntscrn($args){
echo '<img src="?printscreen='.time().'" alt="[LOADING SCREENSHOT]" style="max-width:80%" />';
}
if(isset($_GET['printscreen'])){
$im = imagegrabscreen();
imagepng($im);
}
if(PS::iW())
rC('ps_prntscrn','prntscrn','Print screenshot to shell');
function ps_tail($args){
$data = file($args);
if(!$data){
echo 'Could not open file:'.$args;
return;
}
$modifier = array();
$lc = 25;
if(preg_match("#-n ([0-9]+)#", $args, $modifier)){
$lc = $modifier[1];
}
$count = count($data);
for($i=max($count-$lc,0);$i<$count;$i++){
echo str_pad($i+1,5).htmlentities($data[$i]);
}
}
if(PS::iW())
rC('ps_tail','tail','Get last lines from file (-n [lc])');
function ps_txt2art($i,$ret=false){
$args = PS::strToArgv($i);
$str = utf8_decode($args[0]);
$font = @$args[1] ? $args[1] : 5;
$imW = strlen($str)* imagefontwidth($font);
$imH = imagefontheight($font);
$im = imagecreate($imW,$imH);
$bg = imagecolorallocate($im, 255, 255, 255);
$textcolor = imagecolorallocate($im, 0, 0, 0);
$char = @$args[2] ? $args[2] : '#';
$bgChar = @$args[3] ? $args[3] : ' ';
imagestring($im, $font, 0, 0, $str, $textcolor);
$out = '';
for($y = 0; $y<$imH; $y++){
for($x = 0; $x < $imW; $x++){
$out .= (imagecolorat($im, $x, $y) == $textcolor) ? $char: $bgChar;
}
$out .= "\n";
}
echo $out;
if($ret)return $out;
if(file_put_contents('txt2art.out',$out)){
echo "\nSaved to: txt2art.out";
}
}
rC('ps_txt2art','txt2art','String to "ascii-art"');
function ps_view($args){
if(file_exists($args)){
$ext = pathinfo($args,PATHINFO_EXTENSION);
switch($ext){
case 'jpg':
case 'jpeg':
case 'png':
case 'bmp':
case 'gif':
$info = getimagesize($args);
echo '<img src="?view_img='.urlencode(realpath($args)).'" style="max-width:80%" />';
echo "\n$info[mime] ($info[0] x $info[1])";
break;
case 'php':
echo '<div style="background:#eee; max-height:400px; overflow:auto;">';
highlight_file($args, false);
echo '</div>';
break;
default:
$lines = file($args);
foreach($lines as $l => $s){
echo str_pad($l+1,8).htmlentities($s);
}
break;
}
}
}
if(isset($_GET['view_img'])){
echo file_get_contents($_GET['view_img']);
}
rC('ps_view','view','View various data files. supported: jpeg, png, bmp, gif, txt, php');
$__JS = <<<JSHERE
JSHERE;
$__CSS = <<<CSSHERE
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
$phpshell = new PS(); ?>