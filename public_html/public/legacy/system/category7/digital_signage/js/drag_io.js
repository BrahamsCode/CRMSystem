$(function() {
  var $list_gallery = $('#list_gallery'), $trash = $('#trash');

/*
  $('div', $list_gallery).draggable({
    cancel: 'a.ui-icon',  // clicking an icon won't initiate dragging
    revert: 'invalid',    // when not dropped, the item will revert back to its initial position
    containment: $('#demo-frame').length ? '#demo-frame' : 'document', // stick to demo-frame if present
    helper: 'clone',
    cursor: 'move'
  });


  $('div',$trash).draggable({
    cancel: 'a.ui-icon',  // clicking an icon won't initiate dragging
    revert: 'invalid',    // when not dropped, the item will revert back to its initial position
    containment: $('#demo-frame').length ? '#demo-frame' : 'document', // stick to demo-frame if present
    helper: 'clone',
    cursor: 'move'
  });


  $trash.droppable({
    accept: '#list_gallery > div',
    activeClass: 'ui-state-highlight',
    drop: function(ev, ui) {
      deleteImage(ui.draggable);
    }
  });

  $list_gallery.droppable({
    accept: '#trash div',
    activeClass: 'custom-state-active',
    drop: function(ev, ui) {
      recycleImage(ui.draggable);
    }
  });
*/



  // image deletion function
  var recycle_icon = '<a href="#nogo" title="選択解除" class="ui-icon ui-icon-refresh">選択解除</a>';

  function deleteImage($item) {
    $item.fadeOut(function() {
      var $list = $('div', $trash).length ? $('div', $trash) : $('<div class="list_gallery ui-helper-reset"/>').appendTo($trash);

      $item.find('a.ui-icon-trash').remove();
      $item.append(recycle_icon).appendTo('#list_trash').fadeIn(function() {
     // $item.animate({ width: '96px' }).find('img').animate({ height: '72px' });
      });
    });
  }

  // image recycle function
  var trash_icon = '<a href="#nogo" title="選択する" class="ui-icon ui-icon-trash">選択する</a>';

  function recycleImage($item) {
    $item.fadeOut(function() {
      $item.find('a.ui-icon-refresh').remove();
      $item.append(trash_icon).appendTo('#list_gallery').fadeIn();
    });
  }



  // image preview function, demonstrating the ui.dialog used as a modal window
  function viewLargerImage($link) {
    var src = $link.attr('href');
    var title = $link.siblings('img').attr('alt');
    var $modal = $('img[src$="' + src + '"]');

    $modal.length ? $modal.dialog('open') : $('<img alt="' + title + '" width="384" height="288" style="padding: 8px;" />').attr('src', src).appendTo('body').dialog({
      title: title,
      width: '400px', 
      modal: 'true',
      overlay: {
        backgroundColor: '#000',
        opacity: 0.5
      }
    });
  }

  // resolve the icons behavior with event delegation
  $('div.list_gallery > div').click(function(ev) {
    var $item = $(this);
    var $target = $(ev.target);

    if ($target.is('a.ui-icon-trash')) {
      deleteImage($item);
    } else if ($target.is('a.ui-icon-zoomin')) {
      viewLargerImage($target);
    } else if ($target.is('a.ui-icon-refresh')) {
      recycleImage($item);
    }

    return false;
  });
});