<?php
/**
 * Functions for the plugin
 */
function local_chat_attachments_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options=array()
) {
    /**
     * Is it the correct filearea we support?
     */
    if ($filearea !== 'chat_attachment') {
        return false;
    }
    $systemContext = context_system::instance();
    $itemId = array_shift($args);
    $fileName = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }
    $fs = get_file_storage();
    $file = $fs->get_file(
        $systemContext->id,
        'local_chat_attachments',
        $filearea,
        $itemId,
        $filepath,
        $fileName
    );
    if (!$file) {
        /**
         * Try moving the file from Draft to Our Context and check again
         */
        file_save_draft_area_files(
            $itemId,
            $systemContext->id,
            'local_chat_attachments',
            $filearea,
            $itemId,
            []
        );

        $file = $fs->get_file(
            $systemContext->id,
            'local_chat_attachments',
            $filearea,
            $itemId,
            $filepath,
            $fileName
        );
        if (!$file) {
            return false;
        }
    }
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
