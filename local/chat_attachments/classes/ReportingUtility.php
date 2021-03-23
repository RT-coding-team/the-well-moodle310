<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This class creates a JSON file for reporting script progress to the browser.
 * The file is recreated each time this class intialized
 */
class ReportingUtility
{
    /**
     * What line break we will use when printing to the screen.
     *
     * @var string
     * @access public
     */
    public $printLineBreak = "\r\n";

    /**
     * The name of the file that is created.
     *
     * @var string
     * @access protected
     */
    protected $filename = 'report.json';

    /**
     * The reporting file.
     *
     * @var string
     * @access protected
     */
    protected $logFile;

    /**
     * Do you want to log to the file, or echo out.  If true, it logs to the file.
     * If false, it logs to console.
     *
     * @var boolean
     * @access protected
     */
    protected $toFile = true;

    /**
     * The data that is written to the JSON file.
     *
     * errors:      An array of errors that occurred
     * logs:        An array of log messages
     * progress:    If we are current in a progress loop it will contain keys: current, total, and title.
     * results:     An array of results for the task.
     *
     * @var array
     * @access protected
     */
    protected $data = [
        'errors'    =>  [],
        'logs'      =>  [],
        'progress'  =>  null,
        'results'   =>  []
    ];

    /**
     * Sets up the utility
     *
     * @param string    $directory  The directory to store the file. (default: basename(__DIR__))
     * @param boolean   $toFile     Do you want to store the results to a file?
     */
    public function __construct($directory = null, $toFile = true)
    {
        $this->toFile = $toFile;
        if (!$directory) {
            $directory = basename(__DIR__);
        }
        if (!file_exists($directory)) {
            throw new InvalidArgumentException(
                'You must provide a valid directory for storing the report file.'
            );
        }
        $this->logFile =  rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->filename;
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
        $this->save();
    }

    /**
     * Report an error
     *
     * @param   string $message     The message of the error
     * @param   string $category    A category to group log messages (default: error)
     * @return  void
     * @access  public
     */
    public function error($message, $category = 'error')
    {
        $error = [
            'category'      =>  $category,
            'message'       =>  $message,
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        $this->data['errors'][] = $error;
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('ERROR', $error);
        }
    }

    /**
     * Report a message
     *
     * @param  string $message The message to log
     * @param   string $category    A category to group log messages (default: info)
     * @return void
     * @access public
     */
    public function info($message, $category = 'info')
    {
        $item = [
            'category'      =>  $category,
            'message'       =>  $message,
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        $this->data['logs'][] = $item;
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('INFO', $item);
        }
    }

    /**
     * Print message to screen
     *
     * @param   string  $type       The type of message
     * @param   array   $message    An array of details: message, timestamp, pretty_time
     * @return void
     * @access protected
     */
    protected function print($type, $message)
    {
        echo $message['pretty_time'] . ': [' . $type . '] ' . $message['message'] . ' (' . $message['category'] . ')' . $this->printLineBreak;
    }

    /**
     * Save the data to the file.
     *
     * @return void
     * @access protected
     */
    protected function save()
    {
        if ($this->toFile) {
            file_put_contents($this->logFile, json_encode($this->data));
        }
    }
}
