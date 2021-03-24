/**
 * Poll the server for more information about the sync script
 *
 * @return {void}
 */
function pollServer() {
  $.get('/local/chat_attachments/report.json', function(data) {
    if (data.progress !== null) {
      $('#current-progress h5').text(data.progress.title);
      var completed = (data.progress.current + data.progress.error);
      var total = (completed/data.progress.total) * 100;
      $('#current-progress .progress-bar').css('width', total + '%');
      $('#current-progress .progress-bar').attr('aria-valuenow', total);
      $('#current-progress .progress-bar').text(completed + ' / ' + data.progress.total);
      $('#current-progress').removeClass('d-none');
    } else {
      setTimeout(function() {
        $('#current-progress').addClass('d-none');
      }, 1000);
    }
    if (data.results.status === 'started') {
      $('button#sync').prop('disabled', true);
      $('button#sync i').addClass('fa-spin');
    } else {
      $('button#sync').prop('disabled', false);
      $('button#sync i').removeClass('fa-spin');
    }
    setTimeout(pollServer, 2000);
  });
}

$(function() {
    $('button#sync').on('click', function(event) {
      event.stopPropagation();
      $('button#sync').prop('disabled', true);
      $('button#sync i').addClass('fa-spin');
      $.get('./sync.php').then(function() {});
      return false;
    });
    pollServer();
});
