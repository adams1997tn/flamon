(function (window, document) {
  "use strict";

  var loaderState = {
    pending: null,
    scriptLoaded: false,
    styleLoaded: false,
  };

  function getLoaderScript() {
    return document.querySelector('script[data-videojs-loader="1"]');
  }

  function deriveThemeBaseFromScript(scriptSrc) {
    if (!scriptSrc) {
      return "";
    }
    return scriptSrc.replace(/\/js\/videojs-loader\.js(?:\?.*)?$/i, "");
  }

  function getConfig() {
    var loaderScript = getLoaderScript();
    var themeBase = deriveThemeBaseFromScript(loaderScript ? loaderScript.src : "");
    var jsUrl = loaderScript ? loaderScript.getAttribute("data-videojs-src") : "";
    var cssUrl = loaderScript ? loaderScript.getAttribute("data-videojs-css") : "";

    if (!jsUrl && themeBase) {
      jsUrl = themeBase + "/js/videojs/video.js";
    }
    if (!cssUrl && themeBase) {
      cssUrl = themeBase + "/css/videojscss/video-js.css";
    }

    return {
      jsUrl: jsUrl,
      cssUrl: cssUrl,
    };
  }

  function patchVideoJsScrubbing(videojsLib) {
    if (!videojsLib || videojsLib.__dizzyScrubPatchApplied) {
      return;
    }

    var Player = typeof videojsLib.getComponent === "function"
      ? videojsLib.getComponent("Player")
      : null;

    if (!Player || !Player.prototype || typeof Player.prototype.currentTime !== "function") {
      videojsLib.__dizzyScrubPatchApplied = true;
      return;
    }

    var originalCurrentTime = Player.prototype.currentTime;
    Player.prototype.currentTime = function (seconds) {
      if (typeof seconds !== "undefined" && typeof this.scrubbing === "function" && this.scrubbing()) {
        var parsed = Number(seconds);
        if (isFinite(parsed)) {
          if (parsed < 0) {
            parsed = 0;
          }
          if (this.cache_) {
            this.cache_.currentTime = parsed;
          }
        }
      }

      return originalCurrentTime.apply(this, arguments);
    };

    videojsLib.__dizzyScrubPatchApplied = true;
  }

  function ensureCss(url) {
    if (!url || loaderState.styleLoaded) {
      return Promise.resolve();
    }

    var existing = document.querySelector('link[rel="stylesheet"][href="' + url + '"]');
    if (existing) {
      loaderState.styleLoaded = true;
      return Promise.resolve();
    }

    return new Promise(function (resolve, reject) {
      var link = document.createElement("link");
      link.rel = "stylesheet";
      link.href = url;
      link.onload = function () {
        loaderState.styleLoaded = true;
        resolve();
      };
      link.onerror = function () {
        reject(new Error("Failed to load Video.js stylesheet: " + url));
      };
      document.head.appendChild(link);
    });
  }

  function ensureScript(url) {
    if (window.videojs || loaderState.scriptLoaded) {
      loaderState.scriptLoaded = true;
      patchVideoJsScrubbing(window.videojs);
      return Promise.resolve(window.videojs || null);
    }

    if (!url) {
      return Promise.reject(new Error("Video.js script url is missing"));
    }

    var existing = document.querySelector('script[src="' + url + '"]');
    if (existing) {
      return new Promise(function (resolve, reject) {
        if (window.videojs) {
          loaderState.scriptLoaded = true;
          patchVideoJsScrubbing(window.videojs);
          resolve(window.videojs);
          return;
        }
        existing.addEventListener("load", function () {
          loaderState.scriptLoaded = true;
          patchVideoJsScrubbing(window.videojs);
          resolve(window.videojs || null);
        }, { once: true });
        existing.addEventListener("error", function () {
          reject(new Error("Failed to load Video.js script: " + url));
        }, { once: true });
      });
    }

    return new Promise(function (resolve, reject) {
      var script = document.createElement("script");
      script.src = url;
      script.defer = true;
      script.onload = function () {
        loaderState.scriptLoaded = true;
        patchVideoJsScrubbing(window.videojs);
        resolve(window.videojs || null);
      };
      script.onerror = function () {
        reject(new Error("Failed to load Video.js script: " + url));
      };
      document.head.appendChild(script);
    });
  }

  function ensureVideoJs() {
    if (window.videojs) {
      loaderState.scriptLoaded = true;
      patchVideoJsScrubbing(window.videojs);
      return Promise.resolve(window.videojs);
    }

    if (loaderState.pending) {
      return loaderState.pending;
    }

    var config = getConfig();
    loaderState.pending = Promise.all([
      ensureCss(config.cssUrl),
      ensureScript(config.jsUrl),
    ]).then(
      function () {
        loaderState.pending = null;
        patchVideoJsScrubbing(window.videojs);
        return window.videojs || null;
      },
      function (error) {
        loaderState.pending = null;
        throw error;
      }
    );

    return loaderState.pending;
  }

  function isHtml5VideoUrl(value) {
    if (!value) {
      return false;
    }
    return /\.(mp4|m4v|webm|ogv|ogg|m3u8)(?:[\?#].*)?$/i.test(value);
  }

  function galleryNeedsVideoJs(galleryRoot) {
    if (!galleryRoot) {
      return false;
    }
    if (typeof Element !== "undefined" && !(galleryRoot instanceof Element)) {
      return false;
    }

    if (galleryRoot.querySelector("video, .lg-html5, .video-js")) {
      return true;
    }

    var mediaNodes = galleryRoot.querySelectorAll("[href], [data-src], [data-html]");
    for (var i = 0; i < mediaNodes.length; i += 1) {
      var href = mediaNodes[i].getAttribute("href") || "";
      var dataSrc = mediaNodes[i].getAttribute("data-src") || "";
      var dataHtml = mediaNodes[i].getAttribute("data-html") || "";

      if (isHtml5VideoUrl(href) || isHtml5VideoUrl(dataSrc)) {
        return true;
      }
      if (dataHtml && /<\s*video\b/i.test(dataHtml)) {
        return true;
      }
      if (dataHtml && (dataHtml.charAt(0) === "#" || dataHtml.charAt(0) === ".")) {
        try {
          var refNode = document.querySelector(dataHtml);
          if (refNode && refNode.querySelector("video, .lg-html5, .video-js")) {
            return true;
          }
        } catch (e) {
          // Ignore invalid selectors and continue scanning.
        }
      }
    }

    return false;
  }

  function pageNeedsVideoJs() {
    if (document.querySelector("video.video-js, .lg-html5, [data-html*='<video'], [data-html*='<VIDEO']")) {
      return true;
    }
    return galleryNeedsVideoJs(document.body);
  }

  window.dizzyVideoJsLoader = {
    ensure: ensureVideoJs,
    galleryNeedsVideoJs: galleryNeedsVideoJs,
    pageNeedsVideoJs: pageNeedsVideoJs,
    isLoaded: function () {
      return !!window.videojs;
    },
  };
})(window, document);
