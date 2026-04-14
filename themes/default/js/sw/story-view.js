/* Helpers and constructor wrapped in strict mode */
(function($, window, document){
  "use strict";
  var self = this;
var svTransitionEnd = 'webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend';

/**
 * @class StoryViewFlagger
 * @desc --
 * @param {} view -
 */
function StoryViewFlagger() {
    this.fired = false;
    this.set = function() {
        this.fired = true;
    }
    return this;
}

function getStoryAudioMuteIcon(muted) {
    return muted
        ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14.6,4.6c-0.9-0.6-2.1-0.8-3.1-0.4c-1.1,0.4-2.1,0.9-2.9,1.6L6.2,7.6H5c-1.7,0-3,1.3-3,3v2.9c0,1.7,1.3,3,3,3h1.2 l2.3,1.8c0.9,0.7,1.9,1.2,2.9,1.6c0.4,0.1,0.8,0.2,1.2,0.2c0.7,0,1.4-0.2,2-0.6c0.9-0.6,1.4-1.6,1.4-2.7V7.3 C16,6.2,15.5,5.2,14.6,4.6z M5.5,14.4H5c-0.6,0-1-0.4-1-1v-2.9c0-0.6,0.4-1,1-1h0.5V14.4z M14,16.7c0,0.4-0.2,0.8-0.6,1 c-0.4,0.3-0.9,0.3-1.3,0.2c-0.9-0.3-1.7-0.8-2.4-1.3l-2.2-1.7V9.1l2.2-1.7c0.7-0.6,1.5-1,2.4-1.3c0.4-0.2,0.9-0.1,1.3,0.2 c0.3,0.2,0.6,0.6,0.6,1V16.7z"/><path fill="currentColor" d="M21.4,12l0.7-0.7c0.4-0.4,0.4-1,0-1.4s-1-0.4-1.4,0L20,10.6l-0.7-0.7c-0.4-0.4-1-0.4-1.4,0s-0.4,1,0,1.4l0.7,0.7l-0.7,0.7 c-0.4,0.4-0.4,1,0,1.4c0.2,0.2,0.5,0.3,0.7,0.3s0.5-0.1,0.7-0.3l0.7-0.7l0.7,0.7c0.2,0.2,0.5,0.3,0.7,0.3s0.5-0.1,0.7-0.3 c0.4-0.4,0.4-1,0-1.4L21.4,12z"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3,13.4c0,1.7,1.3,3,3,3h1.2l2.3,1.8c0.9,0.7,1.9,1.2,2.9,1.6c0.4,0.1,0.8,0.2,1.2,0.2c0.7,0,1.4-0.2,2-0.6 c0.9-0.6,1.4-1.6,1.4-2.7V7.3c0-1.1-0.5-2-1.4-2.7c-0.9-0.6-2.1-0.8-3.1-0.4c-1.1,0.4-2.1,0.9-2.9,1.6L7.2,7.6H6c-1.7,0-3,1.3-3,3 V13.4z M8.5,9.1l2.2-1.7c0.7-0.6,1.5-1,2.4-1.3c0.4-0.2,0.9-0.1,1.3,0.2c0.3,0.2,0.5,0.6,0.5,1v9.4c0,0.4-0.2,0.8-0.6,1 c-0.4,0.3-0.9,0.3-1.3,0.2c-0.9-0.3-1.7-0.8-2.4-1.3l-2.2-1.7V9.1z M5,10.6c0-0.6,0.4-1,1-1h0.5v4.9H6c-0.6,0-1-0.4-1-1V10.6z"/><path fill="currentColor" d="M19.7,9.3c-0.4-0.4-1-0.4-1.4,0c-0.4,0.4-0.4,1,0,1.4c0.3,0.3,0.5,0.8,0.5,1.3s-0.2,1-0.5,1.3c-0.4,0.4-0.4,1,0,1.4 c0.2,0.2,0.4,0.3,0.7,0.3c0.3,0,0.5-0.1,0.7-0.3c0.7-0.7,1-1.6,1-2.7S20.4,10,19.7,9.3z"/></svg>';
}

function getStoryAudioPlayIcon(playing) {
    return playing
        ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><rect fill="currentColor" x="6" y="5" width="4" height="14"/><rect fill="currentColor" x="14" y="5" width="4" height="14"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8 5v14l11-7z"/></svg>';
}

/**
 * @class StoryView
 * @desc --
 * @param {} view -
 */
function StoryView(options) {
    options = options || {};
    var closeIcon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADQAAAA0CAYAAADFeBvrAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAJySURBVGhD7ZnPThRBEIcXuRAiCSe94ZVXEHwBPfIGgM+iMR68obwGCRfinwfR6FGPetGDya5fTVfFEJadnurqndH0l3R6p7vq1/0dgAw7azQajcZ/z3w+v79YLHb1MRRytxh7+lgfZJ4xhF+MY10OgbyHjG8ILZgvmLZ1qw4c8koOM3gWTnW7CHIOiPyekhOsvWOqI0X4NRlDjKBIiv4bMgZ78VKELpUxxAhcUvTdKmNQEydF2PMUuxoxgkFS1PfKGNReMW1qqw9C7sktU2Q/UgtZUtRlyxj0PNZ2HwTsMH5qXhbUCycasRT2B8sojzTCD4cfyw01MAuph6VSrLtk6DvTiHIIeyo31OwspB6uSfHslTln2tCYGAgtkmKejoxBuFfqBR+nJWNwyGApD2uRMWpLEf2GaT0yRi2pUWSMaKlRZYwoqUnIGKVSk5IxuNTLdL1h0PeBaXIyh4wf6YrDoE8IeUkMgcscMFwyRqc0BSkuUSxjdEpjSnF4mIzRKY0hxaHhMkantE4pDqsmY3RKPS+JIXCIS4ae93JDfcxC6qGeFOHe95nXTBvMg//4Sj3ESxFaJKMxknMqN0y7eUg9xEkRFiJjsD6eFFlbBH1NsfnQs1TGYN8j9ZtpXyN8ELDXpQ2gT8ZwSh1puw8y5If5MsX1Q22WjEH9CSNLirJPjB1t9UPWNkHyv+WVUDNIxqCvV4rtL4wH2lIOmSul2HPJGPTfKsXyZ0acjEG2SL1Nx/yFtSIZg5wbUjzWkTE4Q6TkW4AOPp8xFcsY5ImU/DaT7I9M9b+a5JA7HPaEcahLoZC/T/YR811dajQajUbjH2E2+wNwCp/oUNHGtwAAAABJRU5ErkJggg==';
    var replyLang = options.lang || {};
    var replyPlaceholder = replyLang.replyPlaceholder || 'Send message...';
    var replySendLabel = replyLang.replySendLabel || 'Send Message';
    var audioMuteLabel = replyLang.audioMute || 'Mute';
    var audioPlayLabel = replyLang.audioPlay || 'Play';
    var audioTipLabel = replyLang.audioUnmuteTip || 'Tap to unmute';
    var textMoreLabel = replyLang.textMore || 'Read more';
    var textLessLabel = replyLang.textLess || 'Show less';
    var fsvTemplate = [
        '<div class="sv-view flex_ tabing sv-view--with-reply">',
        '<span class="close"><img src="' + (options.closeIcon || closeIcon) + '" /></span>',
        '<div class="loading"><span></span></div>',
        '<div class="profile">',
        '<div class="image"></div>',
        '<span class="name"></span>',
        '</div>',
        '<div class="story-audio-controls">',
        '<button type="button" class="story-audio-btn story-audio-mute" aria-label="' + audioMuteLabel + '">',
        getStoryAudioMuteIcon(false),
        '</button>',
        '<button type="button" class="story-audio-btn story-audio-play" aria-label="' + audioPlayLabel + '">',
        getStoryAudioPlayIcon(false),
        '</button>',
        '</div>',
        '<div class="story-audio-tip">' + audioTipLabel + '</div>',
        '<div class="story-audio-badge"></div>',
        '<div class="hereText"></div>',
        '<div class="content">',
        '<div class="media-container"></div>',
        '<div class="story-overlays">',
        '<a class="story-overlay story-overlay-link" href="#" target="_blank" rel="noopener"></a>',
        '<a class="story-overlay story-overlay-mention" href="#"></a>',
        '<div class="story-overlay story-overlay-sticker"><img alt="" /></div>',
        '</div>',
        '<div class="story-access-overlay">',
        '<div class="story-access-content">',
        '<div class="story-access-text"></div>',
        '<div class="story-access-actions">',
        '<button type="button" class="story-access-btn story-access-follow"></button>',
        '<button type="button" class="story-access-btn story-access-subscribe"></button>',
        '</div>',
        '</div>',
        '</div>',
        '</div>',
        '<div class="story-reactions" role="group" aria-label="Reactions"></div>',
        '<div class="story-quick-replies" role="group" aria-label="Quick replies"></div>',
        '<div class="story-reply">',
        '<div class="story-reply-inner">',
        '<input type="text" class="story-reply-input" placeholder="' + replyPlaceholder + '" autocomplete="off" />',
        '<button type="button" class="story-reply-send" aria-label="' + replySendLabel + '">',
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.2 21.3l19.2-9.3L2.2 2.7l1.8 7.2 9.4 2.1-9.4 2.1-1.8 7.2z"/></svg>',
        '</button>',
        '</div>',
        '<div class="story-reply-status" aria-live="polite"></div>',
        '</div>',
        '</div>'
    ];
    this.options = options;
    this.lang = replyLang;
    this.replyPlaceholder = replyPlaceholder;
    this.currentUserId = Number(options.currentUserId || 0);
    this.requestUrl = options.requestUrl || (typeof siteurl !== 'undefined' ? siteurl + 'requests/request.php' : 'requests/request.php');
    this.baseUrl = options.baseUrl || (typeof siteurl !== 'undefined' ? siteurl : '');
    this.reactionSet = Array.isArray(options.reactionSet) ? options.reactionSet : [];
    this.accessLabels = {
        followers: options.accessFollowers || '',
        subscribers: options.accessSubscribers || '',
        followBtn: options.accessFollowBtn || '',
        subscribeBtn: options.accessSubscribeBtn || ''
    };
    this.audioLabels = {
        mute: replyLang.audioMute || 'Mute',
        unmute: replyLang.audioUnmute || 'Unmute',
        play: replyLang.audioPlay || 'Play',
        pause: replyLang.audioPause || 'Pause',
        unmuteTip: replyLang.audioUnmuteTip || 'Tap to unmute'
    };
    this.textToggleLabels = {
        more: textMoreLabel,
        less: textLessLabel
    };
    this.itemSelector = '.story-view-item';
    this.fsvTemplate = fsvTemplate.join('\n');
    this.container = $(options.container);
    this.reset();
    this.init();
}

function getActiveStoryContext() {
    var storyId = 0;
    var ownerId = 0;
    var $media = $('.sv-view .media-container .current-media');
    if ($media.length) {
        storyId = Number($media.attr('id') || $media.data('id') || 0);
    }
    var $activeStory = $('.story-view-item.activated');
    if ($activeStory.length) {
        ownerId = Number($activeStory.data('story-id') || 0);
    }
    return {
        storyId: storyId,
        ownerId: ownerId
    };
}

function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute('content') || '') : '';
}

