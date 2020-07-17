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
 * Admin settings.
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Note doco found in lib/adminlib.php.

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox('cado/showlogo',
        get_string('showlogo', 'cado'), get_string('showlogo_desc', 'cado'), 0));

    $activityoptions = ["assign" => get_string('assign', 'cado')
        , "forum" => get_string('forum', 'cado')
        , "quiz" => get_string('quiz', 'cado')];

    $settings->add(new admin_setting_configmultiselect('cado/activityoptions',
        get_string('activityoptions', 'cado'), get_string('activityoptions_desc', 'cado'),
        array_keys($activityoptions), $activityoptions));

    $settings->add(new admin_setting_configcheckbox('cado/inchidden',
        get_string('inchidden', 'cado'), get_string('inchidden_desc', 'cado'), 0));

    $cadooptions = [
        "summary" => get_string('summary', 'cado')
        , "cadobiblio" => get_string('cadobiblio', 'cado')
        , "schedule" => get_string('schedule', 'cado')
        , "tags" => get_string('tags', 'cado')
        , "cadocomment" => get_string('cadocomment', 'cado')
        , "sitecomment" => get_string('sitecomment', 'cado')];

    $settings->add(new admin_setting_configmultiselect('cado/cadooptions',
        get_string('cadooptions', 'cado'), get_string('cadooptions_desc', 'cado', get_string('general', 'cado')),
        array_keys($cadooptions), $cadooptions));

    $settings->add(new admin_setting_configtext('cado/tagslist',
        get_string('taginclude', 'cado'), get_string('taginclude_desc', 'cado'), '', PARAM_TEXT, 50));

    $settings->add(new admin_setting_configcheckbox('cado/tagschedule',
        get_string('tagschedule', 'cado'), get_string('tagschedule_desc', 'cado'), 0));

    $settings->add(new admin_setting_configcheckbox('cado/sumschedule',
        get_string('sumschedule', 'cado'), get_string('sumschedule_desc', 'cado'), 0));

    $settings->add(new admin_setting_confightmleditor('cado/sitecomment',
        get_string('sitecomment', 'cado'), get_string('sitecomment_desc', 'cado') , ''));
        // Note default param_raw and size textbox fine otherwise add cols, rows.


}
