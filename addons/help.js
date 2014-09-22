$(function(){
    PHPShell.onCommand(function(statement){
        if(statement.match(/^help/)){
            $([
                'Client commands:',
                'xsetmode       shell_exec | interactive',
                'xecho          on | off',
                'help',
                '',
                'PHP commands:'
            ]).each(function(){
                PHPShell.writeln(this);
            });
            
        }
    });
})