function fallbackStoryReaction(reaction) {
    var ctx = getActiveStoryContext();
    if (!ctx.storyId) {
        return;
    }
    var data = 'f=story_reaction&story_id=' + encodeURIComponent(ctx.storyId) +
        '&reaction=' + encodeURIComponent(reaction);
    var csrfToken = getCsrfToken();
    if (csrfToken) {
        data += '&csrf_token=' + encodeURIComponent(csrfToken);
    }
    $.ajax({
        type: 'POST',
        url: typeof siteurl !== 'undefined' ? siteurl + 'requests/request.php' : 'requests/request.php',
        data: data,
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response && response.status === 'success') {
                $('.story-reaction-btn').removeClass('is-selected');
                if (response.action !== 'removed') {
                    $('.story-reaction-btn[data-reaction="' + reaction + '"]').addClass('is-selected');
                }
            }
        }
    });
}

function fallbackStoryReply(message) {
    var ctx = getActiveStoryContext();
    if (!ctx.storyId || !ctx.ownerId) {
        return;
    }
    var data = 'f=story_reply&story_id=' + encodeURIComponent(ctx.storyId) +
        '&story_uid=' + encodeURIComponent(ctx.ownerId) +
        '&message=' + encodeURIComponent(message);
    var csrfToken = getCsrfToken();
    if (csrfToken) {
        data += '&csrf_token=' + encodeURIComponent(csrfToken);
    }
    $.ajax({
        type: 'POST',
        url: typeof siteurl !== 'undefined' ? siteurl + 'requests/request.php' : 'requests/request.php',
        data: data,
        dataType: 'json',
        cache: false,
        success: function(response) {
            var statusText = '';
            var isError = true;
            if (response && response.status === 'success') {
                statusText = response.message || '';
                isError = false;
            } else if (response && response.message) {
                statusText = response.message;
            }
            if (statusText !== '') {
                var $status = $('.sv-view .story-reply-status');
                $status.toggleClass('is-error', isError).addClass('is-visible').text(statusText);
                setTimeout(function() {
                    $status.removeClass('is-visible is-error').text('');
                }, 3000);
            }
        }
    });
}

function bindGlobalStoryActions() {
    if (window.__storyViewActionsBound) {
        return;
    }
    window.__storyViewActionsBound = true;
    var lastStoryActionTime = 0;
    var lastStoryActionKey = '';

    function normalizeTarget(target) {
        if (!target) {
            return null;
        }
        return target.nodeType === 3 ? target.parentNode : target;
    }

    function findClosestAction(target) {
        var el = normalizeTarget(target);
        if (!el) {
            return null;
        }
        if (el.closest) {
            var reactionEl = el.closest('.story-reaction-btn');
            if (reactionEl) {
                return { type: 'reaction', el: reactionEl };
            }
            var replyEl = el.closest('.story-quick-reply-btn');
            if (replyEl) {
                return { type: 'reply', el: replyEl };
            }
        }
        var $el = $(el);
        if ($el.length) {
            var $reaction = $el.closest('.story-reaction-btn');
            if ($reaction.length) {
                return { type: 'reaction', el: $reaction.get(0) };
            }
            var $reply = $el.closest('.story-quick-reply-btn');
            if ($reply.length) {
                return { type: 'reply', el: $reply.get(0) };
            }
        }
        return null;
    }

    function shouldIgnoreDuplicate(key) {
        var now = Date.now();
        if (key && lastStoryActionKey === key && (now - lastStoryActionTime) < 400) {
            return true;
        }
        lastStoryActionKey = key;
        lastStoryActionTime = now;
        return false;
    }

    function handleCapturedStoryAction(event) {
        var action = findClosestAction(event.target);
        if (!action) {
            return;
        }
        if (document.body && !document.body.classList.contains('story-view--shown')) {
            return;
        }
        event.__storyHandled = true;
        var $btn = $(action.el);
        var text = action.type === 'reaction' ? ($btn.data('reaction') || $btn.text()) : ($btn.data('reply') || $btn.text());
        if (!text) {
            return;
        }
        var key = action.type + ':' + text;
        if (shouldIgnoreDuplicate(key)) {
            return;
        }
        var view = window.__activeStoryView;
        if (action.type === 'reaction') {
            if (view && typeof view.sendReaction === 'function') {
                view.sendReaction(String(text));
            } else {
                fallbackStoryReaction(String(text));
            }
        } else {
            if (view && typeof view.sendQuickReply === 'function') {
                view.sendQuickReply(String(text));
            } else {
                fallbackStoryReply(String(text));
            }
        }
    }
    $(document).on('click touchend', '.story-reaction-btn', function(event) {
        var view = window.__activeStoryView;
        if (event.originalEvent && event.originalEvent.__storyHandled) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        var reaction = $(this).data('reaction') || $(this).text();
        if (reaction) {
            if (view && typeof view.sendReaction === 'function') {
                view.sendReaction(String(reaction));
            } else {
                fallbackStoryReaction(String(reaction));
            }
        }
    });
    $(document).on('click touchend', '.story-quick-reply-btn', function(event) {
        var view = window.__activeStoryView;
        if (event.originalEvent && event.originalEvent.__storyHandled) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        var reply = $(this).data('reply') || $(this).text();
        if (reply) {
            if (view && typeof view.sendQuickReply === 'function') {
                view.sendQuickReply(String(reply));
            } else {
                fallbackStoryReply(String(reply));
            }
        }
    });

    if (document && document.addEventListener) {
        document.addEventListener('pointerup', handleCapturedStoryAction, true);
        document.addEventListener('touchend', handleCapturedStoryAction, true);
        document.addEventListener('click', handleCapturedStoryAction, true);
    }
}

