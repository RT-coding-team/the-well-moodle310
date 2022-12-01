<?php
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . 'quiz' . DIRECTORY_SEPARATOR . 'locallib.php');

/**
 * Retrieves quiz data from the database and returns as a PHP array.
 */
class QuizSerializer 
{
    /**
     * An instance of Moodle's database
     */
    protected $db = null;
    /**
     * The query to get the results
     */
    protected $query = "SELECT DISTINCT '' || u.id || '#' || COALESCE(quiza.attempt, 0) AS uniqueid, " . 
    "(CASE WHEN (quiza.state = 'finished' AND NOT EXISTS ( SELECT 1 FROM {quiz_attempts} qa2 " .
    "WHERE qa2.quiz = quiza.quiz AND qa2.userid = quiza.userid AND qa2.state = 'finished' AND " .
    "( COALESCE(qa2.sumgrades, 0) > COALESCE(quiza.sumgrades, 0) OR (COALESCE(qa2.sumgrades, 0) = COALESCE(quiza.sumgrades, 0) " .
    "AND qa2.attempt < quiza.attempt) ))) THEN 1 ELSE 0 END) AS gradedattempt, quiza.uniqueid AS usageid, quiza.id AS attempt, " .
    "u.id AS userid, u.idnumber, u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.firstname,u.lastname, " .
    "u.institution, u.department, u.email, quiza.state, quiza.sumgrades, quiza.timefinish, quiza.timestart, CASE WHEN quiza.timefinish = 0 " .
    "THEN null WHEN quiza.timefinish > quiza.timestart THEN quiza.timefinish - quiza.timestart ELSE 0 END AS duration, " .
    "COALESCE(( SELECT MAX(qqr.regraded) FROM {quiz_overview_regrades} qqr WHERE qqr.questionusageid = quiza.uniqueid ), -1) " .
    "AS regraded FROM {user} u LEFT JOIN {quiz_attempts} quiza ON quiza.userid = u.id AND quiza.quiz = :quizid JOIN {user_enrolments} " .
    "ej1_ue ON ej1_ue.userid = u.id JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = :courseid) JOIN " .
    "(SELECT DISTINCT userid FROM {role_assignments} WHERE contextid IN (1,565,717,738) AND roleid IN (5) ) ra ON ra.userid = u.id " .
    "WHERE quiza.preview = 0 AND quiza.id IS NOT NULL AND u.deleted = 0 AND u.id <> 1 AND u.deleted = 0";

    /**
     * The quiz being serialized
     */
    protected $quiz;

    /**
     * Build the class
     * 
     * @param integer   $id         The quiz id
     * @param object    $database   The Moodle database object
     */
    public function __construct($id, $database)
    {
        $this->db = $database;
        $this->quiz = $this->db->get_record('quiz', array('id' => $id));
    }

    /**
     * Get the details about the quiz
     * 
     * @return array an array of details about the quiz
     */
    public function details()
    {
        if (!$this->quiz) {
            return [];
        }
        return [
            'id'            =>  $this->quiz->id,
            'type'          =>  'quiz',
            'name'          =>  $this->quiz->name,
            'intro'         =>  strip_tags($this->quiz->intro),
            'max_grade'     =>  $this->quiz->grade,
            'created_on'    =>  $this->quiz->timecreated,
            'modified_on'   =>  $this->quiz->timemodified,
        ];
    }
    /**
     * Get the results of the quiz
     * 
     * @param integer $courseId The id of the course
     * 
     * @return array            An array of results
     */
    public function results($courseId)
    {
        $results = [];
        $attempts = $this->db->get_records_sql(
            $this->query,
            ['quizid'  =>  $this->quiz->id, 'courseid' => $courseId]
        );
        foreach ($attempts as $attempt) {
            $results[] = [
                'firstname'     =>  $attempt->firstname,
                'lastname'      =>  $attempt->lastname,
                'duration'      =>  $attempt->duration,
                'grade'         =>  quiz_rescale_grade($attempt->sumgrades, $this->quiz),
                'state'         =>  quiz_attempt_state_name($attempt->state),
                'started_on'    =>  $attempt->timestart,
                'finished_on'   =>  $attempt->timefinish,
            ];
        }
        return $results;
    }
}