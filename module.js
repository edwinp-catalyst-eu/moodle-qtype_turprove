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
 * JavaScript required by the turprove question type.
 *
 * @package    qtype
 * @subpackage turprove
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


M.qtype_turprove = M.qtype_turprove || {};


M.qtype_turprove.init = function (Y, questiondiv) {

    // All the code below taken from the multianswer question type

    /*
    Y.one(questiondiv).all('span.subquestion').each(function(subqspan) {
        var feedbackspan = subqspan.one('.feedbackspan');
        if (!feedbackspan) {
            return;
        }

        var overlay = new Y.Overlay({
            srcNode: feedbackspan,
            visible: false,
            align: {
                node: subqspan,
                points: [Y.WidgetPositionAlign.TC, Y.WidgetPositionAlign.BC]
            },
            constrain: subqspan.ancestor('div.que'),
            zIndex: 1,
            preventOverlap: true
        });
        overlay.render();

        Y.on('mouseover', function() { overlay.show(); }, subqspan);
        Y.on('mouseout', function() { overlay.hide(); }, subqspan);

        feedbackspan.removeClass('accesshide');
    });
    */

};