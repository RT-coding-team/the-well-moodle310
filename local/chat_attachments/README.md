# Chat Attachments

A Moodle local plugin designed to work with a modified version of the mobile app.  It enables displaying images, video and files in a chat message.  See the repo for chathost APIs at https://github.com/RT-coding-team/chathost

## File Server

This code also provides a file server.  Any files that have been moved into our chat attachment plugin (See the API below), are accessible through this plugin. The URL for the files should be constructed this way:

**SITE_DOMAIN/pluginfile.php/1/local_chat_attachments/chat_attachment/ITEM_ID/PATH_TO_FILE/FILE_NAME**

## API

This plugin provides an API for managing the chat attachments.  All API requests are made to the following URL: `**SITE_DOMAIN**/local/chat_attachments/api.php`.  Here are the available endpoints:

### Add File [POST]

This endpoint moves a file from the user's draft section to our chat attachment plugin.

#### Parameters

You need to make a POST request with the following parameters:

| Parameter | Required | Description |
| :-------: | :------: | :---------: |
| item_id | Yes | The item id for the file provided by Moodle's File API |
| method | Yes | The method you want to call on the API. This method is **add_file** |
| token | Yes | This is Moodle's user token in order to authorize the user preforming the action.  **This must be the owner of the file.** |

#### Example Query

This example uses Angular's HTTP service.

```javascript
const url = `${siteUrl}/local/chat_attachments/api.php`;
const headers = {
    headers: {
        'content-type': 'application/json'
    }
};
const params = {
    token: token,
    method: 'add_file',
    item_id: itemId,
};
this.http.post(url, params, headers).pipe(take(1)).subscribe(() => {
    // Do your work here.
});
```

#### Returns

```json
{
    "success": true
}
```

## Push Messages

To push messages to the API, you have 3 options:

1. Visit `${siteUrl}/tasks/` and use the provided GUI.
2. Visit `${siteUrl}/local/chat_attachments/push_messages.php?logging=display`. (If you remove the logging param, it will log to the report.json file.)
3. On terminal run the following command `/local/chat_attachments/push_messages.php true`. (If you add an additional true, it will log to the report.json file instead of stdout.)

This script will handle all the message syncing.

## Command Line Scripts

We also provide a few command line scripts for managing the chat attachments.

### Clean Up Attachments

This script will remove files from messages older than the provided number of days, and replace it with a missing symbol.

```
php clean_up.php <NUMBER_OF_DAYS>
```

### Compress Attachments

This script requires [FFmpeg](https://www.ffmpeg.org/) to be installed on your server.  It will compress the attachment in order to reduce the required server space. Use the following command to run the script:

```
php compress_attachment.php <ITEMID>
```

The ITEMID is the itemid that identifies which attachment to compress.

### Push Messages

It is better to use the visual tool listed above, but you can also use the CLI.  Simply run the following command:

```
php push_messages.php true <boolean:LOG_TO_FILE>
```

The first true arguments tells the script that you are using the command line.  The second boolean indicates if you want to log to the JSON file (true) or to the terminal (false).

### Messaging Flow Between Moodle and Chathost
* Moodle -> Chathost
* POST /chathost/logs (Array of log objects) -> Returns 200
* GET /chathost/settings -> Returns Array of settings objects
* DELETE /chathost/settings/:id -> Returns 200
* GET /chathost/messageStatus -> Returns timestamp of last successful sync (Chathost begins to compile pending messages)
* POST /chathost/rosters (Array of course objects) -> Returns 200
* GET /chathost/attachments/:id/exists -> Returns 200 or 404 (repeated for each attachment pending)
* POST /chathost/attachments/ (One attachment multipart form) -> Returns 200
* POST /chathost/messages (Array of message objects) -> Returns 200
* (Sleep to allow Chathost to complete the tasks)
* GET /chathost/messages/:synctime -> Returns array of message objects (synctime is from messageStatus API)
