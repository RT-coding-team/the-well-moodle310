/**
 * Poll the server for more information about the sync script
 *
 * @return {void}
 */
function pollServer() {
  $.get('/local/chat_attachments/report.json', function(data) {
    if (data.results.status === 'started') {
      $('button#sync').prop('disabled', true);
    } else {
      $('button#sync').prop('disabled', false);
    }
    setTimeout(pollServer, 2000);
  });
}

$(function() {
    $('button#sync').on('click', function(event) {
      event.stopPropagation();
      $('button#sync').prop('disabled', true);
      $.get('./sync.php').then(function() {});
      return false;
    });
    pollServer();
});
