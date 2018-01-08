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
        $('body').off('keydown',keyhandler);
        PHPShell.writeCmdLine();
        return false;
      }
    };
    $('body').on('keydown',keyhandler);
  } else {
    PHPShell.writeCmdLine();
  }
}

PHPShell.onCommand(function(cmdline){
  if(cmdline.split(' ')[0] === 'xrun'){
    PHPShell.request({
      xproc : 'start',
      cmd   : cmdline
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
        PHPShell.writeln("\n"+list);
        PHPShell.writeCmdLine();
      });
    } else {
      resumeProc(cmd[1]);
    }
    return false;
  }
});
