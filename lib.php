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
 * Block summary library.
 *
 * @package    block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Create data section to template.
 *
 * @param \stdClass[] $sections
 * @return array
 */
function block_summary_set_data_section_to_template($sections)
{
    global $COURSE;

    // Get current section.
    $currentsectionid = optional_param('section', 1, PARAM_INT);

    // Init sections data to template.
    $sectionsdata = [];

    // Saved children sections.
    $sectionstodepth = [];

    // True if next main section has current child.
    $hascurrentchild = false;

    // Last id section where saved child section.
    $lastnodepthsection = 0;

    // If is section 0, current is 1.
    $currentsectionid = $currentsectionid !== 0 ? $currentsectionid : 1;

    // Check if user has hidden section view capability.
    $seesectionhide = has_capability('moodle/course:viewhiddensections', context_course::instance($COURSE->id));

    // Get all info modules course (for availability section information).
    $sectionsavailabilitiesinfos = get_fast_modinfo($COURSE->id);

    // Set section course data for template.
    foreach ($sections as $section) {
        // Get course section info.
        $coursection = $sectionsavailabilitiesinfos->get_section_info($section->section);

        // CHeck if section is hide.
        $hide = $section->visible === '0';

        $availabilitiesinfosjson = $coursection->availability;

        // Check if section has restriction.
        $hasrestriction = false;
        $hasrestrictionvisibility = false;
        if (isset($availabilitiesinfosjson)) {
            $availabilitiesinfos = json_decode($availabilitiesinfosjson);
            if (isset($availabilitiesinfos)) {
                if (count($availabilitiesinfos->c) > 0) {
                    $hasrestriction = true;
                    $hasrestrictionvisibility = !$coursection->availableinfo && !$coursection->uservisible;
                }
            }
        }

        // Get other section data.
        $name = $section->name ?? '(' . get_string('sectionname', "format_$COURSE->format") . " " . $section->section . ')';
        $depth = (int) $section->depth;
        $positon = (int) $section->section;

        // Create section data for template.
        $sectiondata = [
            'id' => $section->id,
            'name' => $name,
            // If is hide and no has capability, user not view this.
            'visiblecapability' => !($hide || $hasrestrictionvisibility) || $seesectionhide,
            'hide' => $hide,
            // If it has restriction and no has capability, user not view icon.
            'hasrestriction' => $hasrestriction,
            'position' => $section->section,
            'url' => course_get_url($COURSE, $section)->out(false),
            'sections' => [],
            'iscurrent' => $currentsectionid === $positon,
            'i18nsummarysublistof' => get_string('summarysublistof', 'block_summary')
        ];

        // Main section.
        if ($depth === 0) {
            // Has children sections.
            if (!empty($sectionstodepth)) {
                $sectionsdata[$lastnodepthsection]['haschild'] = true;
                $sectionsdata[$lastnodepthsection]['sections'] = $sectionstodepth;

                // Children or self are current section.
                if ($hascurrentchild || $sectionsdata[$lastnodepthsection]['iscurrent']) {
                    $sectionsdata[$lastnodepthsection]['hascurrentchild'] = true;
                    $hascurrentchild = false;
                }

                // Init children section list.
                $sectionstodepth = [];
            }
            // Add section to list.
            $sectionsdata[] = $sectiondata;
            $lastnodepthsection = count($sectionsdata) - 1;
            continue;
        }

        // Is current section.
        if ($sectiondata['iscurrent']) {
            $hascurrentchild = true;
        }

        // Add section to children list.
        $sectionstodepth[] = $sectiondata;
    }

    // Same action to last section for add children if exist.
    if (!empty($sectionstodepth)) {
        $sectionsdata[$lastnodepthsection]['haschild'] = true;
        $sectionsdata[$lastnodepthsection]['sections'] = $sectionstodepth;


        if ($hascurrentchild || $sectionsdata[$lastnodepthsection]['iscurrent']) {
            $sectionsdata[$lastnodepthsection]['hascurrentchild'] = true;
        }
    }
    return $sectionsdata;
}
