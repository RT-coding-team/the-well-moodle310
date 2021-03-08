<?php
/**
 * An API used to trigger specific functionality for the chat attachments.
 * NOTE: You must use a specific version of the mobile app to use this.
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/webservice/lib.php');
require_once($CFG->libdir . "/filelib.php");

/**
 * A list of methods allowed for this API
 */
$allowedMethods = ['add_file'];
$token = $_REQUEST['token'];
if ($token) {
    $api = new webservice();
    $api->authenticate_user($token);
}
/**
 * Only allow logged in users.
 *
 */
if (!isloggedin()) {
    http_response_code(401);
    header('Content-type: application/json');
    echo json_encode([
        'exception' =>  'webservice_access_exception',
        'errorcode' =>  'accessexception',
        'message'   =>  'You must be logged in to access this endpoint.'
    ]);
    exit();
}
$method = $_REQUEST['method'];
if ((!$method) || (!in_array($method, $allowedMethods))) {
    http_response_code(400);
    header('Content-type: application/json');
    echo json_encode([
        'exception' =>  'webservice_bad_request',
        'errorcode' =>  'bad_request',
        'message'   =>  'The method is not available.'
    ]);
    exit();
}
if ($method === 'add_file') {
    /**
     * Only POST Requests
     */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            'exception' =>  'webservice_bad_request',
            'errorcode' =>  'bad_request',
            'message'   =>  'The request method is not available.'
        ]);
        exit();
    }
    /**
     * Move the given file from draft to our context
     * NOTE: This endpoint will not work if the user is different then the user who added the file.
     */
    $itemId = $_REQUEST['item_id'];
    if (!$itemId) {
        http_response_code(404);
        header('Content-type: application/json');
        echo json_encode([
            'exception' =>  'webservice_resource_not_found',
            'errorcode' =>  'resource_not_found',
            'message'   =>  'You are missing the item id.'
        ]);
        exit();
    }
     $context = context_system::instance();

     file_save_draft_area_files(
         $itemId,
         $context->id,
         'local_chat_attachments',
         'chat_attachment',
         $itemId,
         []
     );

     http_response_code(200);
     header('Content-type: application/json');
     echo json_encode(['success' =>  true]);
     exit();
}
