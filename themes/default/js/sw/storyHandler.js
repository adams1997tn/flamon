(function($) {
  "use strict";

  // Set background images from data attributes
  function applyBackgrounds() {
    $('[data-background-image]').each(function() {
      const $item = $(this);
      const bgUrl = $item.data('background-image');
      if (bgUrl) {
        const cssUrl = 'url(' + bgUrl + ')';
        $item.css('--story-bg', cssUrl);

        const avatarHolder = $item.find('.story-view-pr-avatar');
        if (avatarHolder.length && !avatarHolder.data('avatar')) {
          avatarHolder.css({
            'background-image': cssUrl,
            'background-size': 'cover',
            'background-position': 'center',
            'background-repeat': 'no-repeat'
          });
        }
      }
    });
  }

  // Set avatars from data-avatar attributes
  function applyAvatars() {
    $('.story-view-pr-avatar').each(function() {
      const avatarUrl = $(this).data('avatar');
      if (avatarUrl) {
        $(this).css({
          'background-image': 'url(' + avatarUrl + ')',
          'background-size': 'cover',
          'background-position': 'center',
          'background-repeat': 'no-repeat'
        });
      }
    });
  }

  // Swiper-powered story strip
  function initStorySwiper() {
    if (typeof Swiper !== 'function') {
      return;
    }

    $('.stories_outer').each(function(index) {
      const $outer = $(this);
      const $container = $outer.find('.stories_scroller.swiper').first();
      const $prev = $outer.find('.stories_nav-prev');
      const $next = $outer.find('.stories_nav-next');

      if (!$container.length) {
        return;
      }

      const existing = $container.data('stories-swiper');
      if (existing && typeof existing.destroy === 'function') {
        existing.destroy(true, true);
      }

      const swiper = new Swiper($container.get(0), {
        wrapperClass: 'stories_track',
        slideClass: 'swiper-slide',
        slidesPerView: 'auto',
        spaceBetween: 0,
        freeMode: true,
        freeModeMomentum: true,
        observer: true,
        observeParents: true,
        observeSlideChildren: true,
        mousewheel: {
          forceToAxis: true,
          sensitivity: 0.6
        },
        navigation: {
          nextEl: $next.get(0),
          prevEl: $prev.get(0)
        },
        on: {
          afterInit: function() {
            const hasOverflow = this.isLocked === false;
            $outer.toggleClass('has-nav', hasOverflow);
            $prev.toggleClass('is-hidden', this.isBeginning);
            $next.toggleClass('is-hidden', this.isEnd);
          },
          slideChange: function() {
            $prev.toggleClass('is-hidden', this.isBeginning);
            $next.toggleClass('is-hidden', this.isEnd);
          },
          resize: function() {
            const hasOverflow = this.isLocked === false;
            $outer.toggleClass('has-nav', hasOverflow);
            $prev.toggleClass('is-hidden', this.isBeginning);
            $next.toggleClass('is-hidden', this.isEnd);
          }
        }
      });

      $container.data('stories-swiper', swiper);
    });
  }

  function buildStoryLang($container) {
    return {
      replyPlaceholder: $container.data('story-reply-placeholder') || '',
      replySent: $container.data('story-reply-sent') || '',
      replyFailed: $container.data('story-reply-failed') || '',
      replyEmpty: $container.data('story-reply-empty') || '',
      replySelf: $container.data('story-reply-self') || '',
      replySendLabel: $container.data('story-reply-send') || '',
      accessFollowers: $container.data('story-access-followers') || '',
      accessSubscribers: $container.data('story-access-subscribers') || '',
      audioMute: $container.data('story-audio-mute') || '',
      audioUnmute: $container.data('story-audio-unmute') || '',
      audioPlay: $container.data('story-audio-play') || '',
      audioPause: $container.data('story-audio-pause') || '',
      audioUnmuteTip: $container.data('story-audio-tip') || '',
      textMore: $container.data('story-text-more') || '',
      textLess: $container.data('story-text-less') || ''
    };
  }

  function initStoryViewFor($container) {
    if (typeof StoryView !== 'function') {
      return;
    }
    try {
      const replyLang = buildStoryLang($container);
      const reactionSetRaw = $container.data('story-reaction-set') || '';
      const reactionSet = String(reactionSetRaw).split(/[,\s]+/).filter(Boolean);
      new StoryView({
        container: $container.get(0),
        autoClose: true,
        lang: replyLang,
        currentUserId: Number($container.data('story-reply-user') || 0),
        reactionSet: reactionSet,
        accessFollowers: $container.data('story-access-followers') || '',
        accessSubscribers: $container.data('story-access-subscribers') || '',
        accessFollowBtn: $container.data('story-access-follow-btn') || '',
        accessSubscribeBtn: $container.data('story-access-subscribe-btn') || '',
        baseUrl: typeof siteurl !== 'undefined' ? siteurl : ''
      });
    } catch (e) {
      console.warn('StoryView initialization failed:', e);
    }
  }

  // Initialize StoryView for each container
  function initStoryViews() {
    $('.my-stories-wrapper').each(function() {
      initStoryViewFor($(this));
    });
  }

  // Public method for reinitialization (e.g., after AJAX load)
  window.reInitStories = function() {
    applyBackgrounds();
    applyAvatars();
    applySeenStories();
    initStorySwiper();
    initStoryViews();
  };

  function getStoryLangDefaults() {
    const fallback = window.storyLang || {};
    return {
      replyPlaceholder: fallback.replyPlaceholder || '',
      replySent: fallback.replySent || '',
      replyFailed: fallback.replyFailed || '',
      replyEmpty: fallback.replyEmpty || '',
      replySelf: fallback.replySelf || '',
      replySendLabel: fallback.replySendLabel || '',
      accessFollowers: fallback.accessFollowers || '',
      accessSubscribers: fallback.accessSubscribers || '',
      accessFollowBtn: fallback.accessFollowBtn || '',
      accessSubscribeBtn: fallback.accessSubscribeBtn || '',
      reactionSet: fallback.reactionSet || '',
      audioMute: fallback.audioMute || '',
      audioUnmute: fallback.audioUnmute || '',
      audioPlay: fallback.audioPlay || '',
      audioPause: fallback.audioPause || '',
      audioUnmuteTip: fallback.audioUnmuteTip || '',
      textMore: fallback.textMore || '',
      textLess: fallback.textLess || '',
      currentUserId: Number(fallback.currentUserId || 0)
    };
  }

  function ensureAvatarStoryContainer() {
    let $container = $('.avatar-story-wrapper').first();
    if ($container.length) {
      return $container;
    }
    const defaults = getStoryLangDefaults();
    $container = $('<div class="my-stories-wrapper story-inline avatar-story-wrapper"></div>');
    $container
      .attr('data-padding-top', '30')
      .attr('data-story-reply-placeholder', defaults.replyPlaceholder)
      .attr('data-story-reply-sent', defaults.replySent)
      .attr('data-story-reply-failed', defaults.replyFailed)
      .attr('data-story-reply-empty', defaults.replyEmpty)
      .attr('data-story-reply-self', defaults.replySelf)
      .attr('data-story-reply-send', defaults.replySendLabel)
      .attr('data-story-reply-user', defaults.currentUserId)
      .attr('data-story-access-followers', defaults.accessFollowers)
      .attr('data-story-access-subscribers', defaults.accessSubscribers)
      .attr('data-story-access-follow-btn', defaults.accessFollowBtn)
      .attr('data-story-access-subscribe-btn', defaults.accessSubscribeBtn)
      .attr('data-story-reaction-set', defaults.reactionSet)
      .attr('data-story-audio-mute', defaults.audioMute)
      .attr('data-story-audio-unmute', defaults.audioUnmute)
      .attr('data-story-audio-play', defaults.audioPlay)
      .attr('data-story-audio-pause', defaults.audioPause)
      .attr('data-story-audio-tip', defaults.audioUnmuteTip)
      .attr('data-story-text-more', defaults.textMore)
      .attr('data-story-text-less', defaults.textLess)
      .css({
        position: 'absolute',
        left: '-9999px',
        top: '-9999px',
        width: '1px',
        height: '1px',
        overflow: 'hidden'
      });
    $('body').append($container);
    return $container;
  }

  $(document).on('click', '.js-story-avatar', function(e) {
    const $avatar = $(this);
    const hasStory = String($avatar.data('has-story') || $avatar.data('hasStory') || '');
    const hasAnyStory = String($avatar.data('has-any-story') || $avatar.data('hasAnyStory') || '');
    function getProfileUrl() {
      const $link = $avatar.closest('.i_post_body_header').find('.i_post_username a').first();
      const href = $link.attr('href');
      if (href) {
        return href;
      }
      const username = String($avatar.data('story-username') || '').trim();
      if (!username) {
        return '';
      }
      let base = typeof siteurl !== 'undefined' ? siteurl : '/';
      if (base.slice(-1) !== '/') {
        base += '/';
      }
      return base + username;
    }
    if (hasStory !== '1') {
      if (hasAnyStory === '0') {
        const profileUrl = getProfileUrl();
        if (profileUrl) {
          window.location.href = profileUrl;
        }
      }
      return;
    }
    const userId = Number($avatar.data('story-user-id') || $avatar.data('storyUserId') || 0);
    if (!userId) {
      return;
    }
    if ($avatar.data('story-loading')) {
      return;
    }
    $avatar.data('story-loading', 1);
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
    $.ajax({
      type: 'POST',
      url: (typeof siteurl !== 'undefined' ? siteurl : '/') + 'requests/request.php',
      dataType: 'json',
      data: {
        f: 'story_public_by_user',
        user_id: userId,
        csrf_token: csrfToken
      },
      success: function(response) {
        if (!response || response.status !== 'ok' || !response.html) {
          if (response && response.status === 'empty' && hasAnyStory === '0') {
            const profileUrl = getProfileUrl();
            if (profileUrl) {
              window.location.href = profileUrl;
            }
          }
          return;
        }
        const $container = ensureAvatarStoryContainer();
        $container.empty().append(response.html);
        applyBackgrounds();
        applyAvatars();
        applySeenStories();
        initStoryViewFor($container);
        const $newItem = $container
          .find('.story-view-item[data-story-id="' + userId + '"]')
          .first();
        if ($newItem.length) {
          $newItem.trigger('click');
        }
      },
      complete: function() {
        $avatar.removeData('story-loading');
      }
    });
  });

  // Initial run on document ready
  $(function() {
    window.reInitStories();
  });

  // Persist seen stories via localStorage
  const seenKeyDefault = 'dizzy_seen_stories_map';
  const seenKeyHighlights = 'dizzy_seen_highlights_map';

  function getSeenKey(scope) {
    return scope === 'highlights' ? seenKeyHighlights : seenKeyDefault;
  }

  function getSeenMap(scope) {
    const key = getSeenKey(scope);
    let map = {};
    try {
      const parsed = JSON.parse(localStorage.getItem(key));
      if (Array.isArray(parsed)) {
        // Legacy array of ids -> mark them as seen with value 1
        parsed.forEach(function(id) {
          map[String(id)] = 1;
        });
      } else if (parsed && typeof parsed === 'object') {
        map = parsed;
      }
    } catch (e) {
      map = {};
    }
    return map;
  }

  function saveSeenMap(map, scope) {
    localStorage.setItem(getSeenKey(scope), JSON.stringify(map || {}));
  }

  function getLastStoryId($slide) {
    let lastId = Number($slide.data('last-story-id') || $slide.data('lastStoryId') || 0);
    if (!lastId) {
      // Fallback: compute from media list data-id values
      const ids = [];
      $slide.find('.media li').each(function() {
        const v = Number($(this).data('id') || 0);
        if (v) {
          ids.push(v);
        }
      });
      if (ids.length) {
        lastId = Math.max.apply(Math, ids);
        $slide.data('last-story-id', lastId);
      }
    }
    return lastId;
  }

  function getSeenId($slide) {
    const seenId = $slide.data('seen-id') || $slide.data('seenId') || $slide.data('highlight-id') || $slide.data('highlightId');
    if (seenId) {
      return String(seenId);
    }
    const storyId = $slide.data('story-id') || $slide.data('storyId');
    return storyId ? String(storyId) : '';
  }

  function findSlideBySeenId(seenId) {
    if (!seenId) {
      return $();
    }
    return $('.story-view-item[data-seen-id="' + seenId + '"], .story-view-item[data-highlight-id="' + seenId + '"], .story-view-item[data-story-id="' + seenId + '"]').first();
  }

  function isSlideSeen($slide, seenMap) {
    const seenId = getSeenId($slide);
    if (!seenId) {
      return false;
    }
    const lastStoryId = getLastStoryId($slide);
    const seenVal = Number(seenMap[seenId] || 0);
    return lastStoryId > 0 && seenVal >= lastStoryId;
  }

  function applySeenStories() {
    const cached = {};
    $('.story-view-item[data-story-id]').each(function() {
      const $slide = $(this);
      const scope = String($slide.data('seen-scope') || 'stories');
      if (!cached[scope]) {
        cached[scope] = getSeenMap(scope);
      }
      const map = cached[scope];
      if (isSlideSeen($slide, map)) {
        $slide.addClass('is-seen');
      } else {
        $slide.removeClass('is-seen');
      }
    });
  }

  function markStoryAsSeen(storyId, lastStoryId, scope) {
    if (!storyId) {
      return;
    }
    const normalizedScope = scope ? String(scope) : 'stories';
    const map = getSeenMap(normalizedScope);
    const strId = String(storyId);
    let lastIdNum = Number(lastStoryId || 0);
    if (!lastIdNum) {
      const $slide = findSlideBySeenId(strId);
      lastIdNum = getLastStoryId($slide);
    }
    if (lastIdNum > 0) {
      map[strId] = lastIdNum;
    } else if (!map[strId]) {
      map[strId] = 1;
    }
    saveSeenMap(map, normalizedScope);
    applySeenStories();
  }

  $(document).on('click', '.story-view-item', function() {
    const sid = getSeenId($(this));
    const lastId = $(this).data('last-story-id');
    const scope = $(this).data('seen-scope') || 'stories';
    markStoryAsSeen(sid, lastId, scope);
  });

  // Expose for StoryView to call when moving between stories
  window.markStoryAsSeen = markStoryAsSeen;
  window.applySeenStories = applySeenStories;

  // Video duration extraction after metadata is loaded
  $(document).on('loadedmetadata', 'video', function() {
    const videoId = $(this).attr('id');
    const duration = this.duration;
    if (videoId) {
      $('.move_' + videoId.replace('video_', '')).attr('data-duration', Math.round(duration));
    }
  });

})(jQuery);
