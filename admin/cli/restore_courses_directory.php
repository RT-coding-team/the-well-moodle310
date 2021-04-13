<?php

/**
Added so that we can install classes automatically from directory
 */

echo "Installing Classes From $argv[1]\n";

$courses = glob($argv[1] . '/*.mbz');

foreach ($courses as $course) {
  $command = 'sudo -u www-data php /var/www/moodle/admin/cli/restore_backup.php -f=' . $course . ' -c=1';
  echo "Restoring $course: $command\n";
  rename ($course,$course . ".RESTORED");
}

exit(0);
?>
