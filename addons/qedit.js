
$(function(){
    $(document).on('click','.qedit .save', function(e){
      var $editor = $(this.parentNode);
      $.post(window.location.href,{
        qedit_content : $editor.find('textarea').val(),
        qedit_fn : $editor.data('file')
      }, function(){
        $editor.replaceWith('Saved');
      });

    });
})
