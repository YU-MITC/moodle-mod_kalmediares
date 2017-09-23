var createObjectURL
= window.URL && window.URL.createObjectURL ? function(file) { return window.URL.createObjectURL(file); }
    : window.webkitURL && window.webkitURL.createObjectURL ? function(file) { return window.webkitURL.createObjectURL(file); }
        : undefined;

var revokeObjectURL
= window.URL && window.URL.revokeObjectURL ? function(file) { return window.URL.revokeObjectURL(file); }
    : window.webkitURL && window.webkitURL.revokeObjectURL ? function(file) { return window.webkitURL.revokeObjectURL(file); }
        : undefined;

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

function arisePlay()
{
    removeEvent(videoTag, 'play', arisePlay);
    played = true;
    if (trigger == false)  {
        trigger = true;
        triggerEvent();
    }
}

function arisePlaying()
{
    removeEvent(videoTag, 'playing', arisePlaying);
    playing = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

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

function arisePause()
{
    removeEvent(videoTag, 'pause', arisePause);
    paused = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

function ariseEnded()
{
    removeEvent(videoTag, 'ended', ariseEnded);
    ended = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

function ariseRatechange()
{
    removeEvent(videoTag, 'ratechange', ariseRatechange);
    ratechange = true;
    if (trigger == false) {
        trigger = true;
        triggerEvent();
    }
}

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
