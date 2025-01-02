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
 * Scripts for mod_kalmediaassign
 *
 * @copyright (C) 2016-2025 Yamaguchi University (gh-cc@mlex.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_kalmediaassign/grading
 */

define(['jquery'], function($) {

    return {
        /**
         * Initial function.
         * @access public
         * @param {string} argWidth - width of modal content.
         * @param {string} argHeight - height of modal content.
         */
        init: function(argWidth, argHeight) {

            var modalX = 0;
            var modalY = 0;
            var modalWidth = argWidth;
            var modalHeight = argHeight;

            /**
             * This function centerize modal window.
             */
            function centeringModalSyncer() {

                // Get width and height of window.
                var w = $(window).width();
                var h = $(window).height();

                var width = parseInt(modalWidth);
                var height = parseInt(modalHeight);

                $("#modal_content").css({"width": (width + 40) + "px", "height": (height + 40) + "px"});

                // Get width and height of content area.
                var cw = $("#modal_content").outerWidth();
                var ch = $("#modal_content").outerHeight();

                // Execute centering.
                $("#modal_content").css({"left": ((w - cw) / 2) + "px", "top": ((h - ch) / 2) + "px"});
            }

            /**
             * This function print modal window.
             * @return {bool} - if modal window opend, return true. Otherwise, return false.
             */
            function fadeInModalWindow() {
                // All contents in web page release focus.
                $(this).blur();
                // Precent dupulicate execute of modal window.
                if ($("#modal_window")[0]) {
                    return false;
                }

                var width = parseInt(modalWidth);
                var height = parseInt(modalHeight);

                $("#modal_content").css({"width": (width + 40) + "px", "height": (height + 40) + "px"});

                // Record current scroll position.
                var dElm = document.documentElement;
                var dBody = document.body;
                modalX = dElm.scrollLeft || dBody.scrollLeft; // Get x value of current position.
                modalY = dElm.scrollTop || dBody.scrollTop; // Get y valueion of current position.
                // Create modal window and a content area.
                $("body").append("<div id=\"modal_window\"></div>");
                $("#modal_window").fadeIn("normal");

                // Centering content area.
                centeringModalSyncer();
                // Fade-in content area.
                $("#modal_content").fadeIn("normal");

                return true;
            }

            /**
             * This function delete content area and modal window.
             */
            function fadeOutModalWindow() {
                // Rescore scroll position to web brawser.
                window.scrollTo(modalX, modalY);

                // Fade-put content area and modal window.
                $("#modal_content,#modal_window").fadeOut("normal", function() {
                    $("#modal_window").remove();
                    $("#modal_content").html("");
                });
            }

            /**
             * This function create preview panel
             * @param {Event} event - Event object
             */
            function createPreviewPanel(event) {
                // View modal window.
                fadeInModalWindow();

                var entryId = event.target.id;
                entryId = entryId.substr(6);
                var element = $("#hidden_markup_" + entryId);
                var str = '';
                str += "<center>";

                if (element !== null) {
                    str += element.html();
                } else {
                    str += "Sorry! Media not found.";
                }

                str += "</center>";
                str += "<center>";
                str += "<input type=\"button\" id=\"cancel_btn\" value=\"Close\">";
                str += "</center>";

                $("#modal_content").append(str);

                $("#cancel_btn").on("click", function() {
                    fadeOutModalWindow();
                });
            }

            // Entry a uload event callback.
            $(window).on("unload", function() {
                // If window is resize, call modal window centerize function.
                window.onresize = centeringModalSyncer;
            });

            $(".media_thumbnail_cl").on("click", function(e) {
                createPreviewPanel(e);
            });
        }
    };

});
