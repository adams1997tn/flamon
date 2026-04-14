(function($) {
  "use strict";

  function ensureProvider(callback) {
    if (window.createLiveProvider) {
      callback();
      return;
    }
    var currentScript = document.currentScript || (function () {
      var scripts = document.getElementsByTagName("script");
      return scripts[scripts.length - 1];
    })();
    var src = currentScript ? currentScript.src : "";
    var basePath = src && src.indexOf("/js/") > -1 ? src.split("/js/")[0] : (window.siteurl || "");
    var providerPath = basePath ? (basePath + "/js/liveProvider.js") : "themes/default/js/liveProvider.js";
    var s = document.createElement("script");
    s.src = providerPath;
    s.onload = callback;
    document.head.appendChild(s);
  }

  ensureProvider(function startLive() {
    function isSecureOrigin() {
      if (typeof window.isSecureContext === "boolean") {
        return window.isSecureContext;
      }
      return window.location && window.location.protocol === "https:";
    }

    function getString(val) {
      return (typeof val === "string") ? val : "";
    }

    function getLiveErrorMessage(err) {
      const name = getString(err && err.name);
      const message = getString(err && err.message);
      const combined = (name + " " + message).toLowerCase();

      const requiresHttps = !isSecureOrigin();
      if (requiresHttps) {
        return window.LANG_LIVE_REQUIRES_HTTPS || "Camera/Microphone access requires HTTPS. Please open this page using https://";
      }

      if (
        name === "NotAllowedError" ||
        name === "PermissionDeniedError" ||
        combined.includes("permission denied") ||
        combined.includes("permission dismissed") ||
        combined.includes("notallowederror")
      ) {
        return window.LANG_LIVE_PERMISSION_DENIED || "Camera/Microphone permission denied. Please allow access in your browser settings and reload the page.";
      }

      if (
        name === "NotFoundError" ||
        name === "DevicesNotFoundError" ||
        combined.includes("notfounderror") ||
        combined.includes("requested device not found") ||
        combined.includes("no device")
      ) {
        return window.LANG_LIVE_DEVICE_NOT_FOUND || "No camera or microphone found. Please connect a device and try again.";
      }

      if (
        name === "NotReadableError" ||
        name === "TrackStartError" ||
        combined.includes("notreadableerror") ||
        combined.includes("trackstarterror") ||
        combined.includes("could not start video source") ||
        combined.includes("device in use")
      ) {
        return window.LANG_LIVE_DEVICE_IN_USE || "Camera or microphone is currently in use by another application. Please close other apps and try again.";
      }

      if (message) {
        return message;
      }

      return window.LANG_LIVE_GENERIC_ERROR || "Unable to access camera/microphone. Please check permissions and try again.";
    }

    function showLiveError(err) {
      const msg = (typeof err === "string") ? err : getLiveErrorMessage(err);
      try {
        alert(msg);
      } catch (e) {
        // ignore
      }
    }
    const providerType = (window.liveProvider || "agora").toLowerCase();
    const provider = window.createLiveProvider({
      provider: providerType,
      wsUrl: window.livekitWSUrl || "",
      AgoraRTC: window.AgoraRTC
    });
    let client = null;
    let localTracks = {
      videoTrack: null,
      audioTrack: null
    };
    let localTrackState = {
      videoTrackMuted: false,
      audioTrackMuted: false
    };
    let remoteUsers = {};
    let options = {
      appid: window.liveAppID || null,
      channel: window.liveChannel || null,
      uid: null,
      token: '',
      role: (window.liveUserID == window.liveCreator) ? 'host' : 'audience',
      audienceLatency: 2,
      wsUrl: window.livekitWSUrl || ''
    };
    let mics = [], cams = [], currentMic, currentCam;
    let remoteAudioTrack = null;
    let remoteAudioTrackUserId = null;
    let remoteAudioUnlockBound = false;

    function bindRemoteAudioUnlock() {
      if (remoteAudioUnlockBound) {
        return;
      }
      remoteAudioUnlockBound = true;
      const unlockRemoteAudio = function () {
        if (!remoteAudioTrack || typeof remoteAudioTrack.play !== "function") {
          return;
        }
        try {
          remoteAudioTrack.play();
        } catch (error) {
          console.warn(error);
        }
      };
      document.addEventListener("touchstart", unlockRemoteAudio, { passive: true });
      document.addEventListener("click", unlockRemoteAudio, { passive: true });
    }

    async function fetchJoinToken() {
      return new Promise((resolve, reject) => {
        $.ajax({
          type: "POST",
          url: window.siteurl + "requests/request.php",
          dataType: "json",
          data: { f: "agoraNewToken", ch: options.channel, host: options.role === "host" ? "1" : "0" },
          success: function (res) {
            if (res && res.status === "ok" && res.token) {
              options.token = res.token;
              options.uid = res.uid || options.uid;
              resolve(res);
            } else {
              reject(res && res.message ? new Error(res.message) : new Error("Token fetch failed"));
            }
          },
          error: function () {
            reject(new Error("Token request failed"));
          }
        });
      });
    }

    async function join() {
      try {
        if (options.role === "host" && !isSecureOrigin()) {
          showLiveError(getLiveErrorMessage({ name: "SecurityError", message: "" }));
          return;
        }

        // Always fetch a fresh token (Agora projects with certificates require it; LiveKit also needs it)
        await fetchJoinToken();
        await provider.initClient({
          mode: "live",
          codec: "vp8",
          role: options.role,
          audienceLatency: options.audienceLatency,
          onUserPublished: handleUserPublished,
          onUserUnpublished: handleUserUnpublished
        });
        client = provider.getClient();

        options.uid = await provider.joinChannel({
          appid: options.appid,
          channel: options.channel,
          token: options.token || null,
          uid: options.uid || null,
          wsUrl: options.wsUrl || null
        });

        if (options.role === "host") {
          [localTracks.audioTrack, localTracks.videoTrack] = await Promise.all([
            provider.createMicrophoneTrack({ microphoneId: currentMic?.deviceId }),
            provider.createCameraTrack({ cameraId: currentCam?.deviceId })
          ]);

          localTracks.videoTrack.play("local-player");
          await provider.publish(Object.values(localTracks));

          // Try loading device list after media permission is granted.
          try {
            await mediaDeviceTest();
          } catch (err) {
            console.warn(err);
          }
        }
      } catch (err) {
        console.error(err);
        showLiveError(err);
      }
    }

    async function leave() {
      for (const trackName in localTracks) {
        const track = localTracks[trackName];
        if (track) {
          track.stop();
          track.close();
          localTracks[trackName] = null;
        }
      }
      remoteUsers = {};
      $("#remote-playerlist").html("");
      await provider.leave();
    }

  async function muteAudio() {
    if (!localTracks.audioTrack) return;
    await localTracks.audioTrack.setMuted(true);
    localTrackState.audioTrackMuted = true;
    $("#mute-audio").text(window.LANG_UNMUTE_AUDIO || "Unmute Audio");
  }

  async function unmuteAudio() {
    if (!localTracks.audioTrack) return;
    await localTracks.audioTrack.setMuted(false);
    localTrackState.audioTrackMuted = false;
    $("#mute-audio").text(window.LANG_MUTE_AUDIO || "Mute Audio");
  }

  async function muteVideo() {
    if (!localTracks.videoTrack) return;
    await localTracks.videoTrack.setMuted(true);
    localTrackState.videoTrackMuted = true;
    $("#mute-video").text(window.LANG_UNMUTE_VIDEO || "Unmute Video");
  }

  async function unmuteVideo() {
    if (!localTracks.videoTrack) return;
    await localTracks.videoTrack.setMuted(false);
    localTrackState.videoTrackMuted = false;
    $("#mute-video").text(window.LANG_MUTE_VIDEO || "Mute Video");
  }

  async function switchCamera(deviceIdOrLabel) {
    if (!localTracks.videoTrack) return;
    currentCam = cams.find(cam => cam.deviceId === deviceIdOrLabel || cam.label === deviceIdOrLabel);
    if (!currentCam || !currentCam.deviceId) return;
    try {
      await localTracks.videoTrack.setDevice(currentCam.deviceId);
    } catch (err) {
      console.error(err);
      showLiveError(err);
    }
  }

  async function switchMicrophone(deviceIdOrLabel) {
    if (!localTracks.audioTrack) return;
    currentMic = mics.find(mic => mic.deviceId === deviceIdOrLabel || mic.label === deviceIdOrLabel);
    if (!currentMic || !currentMic.deviceId) return;
    try {
      await localTracks.audioTrack.setDevice(currentMic.deviceId);
    } catch (err) {
      console.error(err);
      showLiveError(err);
    }
  }

	  async function mediaDeviceTest() {
	    if (providerType === "agora" && window.AgoraRTC) {
	      mics = await window.AgoraRTC.getMicrophones();
	      cams = await window.AgoraRTC.getCameras();
	    } else if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
      const devices = await navigator.mediaDevices.enumerateDevices();
      mics = devices.filter((d) => d.kind === "audioinput");
      cams = devices.filter((d) => d.kind === "videoinput");
    }
    currentMic = mics[0];
    currentCam = cams[0];
    $(".mic-list").empty();
    $(".cam-list").empty();

    mics.forEach(mic => {
      const label = mic.label || mic.deviceId || "Microphone";
      const deviceId = mic.deviceId || "";
      $(".mic-list").append(
        `<a class="dropdown-item" href="#" data-device-id="${deviceId}">${label}</a>`
      );
    });

    cams.forEach(cam => {
      const label = cam.label || cam.deviceId || "Camera";
      const deviceId = cam.deviceId || "";
      $(".cam-list").append(
        `<a class="dropdown-item" href="#" data-device-id="${deviceId}">${label}</a>`
      );
    });
  }

  async function subscribe(user, mediaType) {
    const uid = user.uid;
    await provider.subscribe(user, mediaType);
    if (mediaType === 'video') {
      const player = $(`
        <div id="player-wrapper-${uid}">
          <p class="player-name">remoteUser(${uid})</p>
          <div id="player-${uid}" class="player"></div>
        </div>
      `);
      $("#remote-playerlist").append(player);
      user.videoTrack.play(`player-${uid}`, { fit: "contain" });
    }
    if (mediaType === 'audio') {
      remoteAudioTrack = user.audioTrack;
      remoteAudioTrackUserId = uid;
      bindRemoteAudioUnlock();
      user.audioTrack.play();
    }
  }

  function handleUserPublished(user, mediaType) {
    const id = user.uid;
    remoteUsers[id] = user;
    subscribe(user, mediaType);
  }

  function handleUserUnpublished(user, mediaType) {
    if (mediaType === 'video') {
      const id = user.uid;
      delete remoteUsers[id];
      $(`#player-wrapper-${id}`).remove();
    } else if (mediaType === 'audio' && remoteAudioTrackUserId === user.uid) {
      remoteAudioTrack = null;
      remoteAudioTrackUserId = null;
    }
  }

  function ScrollBottomLiveChat() {
    const box = $(".live_right_in_right_in");
    if (box.length > 0) {
      box.stop().animate({ scrollTop: box[0].scrollHeight }, 100);
    }
  }

	  $(document).ready(function () {
	    (async function initLive() {
	      try {
	        if (options.role === "host") {
	          try {
	            await mediaDeviceTest();
	          } catch (err) {
	            // Do not block live start when device listing fails on some mobile browsers.
	            console.warn(err);
	          }
	        }
	        await join();
	      } catch (err) {
	        console.error(err);
	        showLiveError(err);
	      }
	    })();

	    $("body").on("click", "#leave", leave);
	    $("body").on("click", "#mute-audio", function () {
	      localTrackState.audioTrackMuted ? unmuteAudio() : muteAudio();
    });
    $("body").on("click", "#mute-video", function () {
      localTrackState.videoTrackMuted ? unmuteVideo() : muteVideo();
    });

    $("body").on("click", ".camera_chs", function () {
      $(".camList").toggleClass("camListOpen");
    });

    $("body").on("click", ".mick_chs", function () {
      $(".micList").toggleClass("camListOpen");
    });

    $("body").on("mouseup touchend", function (e) {
      const listCont = $('.camList , .micList');
      if (!listCont.is(e.target) && listCont.has(e.target).length === 0) {
        listCont.removeClass('camListOpen');
      }
    });

    $(".cam-list").on("click", "a", function (e) {
      e.preventDefault();
      const deviceId = $(this).data("deviceId") || $(this).attr("data-device-id");
      const label = $(this).data("label") || $(this).text();
      switchCamera(deviceId || label);
    });

	    $(".mic-list").on("click", "a", function (e) {
	      e.preventDefault();
	      const deviceId = $(this).data("deviceId") || $(this).attr("data-device-id");
	      const label = $(this).data("label") || $(this).text();
	      switchMicrophone(deviceId || label);
	    });
	    const liveChatEnabled = String(window.liveChatEnabled || "1") === "1";
	    const liveGiftEnabled = String(window.liveGiftEnabled || "1") === "1";

	    // Online count + likes + time update
	    setInterval(function () {
      $.ajax({
        type: "POST",
        url: window.siteurl + "requests/live.php",
        data: { f: 'live_calcul', lid: window.theLiveID },
        dataType: "json",
        success: function (res) {
          if (res.onlineCount) $(".sumonline").html(res.onlineCount);
          if (res.time) $(".count_time").html(res.time);
          if (res.likeCount) $(".lp_sum_l").html(res.likeCount);
          if (typeof window.updateLivePinnedProduct === "function") {
            window.updateLivePinnedProduct(res.pinned || '', res.pinnedId || '');
          }
          if (res.finished) window.location.href = res.finished;
        }
      });
    }, 15000);

    function getLiveGiftAnimationTarget() {
      var $target = $(".live_vide__holder:visible").first();
      if (!$target.length) {
        $target = $(".live_vide__holder").first();
      }
      if (!$target.length) {
        $target = $(".filtvid:visible").first();
      }
      if (!$target.length) {
        $target = $(".filtvid").first();
      }
      return $target;
    }

    function playLiveGiftAnimation(animationUrl) {
      var $animationTarget = getLiveGiftAnimationTarget();
      if (!animationUrl || !$animationTarget.length) {
        return;
      }
      $(".live_animation_wrapper").remove();
      var $wrapper = $('<div class="live_animation_wrapper"><div class="live_an_img"><img alt=""></div></div>');
      $wrapper.find("img").attr("src", animationUrl);
      $animationTarget.append($wrapper);
      setTimeout(function () {
        $wrapper.remove();
      }, 2800);
    }

    function processGiftAnimations($scope) {
      if (!$scope || !$scope.length) {
        return;
      }
      $scope.find("[data-gift-anim]").addBack("[data-gift-anim]").each(function () {
        var $item = $(this);
        if ($item.attr("data-gift-played") === "1") {
          return;
        }
        var animUrl = $item.attr("data-gift-anim");
        if (animUrl) {
          playLiveGiftAnimation(animUrl);
        }
        $item.attr("data-gift-played", "1");
      });
    }

	    // New chat messages
	    setInterval(function () {
	      if (!liveChatEnabled) {
	        return;
	      }
	      const lastCom = $(".eo2As:last").attr("id") || '';
	      const postData = {
	        f: 'liveLastMessage',
        idc: window.theLiveID,
        lc: lastCom
      };
    
      $.post(window.siteurl + "requests/live.php", postData, function (response) {
        const trimmed = (response || '').trim();
        if (!trimmed || trimmed.includes('no new live messages')) {
          return; // boş cevapta işlem yapma
        }
    
        var $newItems = $(response);
        if ($('.gElp9').length === 0) {
          $(".live_right_in_right_in").append($newItems);
        } else {
          $(".cUq_" + lastCom).after($newItems);
        }
        processGiftAnimations($newItems);
      });
    }, 6000);

	    // Send message
	    $("body").on("click", ".livesendmes", function () {
	      if (!liveChatEnabled) {
	        return;
	      }
	      const value = $(".lmSize").val();
	      if (value.trim()) {
	        LiveMessage(window.theLiveID, value, 'livemessage');
	      }
	    });

	    $(document).on('keydown', ".lmSize", function (e) {
	      if (!liveChatEnabled) {
	        return;
	      }
	      if (e.which === 13 && $(this).val().trim()) {
	        LiveMessage(window.theLiveID, $(this).val(), 'livemessage');
	        e.preventDefault();
      }
    });

    function LiveMessage(ID, value, type) {
      $.post(window.siteurl + 'requests/request.php', {
        f: type,
        id: ID,
        val: encodeURIComponent(value)
      }, function (response) {
        if (response !== '404') {
          $(".live_right_in_right_in").append(response);
          ScrollBottomLiveChat();
        }
        $(".lmSize").val('');
        $(".Message_stickersContainer").remove();
        $(".nanos").css('height', '0px');
      });
    }

    // Emoji
    $("body").on("click", ".getMEmojisa", function () {
      if (!$(".Message_stickersContainer").length) {
        $.post(window.siteurl + 'requests/request.php', {
          f: 'memoji',
          id: $(this).data("type")
        }, function (res) {
          $(".nanos").css('height', '348px').append(res);
        });
      } else {
        $(".Message_stickersContainer").remove();
        $(".nanos").css('height', '0px');
      }
    });

    $("body").on("click", ".emoji_item_m", function () {
      const emoji = $(this).data("emoji");
      const val = $(".lmSize").val();
      $(".lmSize").val(val + ' ' + emoji + ' ');
    });

	    // Gift panel toggle
	    $("body").on("click", ".live_gift_call", function () {
	      if (!liveGiftEnabled) {
	        return;
	      }
	      $(".live_footer_holder").addClass("live_footer_holder_show");
	      $(".live__live_video_holder").append("<div class='appendBoxLive'></div>");
	    });

    $("body").on("click", ".appendBoxLive", function () {
      $(".live_footer_holder").removeClass("live_footer_holder_show");
      $(this).remove();
    });
    
    $("body").on("click", ".camcloseCall", function() {
        var type = 'finishLiveStreaming';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $(".i_modal_bg_in").remove(); // remove any stale modal before appending
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    
    $("body").on("click", ".camclose", function() {
        var type = 'finishLive';
        var liveID = window.theLiveID;
        var data = 'f=' + type + '&lid=' + liveID;
        $(".i_modal_bg_in").removeClass("i_modal_display_in");
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            success: function(response) {
                leave();
                if (response == 'finished') {
                    setTimeout(() => {
                        window.location.href = siteurl;
                    }, 2000);
                }
            }
        });
    });
    
    $("body").on("click", ".no-del", function() {
        $(".i_modal_bg_in").removeClass("i_modal_display_in").remove();
    });
    
    if (window.liveUserID === window.liveCreator) {
        $("body").on("click", ".camera_close", function () {
            const data = {
                f: 'finishLive',
                lid: window.theLiveID
            };
            $.ajax({
                type: 'POST',
                url: window.siteurl + 'requests/request.php',
                data: data,
                success: function (response) {
                    if (response === 'finished') {
                        window.location.href = window.siteurl;
                    }
                }
            });
        });
    }

    // Responsive
    function deviceResizeFunction() {
      const vW = $(window).width();
      $(".live_left").toggle(vW >= 1300);
      $(".header").toggle(vW >= 1050);
      $(".live_wrapper_tik").css("padding-top", vW < 1050 ? "0px" : "72px");
      $(".live__live_video_holder").toggleClass("max_height_live_mobile", vW < 1050);
      $(".live_video_header").toggleClass("live_video_header_mobile", vW < 1050);
      $(".exen, .sumonline").toggleClass("loi", vW < 1050);
      $(".i_header_btn_item").toggleClass("i_header_btn_item_live_mobile", vW < 1050);
      $(".live_footer_holder").toggle(vW >= 1050);
      $(".live_right_in_right").toggleClass("live_right_in_right_mobile", vW < 1050);
      $(".live_holder_plus_in").toggleClass("live_plus_mobile", vW < 1050);
      $(".live_gift_call").toggle(vW < 1050);
      if (vW >= 1050) {
        $(".live_right_in_right").removeClass("live_interactions_open");
        $(".live_holder_plus_in").removeClass("live_actions_open");
        $(".live_action_menu_btn").attr("aria-expanded", "false");
        $(".live_interactions_toggle_btn").removeClass("is-active").attr("aria-expanded", "false");
      }
      if (vW < 700) $(".mobile_footer_fixed_menu_container").remove();
    }

  $(window).on("resize", deviceResizeFunction);
  deviceResizeFunction();
  ScrollBottomLiveChat();
});
  });
})(jQuery);
