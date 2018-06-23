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
 * This filter provides automatic embedding of H5P
 * activities when its name (title) is found inside every Moodle text
 *
 * @package    filter
 * @subpackage hvp
 * @copyright  2018 onwards Daniel Thies <dethies@gmail.com>
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com} (from filter_activitiesnames)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * H5P content filtering
 */
class filter_hvp extends moodle_text_filter {
    // Trivial-cache - keyed on $cachedcourseid and $cacheduserid.
    static $activitylist = null;
    static $cachedcourseid;
    static $cacheduserid;

    function filter($text, array $options = array()) {
        global $USER; // Since 2.7 we can finally start using globals in filters.

        $coursectx = $this->context->get_course_context(false);
        if (!$coursectx) {
            return $text;
        }
        $courseid = $coursectx->instanceid;

        // Initialise/invalidate our trivial cache if dealing with a different course.
        if (!isset(self::$cachedcourseid) || self::$cachedcourseid !== (int)$courseid) {
            self::$activitylist = null;
        }
        self::$cachedcourseid = (int)$courseid;
        // And the same for user id.
        if (!isset(self::$cacheduserid) || self::$cacheduserid !== (int)$USER->id) {
            self::$activitylist = null;
        }
        self::$cacheduserid = (int)$USER->id;

        /// It may be cached

        if (is_null(self::$activitylist)) {
            self::$activitylist = array();

            $modinfo = get_fast_modinfo($courseid);
            if (!empty($modinfo->cms)) {
                self::$activitylist = array(); // We will store all the created filters here.

                // Create array of visible activities sorted by the name length (we are only interested in properties name and url).
                $sortedactivities = array();
                foreach ($modinfo->cms as $cm) {
                    // Use normal access control and visibility, but exclude labels and hidden activities.
                    if ($cm->visible and $cm->has_view() and $cm->uservisible) {
                        $sortedactivities[] = (object)array(
                            'name' => $cm->name,
                            'url' => $cm->url,
                            'id' => $cm->id,
                            'namelen' => -strlen($cm->name), // Negative value for reverse sorting.
                        );
                    }
                }
                // Sort activities by the length of the activity name in reverse order.
                core_collator::asort_objects_by_property($sortedactivities, 'namelen', core_collator::SORT_NUMERIC);

                // If filter applies to headers, embed in div rather that iframe.
                $embedtype = array_key_exists('hvp', filter_get_string_filters()) ? 'div' : 'iframe';
                foreach ($sortedactivities as $cm) {
                    $title = s(trim(strip_tags($cm->name)));
                    $currentname = trim($cm->name);
                    $entitisedname  = s($currentname);
                    // Avoid empty activity names.
                    if (!empty($title) && get_coursemodule_from_id('hvp', $cm->id)) {
                        if ($embedtype == 'div') {
                            $id = get_coursemodule_from_id('hvp', $cm->id)->instance;
                            $href_tag_begin = html_writer::start_tag('div',
                                    array('class' => 'h5p-content', 'data-content-id' => self::embed_hvp($cm->id)));
                            self::$activitylist[$cm->id] = new filterobject($currentname, $href_tag_begin, '</div>', false, true, ' ');
                        } else {
                            $href_tag_begin = html_writer::start_tag('iframe', array('class' => 'h5p-content',
                                'width' => '400',
                                'height' => '400',
                                'frameborder' => '0',
                                'allowfullscreen' => 'allowfullscreen',
                                'src' => new moodle_url('/mod/hvp/embed.php', array('id' => $cm->id))));
                            self::$activitylist[$cm->id] = new filterobject($currentname, $href_tag_begin, '</iframe>', false, true, ' ');
                        }
                        if ($currentname != $entitisedname) {
                            // If name has some entity (&amp; &quot; &lt; &gt;) add that filter too. MDL-17545.
                            self::$activitylist[$cm->id.'-e'] = new filterobject($entitisedname, $href_tag_begin, '</iframe>', false, true);
                        }
                    }
                }
            }
        }

        $filterslist = array();
        if (self::$activitylist) {
            $cmid = $this->context->instanceid;
            if ($this->context->contextlevel == CONTEXT_MODULE && isset(self::$activitylist[$cmid])) {
                // remove filterobjects for the current module
                $filterslist = array_values(array_diff_key(self::$activitylist, array($cmid => 1, $cmid.'-e' => 1)));
            } else {
                $filterslist = array_values(self::$activitylist);
            }
        }

        if ($filterslist) {
            return $text = filter_phrases($text, $filterslist);
        } else {
            return $text;
        }
    }
 
    function embed_hvp($id) {
        global $CFG, $DB, $PAGE, $OUTPUT;
        require_once($CFG->dirroot . "/config.php");
        require_once($CFG->dirroot . "/mod/hvp/locallib.php");

        if (! $cm = get_coursemodule_from_id('hvp', $id)) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('coursemisconf');
        }

        /*
        // Load H5P Core.
        $core = \mod_hvp\framework::instance();

        // Load H5P Content.
        $content = $core->loadContent($cm->instance);
        if ($content === null) {
            print_error('invalidhvp');
        }
         */
        // Set up view assets.
        $view    = new \mod_hvp\view_assets($cm, $course);
        $content = $view->getcontent();
        $view->validatecontent();

        // Add H5P assets to page.
        $view->addassetstopage();
        $view->logviewed();
        return $content['id'];
    }
} 