StoryView.prototype.Helpers = {

    /**
     * @desc
     * @param {} element -
     */
    getBackground: function(element) {
        var bg = element.css('background-image');
        return bg.replace(/.*\s?url\([\'\"]?/, '').replace(/[\'\"]?\).*/, '')
    },

    /**
     * @desc
     * @param {} element -
     */
    isTouchDevice: function() {
        return 'ontouchstart' in window || 'onmsgesturechange' in window;
    },

    /**
     * @desc
     */
    generateID: function() {
        return "x".split('-').map(function(x) {
            return x.replace(x, Math.random().toString(16).substring(2) + Date.now().toString(16))
        }).join('-');
    },

    /**
     * @desc
     */
    containerPositon: function() {
        var container = this.storyContainer,
            matrix;

        matrix = container.css('transform').split(',').map(function(val) {
            return parseFloat(val.replace(/[a-z\s()]/g, ''))
        });

        return {
            x: matrix[4],
            y: matrix[5]
        }
    },

    /**
     * @desc
     */
    oneTimeFire: function(callback) {
        var flagger = false;
        return function(event) {
            if (!flagger) callback(event);
            flagger = true;
        }
    }
}



/**
 * @desc
 *
 */
StoryView.prototype.reset = function() {
    this.stories = [];
    this.mediaItems = [];
    this.mask = null;
    this.view = null;
    this.storyContainer = null;
    this.currentStory = null;
    this.currentMediaProgress = null;
    this.currentMedia = 0;
    this.paused = false;
    this.lastProgressVal = null;
    this.removingState = false;
    this.lastTouchStartTime = null;
    this.mediaLoading = false;
    this.mediaStartTime = null;
    this.mediaTempItem = null;
    this.mediaTempLoadFn = null;
    this.gestureStartOffset = null;
    this.elements = {};
    this.replyContext = { storyId: null, ownerId: null };
    this.accessContext = { ownerId: null, ownerUsername: '', privacy: '' };
    this.reactionContext = { storyId: null, ownerId: null, canView: true };
    this.replySending = false;
    this.reactionSending = false;
    if (this.replyStatusTimer) {
        clearTimeout(this.replyStatusTimer);
    }
    this.replyStatusTimer = null;
    this.replyHandlersBound = false;
    this.reactionHandlersBound = false;
    this.quickReplyHandlersBound = false;
    this.accessHandlersBound = false;
    this.audioHandlersBound = false;
    this.audioElementBound = false;
    this.audioEl = null;
    this.audioMuted = false;
    this.audioPlaying = false;
    this.currentAudioUrl = '';
    this.currentAudioTitle = '';
    this.currentAudioArtist = '';
    this.audioHoldPaused = false;
    this.audioTipTimer = null;
    this.audioTipShownFor = '';
    if (this.hammer) this.hammer.destroy();
    this.hammer = null;
}

/**
 * @desc
 *
 */
StoryView.prototype.init = function() {
    this.getItems();
};

/**
 * @desc
 *
 */
StoryView.prototype.getItems = function() {
    var items = this.container.find(this.itemSelector);
    var self = this; 

    items.each(function(idx, item) {
        var $item = $(item);
        $item.on('click', self.storyClick.bind(self, $item));
        $item.data('has-next', idx != items.length - 1);
        $item.data('profile-image', $item.data('profile-image'));
        $item.data('profile-name', $item.data('profile-name'));
        self.stories.push($item);
    });
}

/**
 * @desc
 * @param {} story -
 */
StoryView.prototype.storyClick = function(story, event) {
    if (event && $(event.target).closest('.highlight-edit').length) {
        return;
    }
    if (story.hasClass('activated')) return;
    this.removingState = false;
    this.openStory(story);
}

/**
 * @desc
 * @param {} x -
 */
StoryView.prototype.changeMediaByGesture = function(x, type) {
    var wv = $(window).width();

    // Get next media
    // #
    if (x > wv / 2) {
        this.nextMedia(true);
    }

    // Get previous media
    // #
    if (x < wv / 2) {
        this.prevMedia(true);
    }
};

/**
 * @desc
 * @param {} event -
 */
StoryView.prototype.storyViewMouse = function(event) {
    var diff, evX = event.clientX || (event.changedTouches && event.changedTouches[0].clientX),
        evType = event.type;
    // Detecting pause state
    // #
    if (evType == 'touchstart' || evType == 'mousedown')
        this.lastTouchStartTime = Date.now();

    if (evType == 'touchend' || evType == 'mouseup')
        diff = Date.now() - this.lastTouchStartTime;

    if (!diff) {
        this.paused = true;
        return;
    } else this.paused = false;

    if ((evType == 'touchend' || evType == 'mouseup') && diff < 400) {
        this.changeMediaByGesture(evX, event.type);
    }
}

/**
 * @desc
 * @param {} event -
 */
StoryView.prototype.gestureVertical = function(event) {

    var pos;

    if (typeof this.gestureStartOffset.y == 'undefined')
        return;

    //
    // #
    if (event.direction == Hammer.DIRECTION_UP) {
        pos = this.gestureStartOffset.y + event.deltaY;
    }

    //
    // #
    if (event.direction == Hammer.DIRECTION_DOWN) {
        pos = this.gestureStartOffset.y + event.deltaY;
    }

    if (pos < 0) pos = 0

    this.storyContainer.removeClass('transition');
    this.storyContainer.css('transform', 'translateY( ' + pos + 'px )');
}

/**
 * @desc
 * @param {} event -
 */
StoryView.prototype.gestureStartStop = function(event) {

    var containerPosition = this.Helpers.containerPositon.call(this);

    if (event.type == 'swipedown') {
        this.mask.addClass('swipe-close');
        this.closeView()
        return;
    }

    // Start
    // #
    if (event.type == 'panstart') {
        this.gestureStartOffset = { x: containerPosition.x, y: containerPosition.y };
    }

    // Stop
    // #
    if (event.type == 'panend') {
        this.storyContainer.addClass('transition').css('transform', 'translateY( 0 )');

    }
}

/**
 * @desc
 *
 */
StoryView.prototype.preserveProfileName = function() {
    this.elements.profile.find('.name').css('min-width',
        this.elements.profile.find('.name').width()
    );
}


/**
 * @desc
 * @param {} mediaIndex -
 */
StoryView.prototype.closeView = function() {
    if (this.removingState) return;

    var that = this,
        t1, t2;

    this.removingState = true;
    this.stopStoryAudio(true);
    this.view.removeClass('open');

    t1 = setTimeout(function() {
        clearTimeout(t1);
        this.view.addClass('removing');
    }.bind(this), 100);

    this.preserveProfileName();

    /*
     *
     *
     * [ jQuery Event: one --> this.view ]
     */
    this.view.one(svTransitionEnd, new this.Helpers.oneTimeFire(function(event) {

        this.view.removeClass('move')
        this.mask.removeClass('open')
        this.currentStory.removeClass('activated');

        t2 = setTimeout(function() {
            clearTimeout(t2);
            this.mask.remove();
            this.view.remove();
            this.storyContainer.remove();
            this.currentStory.removeClass('activated');
            clearTimeout(this.currentMediaProgress);
            this.reset();
            $('body').removeClass('story-view--shown');
            if (window.__activeStoryView === this) {
                window.__activeStoryView = null;
            }
        }.bind(this), 100);
    }.bind(this)));
}

/**
 * @desc
 * @param {} story -
 * @param isNextStory -
 */
StoryView.prototype.openStory = function(story, isNextStory, mediaIndex) {
    var that = this;

    this.view = this.view || $(this.fsvTemplate);
    this.storyContainer = this.storyContainer || $('<div />');

    var background = this.Helpers.getBackground(story),
        fsLeft = story.position().left,
        fsTop = story.position().top - $(window).scrollTop(),
        hammerPan, hammerSwipe, t1, t2;

    this.view.css({
        transform: 'translateX( ' + fsLeft + 'px) translateY( ' + fsTop + 'px )',
        width: story.width(),
        height: story.height()
    });

    if (!isNextStory) {
        this.mask = $('<div class="sv-mask" />');

        // Create hammer instance
        // #
        this.hammer = new Hammer.Manager(this.view.get(0));

        hammerPan = new Hammer.Pan({
            direction: Hammer.DIRECTION_ALL,
            threshold: 0
        });

        hammerSwipe = new Hammer.Swipe({
            direction: Hammer.DIRECTION_DOWN,
            threshold: 10,
            velocity: 1.5
        });

        hammerSwipe.recognizeWith(hammerPan);

        this.hammer.add(hammerPan);
        this.hammer.add(hammerSwipe);

        this.elements = {
            mediaContainer: this.view.find('.media-container'),
            loading: this.view.find('.loading'),
            profile: this.view.find('.profile'),
            audioControls: this.view.find('.story-audio-controls'),
            audioMute: this.view.find('.story-audio-mute'),
            audioPlay: this.view.find('.story-audio-play'),
            audioTip: this.view.find('.story-audio-tip'),
            audioBadge: this.view.find('.story-audio-badge'),
            overlays: this.view.find('.story-overlays'),
            overlayLink: this.view.find('.story-overlay-link'),
            overlayMention: this.view.find('.story-overlay-mention'),
            overlaySticker: this.view.find('.story-overlay-sticker img'),
            reactions: this.view.find('.story-reactions'),
            quickReplies: this.view.find('.story-quick-replies'),
            accessOverlay: this.view.find('.story-access-overlay'),
            accessText: this.view.find('.story-access-text'),
            accessFollow: this.view.find('.story-access-follow'),
            accessSubscribe: this.view.find('.story-access-subscribe')
        };

        if (!this.audioEl) {
            this.audioEl = document.createElement('audio');
            this.audioEl.preload = 'none';
            this.audioEl.className = 'story-audio-element';
            this.audioEl.setAttribute('preload', 'none');
            this.view.append(this.audioEl);
            this.bindAudioElement();
        }

        this.storyContainer.attr('id', 'sv-' + this.Helpers.generateID());
        this.storyContainer.attr('class', 'sv-container');
        this.storyContainer.append(this.view);
        this.storyContainer.append(this.mask);
        $('body').append(this.storyContainer);

        /**
         *
         * [ jQuery Event: [ click ] --> view .close ]
         */
        this.view.find('.close').on('click touchend', function() {
            that.closeView();
        });
        $(".hereText").removeClass("hereTextClicked");
        this.view.find('.hereText').on('click', ".hereTextToggle", function(event) {
            event.preventDefault();
            event.stopPropagation();
            var $text = $(this).closest(".hereText");
            if (!$text.length) {
                return;
            }
            var isExpanded = $text.hasClass("hereTextClicked");
            var nextExpanded = !isExpanded;
            $text.toggleClass("hereTextClicked", nextExpanded);
            $(this).attr("aria-expanded", nextExpanded ? "true" : "false");
            var moreLabel = $(this).data("more-label") || $(this).text();
            var lessLabel = $(this).data("less-label") || $(this).text();
            $(this).text(nextExpanded ? lessLabel : moreLabel);
            var fullText = $text.data("full-text");
            var shortText = $text.data("short-text");
            var $content = $text.find(".hereTextContent p");
            if ($content.length && fullText && shortText) {
                $content.html(nextExpanded ? fullText : shortText);
            }
        });
        /**
         *
         * [ jQuery event: [ mousedown mouseup touchstart touchend ] --> view ]
         */
        var eventList = 'mousedown mouseup touchstart touchend';
        this.view.on(eventList, function(event) {
            var target = event.target;
            var $target = $(target && target.nodeType === 3 ? target.parentNode : target);
            if ($target.closest('.story-reply, .story-reactions, .story-reaction-btn, .story-quick-replies, .story-quick-reply-btn, .story-access-overlay, .story-overlay, .story-audio-controls, .story-audio-badge').length) {
                return;
            }
            event.preventDefault();
            if ($target.parents('.close , .hereText').length == 0) {
                that.storyViewMouse(event);
            }
        });

        /**
         *
         *
         * [ HammerJs event:  ]
         */
        this.hammer.on('swipedown panstart panend', this.gestureStartStop.bind(this));
        this.hammer.on('pandown panup', this.gestureVertical.bind(this));

        this.initReplyHandlers();
        this.initReactionHandlers();
        this.initQuickReplyHandlers();
        this.initAccessHandlers();
        this.initAudioHandlers();
    }

    window.__activeStoryView = this;
    bindGlobalStoryActions();

    // Reset container position
    // #
    this.storyContainer.css('transform', 'translate( 0, 0 )');

    // Reset profile on new story
    // #
    this.elements.profile.removeClass('show');
    // this.elements.profile.find( '.image' ).removeAttr( 'style' );
    // this.elements.profile.find( '.name' ).text( '' );

    // Fill story view
    // #
    // if( isNextStory )
    this.storyView(story);

    // Animate
    // #
    t1 = setTimeout(function() {
        clearInterval(t1);

        if (!isNextStory) {
            this.view.addClass('move')
            this.mask.addClass('open')
        }

        this.currentStory = story;
        story.addClass('activated');
    }.bind(this));

    t2 = setTimeout(function() {
        clearInterval(t2);

        if (!isNextStory) {
            this.view.addClass('open')

            /**
             *
             *
             * [ jQuery Event: transitionend --> StoryView.view ]
             */
            this.view.one(svTransitionEnd, new this.Helpers.oneTimeFire(function(event) {
                this.elements.profile.addClass('can-visible');
            }.bind(this)));
        }
    }.bind(this), 200);

    $('body').addClass('story-view--shown');
}

/**
 * @desc
 * @param {} view -
 * @param {} story -
 */
StoryView.prototype.storyView = function(story) {
    var that = this;
    var bars = $('<ul class="media-bars" />'),
        content = this.view.find('.content'),
        bar, $media, type, src, alt, profileImage, profileName;

    profileImage = story.data().profileImage;
    profileName = story.data().profileName;

    story.find('.media li *').each(function(idx, el) {
        var $media = $(el);
        var id = $media.attr('data-id');
        var canView = String($media.attr('data-can-view') || '1') !== '0';
        var privacy = $media.attr('data-privacy') || 'followers';
        var accessReason = $media.attr('data-access-reason') || '';
        var overlayLink = $media.attr('data-overlay-link') || '';
        var overlayMention = $media.attr('data-overlay-mention') || '';
        var overlaySticker = $media.attr('data-overlay-sticker') || '';
        var overlayAudio = $media.attr('data-overlay-audio') || '';
        var audioTitle = $media.attr('data-audio-title') || '';
        var audioArtist = $media.attr('data-audio-artist') || '';
        var quickReplies = [];
        var quickRaw = $media.attr('data-quick-replies');
        if (quickRaw) {
            try {
                quickReplies = JSON.parse(quickRaw);
            } catch (e) {
                quickReplies = [];
            }
        }
        var bar = $('<li><span class="progress"></span></li>');
        bar.attr('data-type', $media.get(0).tagName.toLowerCase());
        bar.attr('data-src', $media.attr('src'));
        bar.attr('data-alt', $media.attr('alt'));
        bar.attr('data-id', id);
        bar.attr('data-ts', $media.attr('data-ts'));
        bars.append(bar);
    
        that.mediaItems.push({
            duration: $media.parent().data('duration'),
            type: $media.get(0).tagName.toLowerCase(),
            src: $media.attr('src'),
            alt: $media.attr('alt'),
            id: id,
            text_style: $media.attr('data-ts'),
            canView: canView,
            privacy: privacy,
            accessReason: accessReason,
            overlayLink: overlayLink,
            overlayMention: overlayMention,
            overlaySticker: overlaySticker,
            audioUrl: overlayAudio,
            audioTitle: audioTitle,
            audioArtist: audioArtist,
            quickReplies: Array.isArray(quickReplies) ? quickReplies : []
        });
    });


    // Fill Profile
    // #
    if (profileImage || profileName) {
        this.elements.profile.removeClass('sv-profile-image sv-profile-name');
        this.elements.profile.find('.image').css('background-image', 'url(' + (profileImage ? profileImage : '') + ')');
        this.elements.profile.find('.name').text(profileName ? profileName : '');

        if (profileImage) this.elements.profile.addClass('sv-profile-image');
        if (profileName) this.elements.profile.addClass('sv-profile-name');

        this.elements.profile.addClass('show');
    }

    // Mark story as seen for gradient change
    if (typeof window.markStoryAsSeen === 'function' && story) {
        var seenId = story.data('seen-id') || story.data('seenId') || story.data('highlight-id') || story.data('highlightId') || story.data('story-id');
        if (seenId) {
            window.markStoryAsSeen(
                seenId,
                story.data('last-story-id'),
                story.data('seen-scope')
            );
        }
    }

    content.find('.media-bars').remove();
    content.prepend(bars);

    this.currentStory = story;

    // Start from first
    // #
    this.showMedia(this.currentMedia, 'next');
}

StoryView.prototype.initReplyHandlers = function() {
    var that = this;
    if (this.replyHandlersBound || !this.view) {
        return;
    }
    this.replyHandlersBound = true;
    this.elements.replyInput = this.view.find('.story-reply-input');
    this.elements.replySend = this.view.find('.story-reply-send');
    this.elements.replyStatus = this.view.find('.story-reply-status');

    this.view.on('focus', '.story-reply-input', function() {
        that.paused = true;
    });

    this.view.on('blur', '.story-reply-input', function() {
        that.paused = false;
    });

    this.view.on('keydown', '.story-reply-input', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            that.sendStoryReply();
        }
    });

    this.view.on('click', '.story-reply-send', function(event) {
        event.preventDefault();
        that.sendStoryReply();
    });
};

StoryView.prototype.initReactionHandlers = function() {
    if (this.reactionHandlersBound || !this.view) {
        return;
    }
    if (!this.elements.reactions || !this.elements.reactions.length) {
        this.elements.reactions = this.view.find('.story-reactions');
    }
    this.renderReactionButtons();
    this.view.on('click', '.story-reaction-btn', function(event) {
        if (event.originalEvent && event.originalEvent.__storyHandled) {
            return;
        }
        if (event.originalEvent) {
            event.originalEvent.__storyHandled = true;
        }
        event.preventDefault();
        event.stopPropagation();
        var reaction = $(this).data('reaction') || $(this).text();
        if (reaction) {
            this.sendReaction(String(reaction));
        }
    }.bind(this));
    this.reactionHandlersBound = true;
};

StoryView.prototype.renderReactionButtons = function() {
    if (!this.elements.reactions || !this.elements.reactions.length) {
        if (this.view) {
            this.elements.reactions = this.view.find('.story-reactions');
        }
    }
    if (!this.elements.reactions || !this.elements.reactions.length) {
        return;
    }
    this.elements.reactions.empty();
    if (!Array.isArray(this.reactionSet) || this.reactionSet.length === 0) {
        this.elements.reactions.addClass('is-hidden');
        return;
    }
    this.reactionSet.forEach(function(reaction) {
        var btn = $('<button type="button" class="story-reaction-btn"></button>');
        btn.text(reaction);
        btn.attr('data-reaction', reaction);
        this.elements.reactions.append(btn);
    }.bind(this));
    this.elements.reactions.removeClass('is-hidden');
};

StoryView.prototype.sendReaction = function(reaction) {
    if (this.reactionSending) {
        return;
    }
    var ctx = this.reactionContext || {};
    var storyId = Number(ctx.storyId || 0);
    var ownerId = Number(ctx.ownerId || 0);
    var canView = (ctx.canView !== false && ctx.canView !== 0 && ctx.canView !== '0');
    if (!storyId && this.mediaItems && this.mediaItems[this.currentMedia]) {
        storyId = Number(this.mediaItems[this.currentMedia].id || 0);
    }
    if (!ownerId && this.currentStory) {
        ownerId = Number(this.currentStory.data('story-id') || 0);
    }
    if (!storyId || !canView) {
        return;
    }
    if (this.currentUserId > 0 && ownerId && ownerId === this.currentUserId) {
        return;
    }
    var data = 'f=story_reaction&story_id=' + encodeURIComponent(storyId) +
        '&reaction=' + encodeURIComponent(reaction);
    var csrfToken = '';
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        csrfToken = meta.getAttribute('content') || '';
    }
    if (csrfToken) {
        data += '&csrf_token=' + encodeURIComponent(csrfToken);
    }
    this.reactionSending = true;
    this.elements.reactions.addClass('is-loading');
    $.ajax({
        type: 'POST',
        url: this.requestUrl,
        data: data,
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response && response.status === 'success') {
                this.elements.reactions.find('.story-reaction-btn').removeClass('is-selected');
                if (response.action !== 'removed') {
                    this.elements.reactions.find('.story-reaction-btn[data-reaction="' + reaction + '"]').addClass('is-selected');
                }
            }
        }.bind(this),
        complete: function() {
            this.reactionSending = false;
            this.elements.reactions.removeClass('is-loading');
        }.bind(this)
    });
};

