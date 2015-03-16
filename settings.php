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
 * This file defines the admin settings for this plugin
 *
 * @package   assignsubmission_engcentral
 * @copyright 2015 Justin Hunt {@link http://poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/default',
                   new lang_string('default', 'assignsubmission_engcentral'),
                   new lang_string('default_help', 'assignsubmission_engcentral'), 0));
                   
$settings->add(new admin_setting_heading('assignsubmission_engcentral/defaultsettings', get_string('defaultsettings', 'assignsubmission_engcentral'), ''));
$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/simpleui', get_string('simpleui', 'assignsubmission_engcentral'), '', 0));
$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/watchmode', get_string('watchmode', 'assignsubmission_engcentral'), '', 1));
$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/speakmode', get_string('speakmode', 'assignsubmission_engcentral'), '', 1));
$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/speaklitemode', get_string('speaklitemode', 'assignsubmission_engcentral'), '', 0));
$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/learnmode', get_string('learnmode', 'assignsubmission_engcentral'), '', 0));
$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/hiddenchallengemode', get_string('hiddenchallengemode', 'assignsubmission_engcentral'), '', 0));
$settings->add(new admin_setting_configcheckbox('assignsubmission_engcentral/lightboxmode', get_string('lightboxmode', 'assignsubmission_engcentral'), '', 1));


