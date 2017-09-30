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
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2017 Yamaguchi University (info-cc@ml.cc.yamaguchi-u.ac.jp)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*global $:false */
/* global $ */

var addEvent;
var removeEvent;
var videoTag;
var iframe;
var serviceURL;
var trigger = false;
var played = false;
var playing = false;
var timeupdated = false;
var seeked = false;
var paused = false;
var ended = false;
var ratechange = false;

(function () {
    if (typeof window.console === "undefined") {
        window.console = {};
    }
    if (typeof window.console.log !== "function") {
        window.console.log = function () {};
    }
})();

/**
 * This function retrieve os type.
 * @param none.
 * @return {string} - os type.
 */
function getOperatingSystem() {
    var os, ua = navigator.userAgent;

    if (ua.match(/iPhone|iPad|iPod/)) {
        os = "iOS";
    } else if (ua.match(/Android|android/)) {
        os = "Android";
    } else if (ua.match(/Linux|linux/)) {
        os = "Linux";
    } else if (ua.match(/Win(dows)/)) {
        os = "Windows";
    }
    else if (ua.match(/Mac|PPC/)) {
        os = "Mac OS";
    }
    else {
        os = "Other";
    }

    return os;
}

/**
 * Attatch onlaod event.
 * @param none.
 * @return nothing.
 */
window.onload = function() {
    var mobile = false;

    var os = getOperatingSystem();
    if (os == 'iOS'|| os == 'Android') {
        mobile = true;
    }

    if (document.attachEvent) {
        addEvent = function(node,type,handler){
            node.attachEvent('on' + type, function(evt){
                handler.call();
            });
        };
    } else if(document.addEventListener) {
        addEvent = function(node,type,handler){
            node.addEventListener(type,handler, false);
        };
    }

    if (document.detachEvent) {
        removeEvent = function(node,type,handler){
            node.detachEvent('on' + type, function(evt){
                handler.call();
            });
        };
    } else if(document.removeEventListener) {
        removeEvent = function(node,type,handler){
            node.removeEventListener(type,handler, false);
        };
    }

    var iframes = document.getElementsByTagName("iframe");
    iframe = iframes[0];

    var contentDocument = iframe.contentDocument || iframe.contentWindow.document;

    var videoTags = contentDocument.getElementsByTagName("video");
    videoTag = videoTags[0];
    if (videoTag != null) {
        addEvent(videoTag, 'play', arisePlay);
        addEvent(videoTag, 'playing', arisePlaying);
        if (mobile == true) {
            addEvent(videoTag, 'timeupdate', ariseTimeupdate);
            addEvent(videoTag, 'seeked', ariseSeeked);
            addEvent(videoTag, 'pause', arisePause);
            addEvent(videoTag, 'ended', ariseEnded);
            addEvent(videoTag, 'ratechange', ariseRatechange);
        }
    }

    iframe.onload = function() {
        var videoTags = contentDocument.getElementsByTagName("video");
        videoTag = videoTags[0];
        if (videoTag != null) {
            addEvent(videoTag, 'play', arisePlay);
            addEvent(videoTag, 'playing', arisePlaying);
            if (mobile == true) {
                addEvent(videoTag, 'timeupdate', ariseTimeupdate);
                addEvent(videoTag, 'seeked', ariseSeeked);
                addEvent(videoTag, 'pause', arisePause);
                addEvent(videoTag, 'ended', ariseEnded);
                addEvent(videoTag, 'ratechange', ariseRatechange);
            }
        }
    };

    serviceURL = trigger_url.replace( /"/g , "");
};


/**
 * This function arise play event.
 * @param none.
 * @return nothing.
 */
function arisePlay()
{
    removeEvent(videoTag, 'play', arisePlay);
    played = true;
    if (trigger == false)  {
        trigger = true;
        triggerEvent();
    }
}

/**
 * This function arise playing event.
 * @param none.
 * @return nothing.
 */
function arisePlaying()
{
    removeEvent(videoTag, 'playing', arisePlaying);
    playing = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

/**
 * This function arise timeupdate event.
 * @param none.
 * @return nothing.
 */
function ariseTimeupdate()
{
    removeEvent(videoTag, 'timeupdate', ariseTimeupdate);
    timeupdated = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

function ariseSeeked()
{
    removeEvent(videoTag, 'seeked', ariseSeeked);
    seeked = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

/**
 * This function arise pause event.
 * @param none.
 * @return nothing.
 */
function arisePause()
{
    removeEvent(videoTag, 'pause', arisePause);
    paused = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

/**
 * This function arise ended event.
 * @param none.
 * @return nothing.
 */
function ariseEnded()
{
    removeEvent(videoTag, 'ended', ariseEnded);
    ended = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

/**
 * This function arise ratechange event.
 * @param none.
 * @return nothing.
 */
function ariseRatechange()
{
    removeEvent(videoTag, 'ratechange', ariseRatechange);
    ratechange = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

/**
 * This function trigger event.
 * @param none.
 * @return true if event transmission was succeed. Otherwise false.
 */
function triggerEvent()
{
    var fd = new FormData();
    var flag;

    fd.append('id', cmid);

    // Create tnrasmission data.
    var postData = {
        type : 'POST',
        data : fd,
        cache : false,
        contentType : false,
        scriptCharset : 'utf-8',
        processData : false,
        async : true,
        dataType : 'xml'
    };

    serviceURL = serviceURL + '?id=' + cmid;

    // Transmit data.
    $.ajax (
       serviceURL, postData
    )
    .done(function( xmlData ) {
        // Response is not XML.
        if (xmlData === null) {
            flag = false;
        }

        flag = true;
    })
    .fail(function( xmlData ) {
        flag = false;
    });

    return flag;
}