StoryView.prototype.updateReactionState = function(item) {
    if (!this.elements.reactions || !this.elements.reactions.length) {
        return;
    }
    var canView = item ? (item.canView !== false && item.canView !== 0 && item.canView !== '0') : true;
    var ownerId = this.reactionContext.ownerId;
    if (!ownerId && this.currentStory) {
        ownerId = Number(this.currentStory.data('story-id') || 0);
    }
    var isSelf = this.currentUserId > 0 && ownerId && ownerId === this.currentUserId;
    if (!canView || isSelf || !Array.isArray(this.reactionSet) || this.reactionSet.length === 0) {
        this.elements.reactions.addClass('is-hidden');
        return;
    }
    this.elements.reactions.removeClass('is-hidden');
    this.elements.reactions.find('.story-reaction-btn').removeClass('is-selected');
};

StoryView.prototype.initQuickReplyHandlers = function() {
    if (this.quickReplyHandlersBound || !this.view) {
        return;
    }
    if (!this.elements.quickReplies || !this.elements.quickReplies.length) {
        this.elements.quickReplies = this.view.find('.story-quick-replies');
    }
    this.view.on('click', '.story-quick-reply-btn', function(event) {
        if (event.originalEvent && event.originalEvent.__storyHandled) {
            return;
        }
        if (event.originalEvent) {
            event.originalEvent.__storyHandled = true;
        }
        event.preventDefault();
        event.stopPropagation();
        var reply = $(this).data('reply') || $(this).text();
        if (reply) {
            this.sendQuickReply(String(reply));
        }
    }.bind(this));
    this.quickReplyHandlersBound = true;
};

