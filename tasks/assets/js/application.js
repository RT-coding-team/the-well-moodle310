/**
 * Populate the stats fields
 *
 * @param  {object} stats The stats data
 * @return {void}
 */
function populateStats(stats) {
  var parent = $('#statistics .statistics-table');
  parent.find('.info-status').first().text(stats.status);
  if (stats.hasOwnProperty('box_id')) {
      parent.find('.info-box-id').first().text(stats.box_id);
  } else {
    parent.find('.info-box-id').first().text('-');
  }
  if (stats.hasOwnProperty('last_time_synced_pretty')) {
    parent.find('.info-time-synced').first().text(stats.last_time_synced_pretty);
  } else {
    parent.find('.info-time-synced').first().text('-');
  }
  if (stats.hasOwnProperty('total_messages_sent')) {
    parent.find('.info-sent-messages').first().text(stats.total_messages_sent);
  } else {
    parent.find('.info-sent-messages').first().text('-');
  }
  if (stats.hasOwnProperty('total_attachments_sent')) {
    parent.find('.info-sent-attachments').first().text(stats.total_attachments_sent);
  } else {
    parent.find('.info-sent-attachments').first().text('-');
  }
  if (stats.hasOwnProperty('total_attachments_sent_failed')) {
    parent.find('.info-sent-attachments-failed').first().text(stats.total_attachments_sent_failed);
  } else {
    parent.find('.info-sent-attachments-failed').first().text('-');
  }
  if (stats.hasOwnProperty('total_messages_received_completed')) {
    parent.find('.info-messages-received').first().text(stats.total_messages_received_completed);
  } else {
    parent.find('.info-messages-received').first().text('-');
  }
  if (stats.hasOwnProperty('total_messages_received_failed')) {
      parent.find('.info-messages-received-failed').first().text(stats.total_messages_received_failed);
  } else {
      parent.find('.info-messages-received-failed').first().text('-');
  }

  if (stats.hasOwnProperty('total_missing_attachments_requested')) {
    parent.find('.info-missing-attachments').first().text(stats.total_missing_attachments_requested);
  } else {
    parent.find('.info-missing-attachments').first().text('-');
  }
}
/**
 * Populate the logs panel.
 *
 * @param  {array} logs   An array of log data
 * @return {void}
 */
function populateLogs(logs) {
  $('#logs-output .card-body').html('');
  $.each(logs, function(index, log) {
    var html = '<p><em>' + log.pretty_time + ':</em> ' + log.message + ' <span class="badge badge-pill badge-primary">' + log.category + '</span></p>';
    $('#logs-output .card-body').append(html);
  });
}
/**
 * Poll the server for more information about the sync script
 *
 * @return {void}
 */
function pollServer() {
  $.get('/local/chat_attachments/report.json', function(data) {
    populateLogs(data.logs);
    populateStats(data.results);
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
