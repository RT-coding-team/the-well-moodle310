# Module Icons

This is a Moodle custom field that allows the user to select which icon to display on the course page.

## Icons

All icons should be placed in your theme directory under `pix_core/mi`.  If no images are found, you will not be able to select an image.

## Issues

In order for this to work, I needed to add a new function callback named **PLUGINNAME**_modify_coursemodule_info.  I then modified `course/lib.php` to call this callback.  You will find the code on the following lines: 478 & 558-574.