StoryView.prototype.bindAudioElement = function() {
    if (!this.audioEl || this.audioElementBound) {
        return;
    }
    this.audioElementBound = true;
    this.audioMuted = !!this.audioEl.muted;
    this.audioEl.addEventListener('play', function() {
        this.audioPlaying = true;
        this.updateAudioUI();
        if (this.audioEl.muted && this.currentAudioUrl && this.audioTipShownFor !== this.currentAudioUrl) {
            this.audioTipShownFor = this.currentAudioUrl;
            this.showAudioTip();
        }
    }.bind(this));
    this.audioEl.addEventListener('pause', function() {
        this.audioPlaying = false;
        this.updateAudioUI();
    }.bind(this));
    this.audioEl.addEventListener('ended', function() {
        this.audioPlaying = false;
        try {
            this.audioEl.currentTime = 0;
        } catch (e) {
            /* ignore */
        }
        this.updateAudioUI();
        var item = this.mediaItems[this.currentMedia] || {};
        if (item.audioUrl && this.currentAudioUrl && item.audioUrl === this.currentAudioUrl) {
            this.nextMedia();
        }
    }.bind(this));
    this.audioEl.addEventListener('volumechange', function() {
        this.audioMuted = !!this.audioEl.muted;
        this.updateAudioUI();
    }.bind(this));
};

StoryView.prototype.initAudioHandlers = function() {
    if (this.audioHandlersBound || !this.view) {
        return;
    }
    if (!this.elements.audioControls || !this.elements.audioControls.length) {
        this.elements.audioControls = this.view.find('.story-audio-controls');
        this.elements.audioMute = this.view.find('.story-audio-mute');
        this.elements.audioPlay = this.view.find('.story-audio-play');
        this.elements.audioTip = this.view.find('.story-audio-tip');
        this.elements.audioBadge = this.view.find('.story-audio-badge');
    }
    this.view.on('click', '.story-audio-play', function(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.audioEl || !this.currentAudioUrl) {
            return;
        }
        if (this.audioEl.paused) {
            var playPromise = this.audioEl.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(function() {});
            }
        } else {
            this.audioEl.pause();
        }
    }.bind(this));
    this.view.on('click', '.story-audio-mute', function(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.audioEl || !this.currentAudioUrl) {
            return;
        }
        this.audioEl.muted = !this.audioEl.muted;
        this.audioMuted = !!this.audioEl.muted;
        if (!this.audioEl.muted) {
            this.hideAudioTip();
        }
        this.updateAudioUI();
    }.bind(this));
    this.audioHandlersBound = true;
};

StoryView.prototype.showAudioTip = function() {
    if (!this.elements.audioTip || !this.elements.audioTip.length) {
        return;
    }
    this.elements.audioTip.text(this.audioLabels.unmuteTip || '');
    this.elements.audioTip.addClass('is-visible');
    if (this.audioTipTimer) {
        clearTimeout(this.audioTipTimer);
    }
    this.audioTipTimer = setTimeout(function() {
        this.hideAudioTip();
    }.bind(this), 5000);
};

StoryView.prototype.hideAudioTip = function() {
    if (this.audioTipTimer) {
        clearTimeout(this.audioTipTimer);
    }
    this.audioTipTimer = null;
    if (this.elements.audioTip && this.elements.audioTip.length) {
        this.elements.audioTip.removeClass('is-visible');
    }
};

StoryView.prototype.stopStoryAudio = function(resetTime) {
    if (!this.audioEl) {
        return;
    }
    if (!this.audioEl.paused) {
        this.audioEl.pause();
    }
    if (resetTime) {
        try {
            this.audioEl.currentTime = 0;
        } catch (e) {
            /* ignore */
        }
    }
    this.audioPlaying = false;
    this.hideAudioTip();
};

StoryView.prototype.updateAudioBadge = function() {
    if (!this.elements.audioBadge || !this.elements.audioBadge.length) {
        return;
    }
    var label = '';
    if (this.currentAudioTitle) {
        label = this.currentAudioTitle;
    }
    if (this.currentAudioArtist) {
        label = label ? (label + ' - ' + this.currentAudioArtist) : this.currentAudioArtist;
    }
    if (label) {
        this.elements.audioBadge.text(label).addClass('is-visible');
    } else {
        this.elements.audioBadge.text('').removeClass('is-visible');
    }
};

