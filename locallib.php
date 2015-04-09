<?php
// This file is part of Moodle - http://moodle.org/
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
 * This file contains the definition for the library class for engcentral submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_engcentral
 * @copyright 2015 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// File area for online text submission assignment.
define('ASSIGNSUBMISSION_ENGCENTRAL_FILEAREA', 'submissions_engcentral');
require_once($CFG->dirroot  . '/mod/englishcentral/englishcentral.php');

/**
 * library class for engcentral submission plugin extending submission plugin base class
 *
 * @package assignsubmission_engcentral
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_engcentral extends assign_submission_plugin {
	
	const NM = 'assignsubmission_engcentral';
	const TABLE = 'assignsubmission_engcentral';

    /**
     * Get the name of the online text submission plugin
     * @return stringself::NM . '
     */
    public function get_name() {
        return get_string('engcentral', self::NM);
    }



    /**
     * Get engcentral submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_engcentral_submission($submissionid) {
        global $DB;

        return $DB->get_record(self::NM, array('submission'=>$submissionid));
    }

    /**
     * Get the settings for engcentral submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

		//just for now
		$gconfig = get_config('assignsubmission_engcentral');
		$config = new stdClass();
		$config->videotitle = $this->get_config('videotitle') ?  $this->get_config('videotitle') : '-English Central video title -';
		$config->videoid = $this->get_config('videoid') ?  $this->get_config('videoid') : '-English Central video id-';
		
		//-------------------------------------------------------------------------------
        // Adding the rest of englishcentral settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        $mform->addElement('text', self::NM . '_videotitle', get_string('videotitle', 'assignsubmission_engcentral'), array('size'=>'64'));
        $mform->addElement('text', self::NM . '_videoid', get_string('videoid', 'assignsubmission_engcentral'), array('size'=>'24'));
       // $mform->addRule(self::NM . '_videotitle', null, 'required', null, 'client');
        //$mform->addRule(self::NM . '_videoid', null, 'required', null, 'client');
        $mform->setType(self::NM . '_videotitle', PARAM_TEXT);
        $mform->setType(self::NM . '_videoid', PARAM_INT);
        $mform->setDefault(self::NM . '_videoid', $config->videoid);
        $mform->setDefault(self::NM . '_videotitle', $config->videotitle);
			
		$checkboxes = array('watchmode','speakmode','speaklitemode','simpleui','learnmode','hiddenchallengemode', 'lightboxmode');
		foreach($checkboxes as $cbox){
			$config->{$cbox}=$this->get_config($cbox) ? $this->get_config($cbox) : $gconfig->{$cbox};
        	$mform->addElement('advcheckbox', self::NM . '_' . $cbox, get_string($cbox, 'assignsubmission_engcentral'));
        	$mform->setDefault(self::NM . '_' . $cbox, $config->{$cbox});
		}

    }

    /**
     * Save the settings for engcentral submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
    
    	$elements = array('videotitle','videoid','watchmode','speakmode','speaklitemode',
    					'simpleui','learnmode','hiddenchallengemode', 'lightboxmode');
    	foreach($elements as $element){
    		$this->set_config($element, $data->{self::NM . '_' . $element}); 
    	}

        return true;
    }
    
    private function get_ec_result_fields(){
    	return  array('recordingComplete',
		'sessionScore','activeTime','dateCompleted',
		'timeOnTaskClock','points','linesTotal','watchedComplete',
		'linesWatched','linesRecorded','sessionGrade','videoid');
    }

    /**
     * Add form elements for submission
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
    	global $CFG, $PAGE , $USER;
    	
        $elements = array();

        $submissionid = $submission ? $submission->id : 0;
		
		$hiddenelements = $this->get_ec_result_fields();
		
	foreach($hiddenelements as $element){
		$mform->addElement('hidden', self::NM . '_' . $element,false, array('class'=>'englishcentral_' . $element));
		$mform->setType(self::NM . '_' . $element,PARAM_RAW);
    }

     //get EC stuff ready   
	//authenticate with English Central, and get our API ready
	$gconfig = get_config('englishcentral');
  	$consumer_key = $gconfig->consumerkey;
    $consumer_secret = $gconfig->consumersecret;
    $ec = new EnglishCentral($consumer_key,$consumer_secret);
    $oauth_callback = $CFG->wwwroot. '/admin/oauth2callback.php';

	$ec_user = $USER->username; //'justinhunt';
    $requesttoken = $ec->getRequestToken($oauth_callback);
    $accesstoken = $ec->getAccessToken($requesttoken,$ec_user);



	//get our javascript all ready to go
	$jsmodule = array(
		'name'     => 'mod_englishcentral',
		'fullpath' => '/mod/englishcentral/module.js',
		'requires' => array('io','json','button')
	);
	//here we set up any info we need to pass into javascript
	$opts =Array();
	$opts['appid'] =$consumer_key;
	$opts['cmid'] =1; //$cm->id;
	$opts['accesstoken'] =$accesstoken;
	$opts['requesttoken'] =$requesttoken; 
	$opts['videoid'] =$this->get_config('videoid') ; 
	$opts['watchmode'] =$this->get_config('watchmode') ==1;
	$opts['speakmode'] =$this->get_config('speakmode')==1; 
	$opts['speaklitemode'] =$this->get_config('speaklitemode')==1; 
	$opts['learnmode'] =$this->get_config('learnmode')==1; 
	$opts['hiddenchallengemode'] =$this->get_config('hiddenchallengemode')==1; 
	$opts['simpleui'] =$this->get_config('simpleui')==1;
	$opts['resultsmode'] ='form';
	$opts['playerdiv'] ='mod_englishcentral_playercontainer';
	$opts['resultsdiv'] ='mod_englishcentral_resultscontainer';
	$opts['lightbox'] =$this->get_config('lightboxmode')==1; 

	//this inits the M.mod_englishcentral thingy, after the page has loaded.
	$PAGE->requires->js_init_call('M.mod_englishcentral.playerhelper.init', array($opts),false,$jsmodule);
	
	//this loads the strings we need into JS
	$PAGE->requires->strings_for_js(array('sessionresults','sessionscore','sessiongrade','lineswatched',
						'linesrecorded','compositescore','sessionactivetime','totalactivetime'), 'englishcentral');

	//this loads the external JS libraries we need to call
	$PAGE->requires->js(new moodle_url('https://www.englishcentral.com/platform/ec.js'));
	$renderer = $PAGE->get_renderer('mod_englishcentral');
	$echtml = $renderer->show_bigbutton(false);
	$echtml .= $renderer->show_ec_box();
	$mform->addElement('static', self::NM . '_' . 'description', '',$echtml);
	
	
	return true;
    }

    

    /**
     * Save data to the database and trigger plagiarism plugin,
     * if enabled, to scan the uploaded content via events trigger
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;


        $engcentralsubmission = $this->get_engcentral_submission($submission->id);

        $params = array(
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submission->id,
            'other' => array( )
        );
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
            $params['relateduserid'] = $submission->userid;
        }
/*
        $event = \assignsubmission_engcentral\event\assessable_uploaded::create($params);
        $event->trigger();
*/
        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), '*', MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'groupid' => $groupid,
            'groupname' => $groupname
        );
        
		//the elements we need for storing results from EC
		$hiddenelements =  $this->get_ec_result_fields();
		
		//if we don't have a submission object create one
		$existingsubmission =true;
		 if (!$engcentralsubmission) {
		 	 $engcentralsubmission = new stdClass();
		 	 $existingsubmission =false;
		 }
		
		 //get all the english central field elements
		foreach($hiddenelements as $element){
			$thevalue = trim($data->{self::NM . '_' . $element});
			switch($element){

				case 'sessionGrade':
					if(!$thevalue){
						$thevalue='';
					}
					break;
				case 'dateCompleted':
					if(!empty($thevalue)){
						$thevalue=time();
					}
					break;
				case 'recordingComplete':
				case 'watchedComplete':
					if($thevalue=='true'){
						$thevalue=1;
					}else{
						$thevalue=0;
					}
					break;
				case 'sessionScore':
					if(!is_numeric($thevalue)){
						$thevalue=null;
					}else{
						$thevalue=round($thevalue*100,0);
					}
					break;
				default:
					if(!is_numeric($thevalue)){
						$thevalue=null;
					}
					
			}
			$engcentralsubmission->{strtolower($element)}=$thevalue;
		}
		
		//if we have no video id or the active time is zero, we should not save.
		//the user has not interacted with EC and if we save we might overwrite previous submission
		if(!$engcentralsubmission->videoid || !$engcentralsubmission->activetime){
			return true;
		}
			
        if ($existingsubmission) {

            $params['objectid'] = $engcentralsubmission->id;
            $updatestatus = $DB->update_record(self::TABLE, $engcentralsubmission);
            /*
            $event = \assignsubmission_engcentral\event\submission_updated::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            */
            return $updatestatus;
        } else {
            $engcentralsubmission->submission = $submission->id;
            $engcentralsubmission->assignment = $this->assignment->get_instance()->id;
            $engcentralsubmission->id = $DB->insert_record(self::TABLE, $engcentralsubmission);
            $params['objectid'] = $engcentralsubmission->id;
            /*
            $event = \assignsubmission_engcentral\event\submission_created::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            */
            return $engcentralsubmission->id > 0;
        }
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array();
    }
    
    
    /**
     * Produce a list of files suitable for export that represent this submission.
     *
     * @param stdClass $submission - For this is the submission data
     * @param stdClass $user - This is the user record for this submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        global $DB;

        $files = array();
        //$engcentralsubmission = $this->get_engcentral_submission($submission->id);


        return $files;
    }



     /**
      * Display the submission info
      *
      * @param stdClass $submission
      * @param bool $showviewlink - If the summary has been truncated set this to true
      * @return string
      */
    public function view_summary(stdClass $submission, & $showviewlink) {
    	 // Always show the view link.
        $showviewlink = true;
       $result = '';

        $engcentralsubmission = $this->get_engcentral_submission($submission->id);

        if ($engcentralsubmission) {
        
        	$completionrate = $engcentralsubmission->recordingcomplete ? 1: 0;
			//this won't work im speaklite because linestotal = recordable lines
			if(($this->get_config('speakmode')==1 && $this->get_config('speaklitemode')!=1)  && $engcentralsubmission->linesrecorded>0){
				$completionrate = $engcentralsubmission->linesrecorded / $engcentralsubmission->linestotal;
			}

            $result .= round($completionrate * $engcentralsubmission->sessionscore ,0) . '%';

        }

        return $result;
    
        
        
    }


    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
    global $CFG;

        $ecsub = $this->get_engcentral_submission($submission->id);
       
        /*
        $fields = $this->get_ec_result_fields();
		$ret = '';
        if ($ecsub) {
        	foreach($fields as $field){
        		$ret .= $field . ': ' . ($ecsub->{strtolower($field)}!==null ? $ecsub->{strtolower($field)} : '');
         		$ret .= '<br />';
         	}
        }
        return $ret;
        */
        
    	$ret = '<br/><b>' . get_string('sessionactivetime', 'englishcentral') .':  </b>' . $ecsub->activetime  . ' seconds<br />';
    	$ret .= '<b>' . get_string('totalactivetime', 'englishcentral') .':  </b>' . ($ecsub->totalactivetime ? $ecsub->totalactivetime  . ' seconds' : ' unfinished') . '<br />';
    	$ret .= '<b>' . get_string('lineswatched', 'englishcentral') .':  </b>' . $ecsub->lineswatched . '/' . $ecsub->linestotal . '<br />';
    	$ret .= '<b>' . get_string('linesrecorded', 'englishcentral') .':  </b>' . $ecsub->linesrecorded . '<br />';
    	$ret .= '<b>' . get_string('sessionscore', 'englishcentral') .':  </b>' . $ecsub->sessionscore . '%' . '<br />';
    	$ret .= '<b>' . get_string('sessiongrade', 'englishcentral') .':  </b>' . $ecsub->sessiongrade . '<br />';
    	$completionrate = $ecsub->recordingcomplete ? 1: 0;
    	//this won't work im speaklite because linestotal = recordable lines
    	if(($this->get_config('speakmode')==1 && $this->get_config('speaklitemode')!=1)  && $ecsub->linesrecorded>0){
    		$completionrate = $ecsub->linesrecorded / $ecsub->linestotal;
    	}
    	$ret .= '<b>' . get_string('compositescore', 'englishcentral') .':  </b>' . round($completionrate * $ecsub->sessionscore ,0) . '%<br />';
        return $ret;
     
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        return false;
    }


    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string $log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        // No settings to upgrade.
        return true;
    }



    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // Format the info for each submission plugin (will be logged).
        $engcentralsubmission = $this->get_engcentral_submission($submission->id);
        $engcentralloginfo = '';
        $engcentralloginfo .="activetime:" . $engcentralsubmission->activetime;

        return $engcentralloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records(self::TABLE,
                            array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $engcentralsubmission = $this->get_engcentral_submission($submission->id);
        //if we have a video id, something was done
        return !$engcentralsubmission->videoid;

    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array();
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;



        // Copy the assignsubmission_engcentral record.
        $engcentralsubmission = $this->get_engcentral_submission($sourcesubmission->id);
        if ($engcentralsubmission) {
            unset($engcentralsubmission->id);
            $engcentralsubmission->submission = $destsubmission->id;
            $DB->insert_record(self::TABLE, $engcentralsubmission);
        }
        return true;
    }


}


