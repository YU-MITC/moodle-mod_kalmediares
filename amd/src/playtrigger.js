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
 * Scripts for mod_kalmediares
 *
 * @copyright (C) 2016-2025 Yamaguchi University (gh-cc@mlex.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_kalmediares/playtrigger
 */

define(['jquery'], function($) {

    return {
        /**
         * Initial function.
         * @access public
         * @param {string} serviceURL - url of event record page.
         * @param {string} cmid - id of content module.
         */
        init: function(serviceURL, cmid) {

            var videoTags;
            var iframeTags;
            var trigger = false;
            var listener = false;
            var checkAction = null;
            var scchange = "webkitfullscreenchange mozfullscreenchange fullscreenchange MSFullscreenChange";

            /**
             * This function retrieve os type.
             * @return {string} - os type.
             */
            function getOperatingSystem() {
                var os;
                var ua = navigator.userAgent;

                if (ua.match(/iPhone|iPad|iPod|IPHONE|IPAD|IPOD|iphone|ipad|ipod/)) {
                    os = "iOS";
                } else if (ua.match(/Android|android|ANDROID/)) {
                    os = "Android";
                } else if (ua.match(/Linux|linux|LINUX|ubuntu|Ubuntu|UBUNTU/)) {
                    os = "Linux";
                } else if (ua.match(/Win(dows)|WIN(DOWS)/)) {
                    os = "Windows";
                } else if (ua.match(/Mac|PPC/)) {
                    os = "Mac OS";
                } else if (ua.match(/CrOS/)) {
                    os = "Chrome OS";
                } else if (ua.match(/mobile|Mobile|MOBILE/)) {
                    os = "Mobile";
                } else {
                    os = "Other";
                }

                return os;
            }

            /**
             * This function trigger event.
             * @return {bool} - true if event transmission was succeed. Otherwise false.
             */
            function triggerEvent() {
                var fd = new FormData();
                var flag;

                fd.append('id', cmid);

                // Create tnrasmission data.
                var postData = {
                    type: 'POST',
                    data: fd,
                    cache: false,
                    contentType: false,
                    scriptCharset: 'utf-8',
                    processData: false,
                    async: true,
                    dataType: 'xml'
                };

                serviceURL = serviceURL + '?id=' + cmid;

                // Transmit data.
                $.ajax(
                   serviceURL, postData
                )
                .done(function(xmlData) {
                    // Response is not XML.
                    if (xmlData === null) {
                        flag = false;
                    }

                    flag = true;
                })
                .fail(function(xmlData) {
                    window.console.dir(xmlData);
                    flag = false;
                });

                return flag;
            }

            /**
             * This function arise play event.
             */
            function arisePlay() {
                videoTags.off("play", "**");
                if (trigger === false) {
                    trigger = true;
                    triggerEvent();
                }
            }

            /**
             * This function arise playing event.
             */
            function arisePlaying() {
                videoTags.off("playing", "**");
                if (trigger === false) {
                    trigger = true;
                    triggerEvent();
                }
            }

            /**
             * This function arise timeupdate event.
             */
            function ariseTimeupdate() {
                videoTags.off("timeupdate", "**");
                if (trigger === false) {
                    trigger = true;
                    triggerEvent();
                }
            }

            /**
             * This function arise seeked event.
             */
            function ariseSeeked() {
                videoTags.off("seeked", "**");
                if (trigger === false) {
                    trigger = true;
                    triggerEvent();
                }
            }

            /**
             * This function arise pause event.
             */
            function arisePause() {
                videoTags.off("pause", "**");
                if (trigger === false) {
                    trigger = true;
                    triggerEvent();
                }
            }

            /**
             * This function arise ended event.
             */
            function ariseEnded() {
                videoTags.off("ended", "**");
                if (trigger === false) {
                    trigger = true;
                    triggerEvent();
                }
            }

            /**
             * This function arise ratechange event.
             */
            function ariseRatechange() {
                videoTags.off("ratechange", "**");
                if (trigger === false) {
                    trigger = true;
                    triggerEvent();
                }
            }

            /**
             * This function adds callback functions to video element.
             * @param {object} element - element object for video tag.
             */
            function setCallback(element) {
                var mobile = false;
                var os = getOperatingSystem();

                if (os == 'iOS' || os == 'Android' || os == 'Chrome OS' || os == 'Mobile') {
                    mobile = true;
                }

                if (element !== null && element.length >= 1) {
                    element.on("play", function() {
                        arisePlay();
                    });
                    element.on("playing", function() {
                        arisePlaying();
                    });

                    if (mobile === true) {
                        element.on("timeupdate", function() {
                            ariseTimeupdate();
                        });
                        element.on("seeked", function() {
                            ariseSeeked();
                        });
                        element.on("pause", function() {
                            arisePause();
                        });
                        element.on("ended", function() {
                            ariseEnded();
                        });
                        element.on("ratechange", function() {
                            ariseRatechange();
                        });
                    }

                    listener = true;
                }
            }

            /**
             * This function retrieve video element.
             */
            function checkElement() {
                if (listener === false && trigger === false) {
                    videoTags = $("video");
                    if (videoTags !== null && videoTags.length >= 1) {
                        setCallback(videoTags);
                    } else {
                        iframeTags = $("iframe");
                        if (iframeTags !== null && iframeTags.length >= 1) {
                            videoTags = iframeTags.eq(0).contents().find("video");
                            if (videoTags !== null && videoTags.length >= 1) {
                                setCallback(videoTags);
                            }
                        }
                    }
                }

                if (listener === true && checkAction !== null) {
                    clearInterval(checkAction);
                }
            }

            if (serviceURL !== null && serviceURL !== "") {
                $(document).ready(function() {
                    if (trigger === false && listener === false) {
                        checkElement();
                    }

                    if (checkAction === null) {
                        checkAction = setInterval(checkElement, 1000);
                    }
                });

                $("iframe").on("load", function() {
                    if (trigger === false && listener === false) {
                        checkElement();
                    }

                    if (checkAction === null) {
                        checkAction = setInterval(checkElement, 1000);
                    }
                });

                $(window).on("load", function() {
                    if (trigger === false && listener === false) {
                        checkElement();
                    }

                    if (checkAction === null) {
                        checkAction = setInterval(checkElement, 1000);
                    }
                });

                $(window).on(scchange, function() {
                    if (trigger === false && listener === false) {
                        checkElement();
                    }

                    if (checkAction === null) {
                        checkAction = setInterval(checkElement, 1000);
                    }
                });
            }
        }
    };
});