StoryView.prototype.updateAudioUI = function() {
    if (!this.elements.audioControls || !this.elements.audioControls.length) {
        return;
    }
    var hasAudio = !!this.currentAudioUrl;
    this.elements.audioControls.toggleClass('is-hidden', !hasAudio);
    if (!hasAudio) {
        this.hideAudioTip();
        return;
    }
    var muted = this.audioEl ? this.audioEl.muted : !!this.audioMuted;
    var playing = this.audioEl ? !this.audioEl.paused : !!this.audioPlaying;
    if (this.elements.audioMute && this.elements.audioMute.length) {
        this.elements.audioMute.html(getStoryAudioMuteIcon(muted));
        this.elements.audioMute.attr('aria-label', muted ? this.audioLabels.unmute : this.audioLabels.mute);
    }
    if (this.elements.audioPlay && this.elements.audioPlay.length) {
        this.elements.audioPlay.html(getStoryAudioPlayIcon(playing));
        this.elements.audioPlay.attr('aria-label', playing ? this.audioLabels.pause : this.audioLabels.play);
    }
};

StoryView.prototype.setStoryAudio = function(item) {
    var audioUrl = item && item.audioUrl ? item.audioUrl : '';
    var audioTitle = item && item.audioTitle ? item.audioTitle : '';
    var audioArtist = item && item.audioArtist ? item.audioArtist : '';
    if (item && item.type === 'video') {
        audioUrl = '';
    }
    if (!audioUrl || !this.audioEl) {
        this.currentAudioUrl = '';
        this.currentAudioTitle = '';
        this.currentAudioArtist = '';
        this.stopStoryAudio(true);
        this.updateAudioBadge();
        this.updateAudioUI();
        return;
    }
    this.currentAudioUrl = audioUrl;
    this.currentAudioTitle = audioTitle;
    this.currentAudioArtist = audioArtist;
    this.audioTipShownFor = '';
    if (this.audioEl.src !== audioUrl) {
        this.audioEl.src = audioUrl;
        this.audioEl.load();
    }
    this.audioMuted = true;
    this.audioEl.muted = true;
    this.audioPlaying = false;
    this.updateAudioBadge();
    this.updateAudioUI();
    var playPromise = this.audioEl.play();
    if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(function() {});
    }
};

StoryView.prototype.updateQuickReplies = function(item) {
    if (!this.elements.quickReplies || !this.elements.quickReplies.length) {
        if (this.view) {
            this.elements.quickReplies = this.view.find('.story-quick-replies');
        }
    }
    if (!this.elements.quickReplies || !this.elements.quickReplies.length) {
        return;
    }
    this.elements.quickReplies.empty();
    var canView = item ? (item.canView !== false && item.canView !== 0 && item.canView !== '0') : true;
    var replies = item && Array.isArray(item.quickReplies) ? item.quickReplies : [];
    var isSelf = this.currentUserId > 0 && this.replyContext.ownerId === this.currentUserId;
    if (!canView || isSelf || replies.length === 0) {
        this.elements.quickReplies.removeClass('is-visible');
        return;
    }
    replies.slice(0, 5).forEach(function(reply) {
        var btn = $('<button type="button" class="story-quick-reply-btn"></button>');
        btn.text(reply);
        btn.attr('data-reply', reply);
        this.elements.quickReplies.append(btn);
    }.bind(this));
    this.elements.quickReplies.addClass('is-visible');
};

StoryView.prototype.sendQuickReply = function(text) {
    if (!this.elements.replyInput || !this.elements.replyInput.length) {
        return;
    }
    this.elements.replyInput.val(text);
    this.sendStoryReply();
};

StoryView.prototype.initAccessHandlers = function() {
    if (this.accessHandlersBound || !this.elements.accessOverlay || !this.elements.accessOverlay.length) {
        return;
    }
    this.elements.accessFollow.on('click', function(event) {
        event.preventDefault();
        this.handleFollow();
    }.bind(this));
    this.elements.accessSubscribe.on('click', function(event) {
        event.preventDefault();
        this.handleSubscribe();
    }.bind(this));
    this.accessHandlersBound = true;
};

StoryView.prototype.updateAccessState = function(item) {
    if (!this.elements.accessOverlay || !this.elements.accessOverlay.length) {
        return;
    }
    var canView = item ? (item.canView !== false && item.canView !== 0 && item.canView !== '0') : true;
    var reason = item ? (item.accessReason || item.privacy) : '';
    var ownerUsername = this.currentStory ? (this.currentStory.data('profile-username') || '') : '';
    this.accessContext = {
        ownerId: this.currentStory ? Number(this.currentStory.data('story-id') || 0) : 0,
        ownerUsername: ownerUsername,
        privacy: reason
    };
    if (canView) {
        this.elements.accessOverlay.removeClass('is-visible');
        return;
    }
    var label = this.accessLabels.followers || '';
    if (reason === 'subscribers') {
        label = this.accessLabels.subscribers || label;
    }
    this.elements.accessText.text(label);
    this.elements.accessFollow.text(this.accessLabels.followBtn || 'Follow');
    this.elements.accessSubscribe.text(this.accessLabels.subscribeBtn || 'Subscribe');
    this.elements.accessFollow.toggleClass('is-hidden', reason !== 'followers');
    this.elements.accessSubscribe.toggleClass('is-hidden', reason !== 'subscribers');
    this.elements.accessOverlay.addClass('is-visible');
};

StoryView.prototype.handleFollow = function() {
    var ownerId = this.accessContext.ownerId || 0;
    if (!ownerId || !this.requestUrl) {
        return;
    }
    var data = 'f=freeFollow&follow=' + encodeURIComponent(ownerId);
    $.ajax({
        type: 'POST',
        url: this.requestUrl,
        data: data,
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response && response.status === '200') {
                this.elements.accessFollow.addClass('is-disabled').prop('disabled', true);
            }
        }.bind(this)
    });
};

StoryView.prototype.handleSubscribe = function() {
    var username = this.accessContext.ownerUsername || '';
    if (!username) {
        return;
    }
    var base = this.baseUrl || '';
    if (base && base.slice(-1) !== '/') {
        base += '/';
    }
    window.location.href = base + username + '?subscribe=1';
};

StoryView.prototype.updateOverlays = function(item) {
    if (!this.elements.overlays || !this.elements.overlays.length) {
        return;
    }
    var canView = item ? (item.canView !== false && item.canView !== 0 && item.canView !== '0') : true;
    if (!canView) {
        this.elements.overlays.addClass('is-hidden');
        this.elements.overlayLink.removeClass('is-visible').attr('href', '#').text('');
        this.elements.overlayMention.removeClass('is-visible').attr('href', '#').text('');
        if (this.elements.overlaySticker && this.elements.overlaySticker.length) {
            var $stickerBox = this.elements.overlaySticker.closest('.story-overlay-sticker');
            this.elements.overlaySticker.attr('src', '');
            $stickerBox.removeClass('is-visible').css('display', 'none');
        }
        return;
    }
    this.elements.overlays.removeClass('is-hidden');
    if (item && item.overlayLink) {
        this.elements.overlayLink.attr('href', item.overlayLink).text(item.overlayLink).addClass('is-visible');
    } else {
        this.elements.overlayLink.removeClass('is-visible').attr('href', '#').text('');
    }
    if (item && item.overlayMention) {
        var username = String(item.overlayMention).replace(/^@/, '');
        var label = '@' + username;
        var base = this.baseUrl || '';
        if (base && base.slice(-1) !== '/') {
            base += '/';
        }
        this.elements.overlayMention.attr('href', base + username).text(label).addClass('is-visible');
    } else {
        this.elements.overlayMention.removeClass('is-visible').attr('href', '#').text('');
    }
    if (item && item.overlaySticker && this.elements.overlaySticker && this.elements.overlaySticker.length) {
        var $stickerBoxVisible = this.elements.overlaySticker.closest('.story-overlay-sticker');
        this.elements.overlaySticker.attr('src', item.overlaySticker);
        $stickerBoxVisible.addClass('is-visible').css('display', 'flex');
    } else if (this.elements.overlaySticker && this.elements.overlaySticker.length) {
        var $stickerBoxHidden = this.elements.overlaySticker.closest('.story-overlay-sticker');
        this.elements.overlaySticker.attr('src', '');
        $stickerBoxHidden.removeClass('is-visible').css('display', 'none');
    }
};

StoryView.prototype.setReplyContext = function(ownerId, storyId, canView, accessReason) {
    this.replyContext = {
        ownerId: Number(ownerId || 0),
        storyId: Number(storyId || 0)
    };

    if (!this.elements.replyInput || !this.elements.replyInput.length) {
        return;
    }

    var isSelf = this.currentUserId > 0 && this.replyContext.ownerId === this.currentUserId;
    var allowView = (canView !== false && canView !== 0 && canView !== '0');
    var canReply = allowView && !isSelf;
    this.elements.replyInput.val('');
    this.clearReplyStatus();

    if (!allowView) {
        var accessLabel = this.lang.accessFollowers || this.replyPlaceholder;
        if (accessReason === 'subscribers' && this.lang.accessSubscribers) {
            accessLabel = this.lang.accessSubscribers;
        }
        this.elements.replyInput.attr('placeholder', accessLabel);
    } else if (isSelf && this.lang.replySelf) {
        this.elements.replyInput.attr('placeholder', this.lang.replySelf);
    } else if (this.replyPlaceholder) {
        this.elements.replyInput.attr('placeholder', this.replyPlaceholder);
    }

    this.elements.replyInput.prop('disabled', !canReply);
    if (this.elements.replySend && this.elements.replySend.length) {
        this.elements.replySend.prop('disabled', !canReply);
    }
};

StoryView.prototype.setReplyStatus = function(message, isError) {
    if (!this.elements.replyStatus || !this.elements.replyStatus.length) {
        return;
    }
    clearTimeout(this.replyStatusTimer);
    if (!message) {
        this.elements.replyStatus.removeClass('is-visible is-error').text('');
        return;
    }
    this.elements.replyStatus
        .toggleClass('is-error', !!isError)
        .addClass('is-visible')
        .text(message);
    this.replyStatusTimer = setTimeout(function() {
        if (this.elements.replyStatus) {
            this.elements.replyStatus.removeClass('is-visible is-error').text('');
        }
    }.bind(this), 3000);
};

StoryView.prototype.clearReplyStatus = function() {
    this.setReplyStatus('', false);
};

StoryView.prototype.sendStoryReply = function() {
    if (this.replySending || !this.elements.replyInput || !this.elements.replyInput.length) {
        return;
    }

    var message = this.elements.replyInput.val() || '';
    var trimmed = message.replace(/\s+/g, ' ').trim();
    var ownerId = Number(this.replyContext.ownerId || 0);
    var storyId = Number(this.replyContext.storyId || 0);
    if (!storyId && this.mediaItems && this.mediaItems[this.currentMedia]) {
        storyId = Number(this.mediaItems[this.currentMedia].id || 0);
    }
    if (!ownerId && this.currentStory) {
        ownerId = Number(this.currentStory.data('story-id') || 0);
    }

    if (!ownerId || !storyId) {
        this.setReplyStatus(this.lang.replyFailed || 'Message could not be sent.', true);
        return;
    }

    if (trimmed === '') {
        this.setReplyStatus(this.lang.replyEmpty || 'Please enter a message.', true);
        return;
    }

    var csrfToken = '';
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        csrfToken = meta.getAttribute('content') || '';
    }

    var data = 'f=story_reply&story_id=' + encodeURIComponent(storyId) +
        '&story_uid=' + encodeURIComponent(ownerId) +
        '&message=' + encodeURIComponent(message);
    if (csrfToken) {
        data += '&csrf_token=' + encodeURIComponent(csrfToken);
    }

    this.replySending = true;
    this.elements.replyInput.prop('disabled', true);
    if (this.elements.replySend && this.elements.replySend.length) {
        this.elements.replySend.prop('disabled', true);
    }

    $.ajax({
        type: 'POST',
        url: this.requestUrl,
        data: data,
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response && response.status === 'success') {
                var successMessage = response.message || this.lang.replySent || '';
                this.setReplyStatus(successMessage, false);
                this.elements.replyInput.val('');
                this.elements.replyInput.blur();
            } else {
                var errorMessage = (response && response.message) ? response.message : (this.lang.replyFailed || 'Message could not be sent.');
                this.setReplyStatus(errorMessage, true);
            }
        }.bind(this),
        error: function() {
            this.setReplyStatus(this.lang.replyFailed || 'Message could not be sent.', true);
        }.bind(this),
        complete: function() {
            this.replySending = false;
            var isSelf = this.currentUserId > 0 && this.replyContext.ownerId === this.currentUserId;
            this.elements.replyInput.prop('disabled', isSelf);
            if (this.elements.replySend && this.elements.replySend.length) {
                this.elements.replySend.prop('disabled', isSelf);
            }
        }.bind(this)
    });
};

function generateRandomInteger(min, max) {
    return Math.floor(min + Math.random() * (max - min + 1));
}
var NumberGenerated = generateRandomInteger(1, 3);
var d = new Date();
var GeneragedDate = d.getDate();
/**
 * @desc
 * @param {} mediaIndex -
 */
StoryView.prototype.showMedia = function(mediaIndex, direction) {
    var that = this;
    var item = this.mediaItems[mediaIndex],
        content = this.view.find('.content .media-container'),
        textContent = this.view.find('.content .hereText'),
        prevProgressBars = this.view.find('.media-bars li:lt(' + this.currentMedia + ') .progress'),
        nextProgressBars = this.view.find('.media-bars li:gt(' + this.currentMedia + ') .progress'),
        progressBar = this.view
        .find('.media-bars li')
        .eq(mediaIndex)
        .find('.progress');

    function renderStoryText(text, allowToggle) {
        var $hereText = that.view.find('.hereText');
        if (!$hereText.length) {
            return;
        }
        $hereText.removeClass("hereTextClicked");
        $hereText.removeClass("is-hidden is-visible");
        var storyText = typeof text === 'string' ? text : (text == null ? '' : String(text));
        if (!storyText.trim()) {
            $hereText.addClass("is-hidden");
            $hereText.html('');
            $hereText.removeData("full-text").removeData("short-text");
            return;
        }
        $hereText.addClass("is-visible");
        var truncateLimit = 150;
        if (allowToggle && storyText.length > truncateLimit) {
            var moreLabel = (that.textToggleLabels && that.textToggleLabels.more) ? that.textToggleLabels.more : 'Read more';
            var lessLabel = (that.textToggleLabels && that.textToggleLabels.less) ? that.textToggleLabels.less : 'Show less';
            var safeMore = String(moreLabel).replace(/"/g, '&quot;');
            var safeLess = String(lessLabel).replace(/"/g, '&quot;');
            var shortText = storyText.slice(0, truncateLimit).trim();
            if (shortText.length < storyText.length) {
                shortText += '...';
            }
            $hereText.data("full-text", storyText);
            $hereText.data("short-text", shortText);
            var html = '<div class="hereTextContent"><p>' + shortText + '</p></div>'
                + '<button type="button" class="hereTextToggle" aria-expanded="false"'
                + ' data-more-label="' + safeMore + '" data-less-label="' + safeLess + '">'
                + safeMore + '</button>'
                + '<span class="hereTextFade" aria-hidden="true"></span>';
            $hereText.html(html);
            return;
        }
        $hereText.removeData("full-text").removeData("short-text");
        $hereText.html(storyText);
    }

    if (this.currentStory) {
        var ownerId = this.currentStory.data('story-id');
        var canView = item ? (item.canView !== false && item.canView !== 0 && item.canView !== '0') : true;
        var accessReason = item ? item.accessReason : '';
        this.setReplyContext(ownerId, item && item.id ? item.id : 0, canView, accessReason);
        this.reactionContext = {
            storyId: item && item.id ? item.id : 0,
            ownerId: ownerId || 0,
            canView: canView
        };
        this.updateAccessState(item);
        this.updateOverlays(item);
        this.updateReactionState(item);
        this.updateQuickReplies(item);
        this.stopStoryAudio(true);
        this.audioHoldPaused = false;
        this.hideAudioTip();
        this.setStoryAudio(item);
    }

    this.mediaLoading = true;
    this.elements.loading.addClass('show');

    // Cancel previous source loading
    // #
    removeMediaEvents.call(this);


    // Remove media if exist
    // #
    content.empty();
    textContent.empty();
    progressBar.css('width', 0);
    nextProgressBars.css('width', '0');
    prevProgressBars.css('width', '100%');
    this.lastProgressVal = 0;
    this.mediaTempItem = null;
    this.mediaTempLoadFn = null;

    // Create image
    // #
    if (item.type == 'img') {
        this.mediaTempItem = new Image();

        /**
         * @desc
         */
        this.mediaTempLoadFn = function() {
            startMedia(that.mediaTempItem, mediaIndex, item.duration, progressBar, direction);
        };

        /**
         *
         * [ Image event: load --> StoryView.mediaTemplate ]
         */
        this.mediaTempItem.addEventListener('load', this.mediaTempLoadFn);
        this.mediaTempItem.src = item.src;
        this.mediaTempItem.id = item.id;
        this.mediaTempItem.text_style = item.text_style;

        $('.sv-view div.hereText').attr('class', 'hereText');
        if (this.mediaTempItem.text_style == 'one') {
            $(".hereText").addClass("hereTextStyle_one");
        }
        //alert(this.mediaTempItem.id);
        if (this.mediaTempItem.id && item && item.canView !== false && item.canView !== 0 && item.canView !== '0') {
            var type = 'storieSeen';
            var id = this.mediaTempItem.id;
            var data = 'f=' + type + '&id=' + id;
            var csrfToken = '';
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                csrfToken = meta.getAttribute('content') || '';
            }
            if (csrfToken) {
                data += '&csrf_token=' + encodeURIComponent(csrfToken);
            }
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {},
                success: function() {}
            });
        }
        // New One
        this.mediaTempItem.alt = item.alt;
        setTimeout(() => {
            renderStoryText(this.mediaTempItem.alt, this.mediaTempItem.text_style == 'not');
        }, 200);
    }
    // Create Video
    // #
if (item.type == 'video') {
    this.mediaTempItem = document.createElement('video');

    this.mediaTempItem.src = item.src;
    this.mediaTempItem.alt = item.alt;
    this.mediaTempItem.id = item.id;
    this.mediaTempItem.controls = false;
    this.mediaTempItem.autoplay = true;
    this.mediaTempItem.muted = true;
    this.mediaTempItem.playsInline = true;
    this.mediaTempItem.setAttribute('autoplay', 'autoplay');
    this.mediaTempItem.setAttribute('playsinline', '');
    this.mediaTempItem.setAttribute('muted', '');

    const animateProgress = () => {
        if (!this.mediaTempItem || this.mediaTempItem.paused || this.mediaTempItem.ended) return;

        const currentTime = this.mediaTempItem.currentTime;
        const duration = this.mediaTempItem.duration;
        const percent = (currentTime / duration) * 100;

        progressBar.css('width', percent + '%');

        requestAnimationFrame(animateProgress); 
    };

    if (this.mediaTempItem.id && item && item.canView !== false && item.canView !== 0 && item.canView !== '0') {
        var dataVideo = 'f=storieSeen&id=' + this.mediaTempItem.id;
        var csrfTokenVideo = '';
        var metaVideo = document.querySelector('meta[name="csrf-token"]');
        if (metaVideo) {
            csrfTokenVideo = metaVideo.getAttribute('content') || '';
        }
        if (csrfTokenVideo) {
            dataVideo += '&csrf_token=' + encodeURIComponent(csrfTokenVideo);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: dataVideo,
            cache: false,
            beforeSend: function() {},
            success: function() {}
        });
    }

    this.mediaTempItem.onplaying = () => {
        this.elements.loading.removeClass('show');
        startMedia(this.mediaTempItem, mediaIndex, this.mediaTempItem.duration, progressBar, direction);
        animateProgress(); 
    };

    this.mediaTempItem.oncanplay = () => {
        this.mediaTempItem.play(); 
    };

    setTimeout(() => {
        renderStoryText(this.mediaTempItem.alt, true);
    }, 200);
}

    /**
     * @desc
     *
     */
    function removeMediaEvents() {
        if (this.mediaTempItem && this.mediaTempLoadFn) {
            this.mediaTempItem.removeEventListener(
                this.mediaTempItem.tagName === 'IMG' ? 'load' : 'canplay',
                this.mediaTempLoadFn
            );
        }
    }

    /**
     * @desc
     *
     */
    function startMedia(media, mediaIndex, duration, progressBar, direction) {
        content.append(media); 
    
        // Class ekle
        $(that.mediaTempItem).addClass('current-media ' + direction);
        that.mediaLoading = false;
        that.elements.loading.removeClass('show');
    
        that.mediaStartTime = Date.now();
        that.mediaProgressTick(mediaIndex, duration, progressBar);
        removeMediaEvents.call(that);
    
        if (that.mediaTempItem.tagName === 'VIDEO') { 
            const playPromise = that.mediaTempItem.play();
            if (playPromise !== undefined) {
                playPromise
                    .then(() => {
                        requestAnimationFrame(animateProgress); 
                    })
                    .catch((error) => {
                        console.warn("Video playback engellendi:", error);
                    });
            }
        }
    
        setTimeout(function () {
            $(that.mediaTempItem).addClass('effect');
        }, 250);
    }
    }
function generateRandomIntegerTwo(min, max) {return Math.floor(min + Math.random() * (max - min + 1));}
var NumberGeneratedTwo = generateRandomIntegerTwo(1, 7);

/**
 * @desc
 * @param {} mediaIndex -
 * @param {} itemDuration -
 */
StoryView.prototype.mediaProgressTick = function(mediaIndex, itemDuration, progressBar) {
    var that = this,
        timeDiff = 0,
        tempWidth = 0;
    var item = that.mediaItems[mediaIndex] || {};
    var hasAudio = !!(item.audioUrl && that.audioEl && item.type === 'img');


    // Reset progress timer
    // #
    clearInterval(this.currentMediaProgress);

    this.currentMediaProgress = setInterval(function(a) {
        if (that.mediaLoading) return;

        // Stop when removing
        // #
        if (that.removingState)
            clearInterval(that.currentMediaProgress);

        if (!that.paused) {

            // Play when state is in paused
            // #
            if (that.mediaTempItem.tagName == 'VIDEO')
                that.mediaTempItem.play();

            if (hasAudio) {
                if (that.audioHoldPaused) {
                    that.audioHoldPaused = false;
                    if (that.audioEl.paused) {
                        var resumePromise = that.audioEl.play();
                        if (resumePromise && typeof resumePromise.catch === 'function') {
                            resumePromise.catch(function() {});
                        }
                    }
                }
                if (that.audioEl.ended) {
                    progressBar.css('width', '100%');
                    that.nextMedia();
                    return;
                }
                var audioDuration = isFinite(that.audioEl.duration) ? that.audioEl.duration : 0;
                if (audioDuration <= 0) {
                    return;
                }
                if (!that.audioEl.paused) {
                    tempWidth = (that.audioEl.currentTime / audioDuration) * 100;
                    progressBar.css('width', tempWidth + '%');
                    if (tempWidth >= 100) {
                        that.nextMedia();
                    }
                }
                return;
            }

            timeDiff = Date.now() - that.mediaStartTime;
            tempWidth = (timeDiff / 1000 / itemDuration * 100);
            tempWidth = (tempWidth + that.lastProgressVal) * 1;

            progressBar.css('width', tempWidth + '%')

            // Get next media when finished
            // #
            if (parseInt(tempWidth) >= 100) {
                that.nextMedia();
            }
        }
        //
        // #
        else {
            that.lastProgressVal = tempWidth;
            that.mediaStartTime = Date.now();

            // Pause video when story paused
            // #
            if (that.mediaTempItem.tagName == 'VIDEO')
                that.mediaTempItem.pause();

            if (hasAudio && !that.audioEl.paused) {
                that.audioHoldPaused = true;
                that.audioEl.pause();
            }
        }
    });
}

/**
 * @desc
 * @param {} fromClick -
 */
StoryView.prototype.nextMedia = function(fromClick) {
    // if user clicked next close story view
    // on last media item of last story
    if (this.currentMedia + 1 == this.mediaItems.length && this.currentStory.next().length == 0 && fromClick) {
        this.closeView();
        return;
    }

    var next = this.currentMedia + 1 == this.mediaItems.length ?
        this.finish() :
        this.currentMedia++;


    // Show next media
    // #
    if (!!Number(next) || next === 0)
        this.showMedia(this.currentMedia, 'next');
}

/**
 * @desc
 * @param {} fromClick -
 */
StoryView.prototype.prevMedia = function(fromClick) {

    var prev = this.currentMedia - 1 == -1 && this.currentStory && this.currentStory.prev().length == 0 ?
        this.finish(true) :
        this.currentMedia - 1 == -1 && this.currentStory.prev().length == 0 ?
        this.currentMedia = 0 :
        this.currentMedia--;

    // Show next media
    // #
    if (!!Number(prev) || prev === 0)
        this.showMedia(this.currentMedia, 'prev');
}

/**
 * @desc
 * @param {} mediaIndex -
 * @param {} prev -
 */
StoryView.prototype.finish = function(prev) {
    var story;

    clearInterval(this.currentMediaProgress);

    // Get next story
    // #
    if (!prev && this.currentStory.data().hasNext) {
        story = this.currentStory.next();
        this.currentMedia = 0;
    }
    // Get prev story
    // #
    else if (prev && this.currentStory.prev().length > 0) {
        story = this.currentStory.prev();
        this.currentMedia = story.find('.media li').length - 1;
    }

    if (story) {
        this.mediaItems = [];
        this.currentStory.removeClass('activated');
        this.openStory(story, true);
    } else this.options.autoClose && this.closeView();
}
    window.StoryView = StoryView;
    window.StoryViewFlagger = StoryViewFlagger;
})(jQuery, window, document);
