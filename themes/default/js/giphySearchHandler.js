(function(window, document) {
  "use strict";

  var timers = new WeakMap();
  var requests = new WeakMap();
  var lastQueries = new WeakMap();

  function closestSearchUI(sourceEl) {
    if (!sourceEl || !sourceEl.closest) {
      return null;
    }
    return sourceEl.classList && sourceEl.classList.contains("giphy_search_form")
      ? sourceEl
      : sourceEl.closest(".giphy_search_form");
  }

  function getContainerInfo(searchUI) {
    var container = searchUI ? searchUI.closest(".stickersContainer, .Message_stickersContainer") : null;
    var isConversation = !!(container && container.classList.contains("Message_stickersContainer"));

    return {
      container: container,
      resultsSelector: isConversation ? ".giphy_results_container_conversation" : ".giphy_results_container",
      containerSelector: isConversation ? ".Message_stickersContainer" : ".stickersContainer"
    };
  }

  function clearTimer(searchUI) {
    var timerId = timers.get(searchUI);
    if (timerId) {
      window.clearTimeout(timerId);
      timers.delete(searchUI);
    }
  }

  function abortRequest(searchUI) {
    var xhr = requests.get(searchUI);
    if (xhr && xhr.readyState !== 4) {
      xhr.abort();
    }
    requests.delete(searchUI);
  }

  function setLoading(searchUI, isLoading) {
    var button = searchUI.querySelector(".giphy_search_btn");
    var info = getContainerInfo(searchUI);
    var results = info.container ? info.container.querySelector(info.resultsSelector) : null;

    if (button) {
      button.disabled = !!isLoading;
    }
    if (results) {
      results.style.opacity = isLoading ? "0.45" : "1";
    }
  }

  function updateResults(searchUI, responseText) {
    var wrapper = document.createElement("div");
    var info = getContainerInfo(searchUI);
    var currentContainer = info.container;
    var currentResults = currentContainer ? currentContainer.querySelector(info.resultsSelector) : null;
    var nextContainer;
    var nextResults;

    wrapper.innerHTML = (responseText || "").trim();
    nextContainer = wrapper.querySelector(info.containerSelector);
    nextResults = nextContainer ? nextContainer.querySelector(info.resultsSelector) : null;

    if (currentResults && nextResults) {
      currentResults.innerHTML = nextResults.innerHTML;
      currentResults.style.opacity = "1";
      return;
    }

    if (currentContainer && nextContainer) {
      currentContainer.outerHTML = nextContainer.outerHTML;
    }
  }

  function requestSearch(searchUI, forceRequest) {
    var type = searchUI.getAttribute("data-request-type") || "";
    var id = searchUI.getAttribute("data-id") || "";
    var input = searchUI.querySelector(".giphy_search_input");
    var query = input ? (input.value || "").trim() : "";
    var previousQuery = lastQueries.get(searchUI);
    var xhr;

    if (!type || !id) {
      return false;
    }

    if (!forceRequest && previousQuery === query) {
      return false;
    }

    clearTimer(searchUI);
    abortRequest(searchUI);
    lastQueries.set(searchUI, query);
    setLoading(searchUI, true);

    xhr = new XMLHttpRequest();
    requests.set(searchUI, xhr);
    xhr.open("POST", (window.siteurl || "") + "requests/request.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) {
        return;
      }

      requests.delete(searchUI);
      setLoading(searchUI, false);

      if (xhr.status >= 200 && xhr.status < 300) {
        updateResults(searchUI, xhr.responseText);
      }
    };
    xhr.send("f=" + encodeURIComponent(type) + "&id=" + encodeURIComponent(id) + "&q=" + encodeURIComponent(query));

    return false;
  }

  function scheduleSearch(searchUI) {
    clearTimer(searchUI);
    timers.set(searchUI, window.setTimeout(function() {
      requestSearch(searchUI, false);
    }, 300));
    return false;
  }

  window.dizzyRunGiphySearch = function(sourceEl, forceRequest) {
    var searchUI = closestSearchUI(sourceEl);
    if (!searchUI) {
      return false;
    }
    return requestSearch(searchUI, !!forceRequest);
  };

  window.dizzyHandleGiphyInput = function(sourceEl) {
    var searchUI = closestSearchUI(sourceEl);
    if (!searchUI) {
      return false;
    }
    return scheduleSearch(searchUI);
  };

  window.dizzyHandleGiphyKeydown = function(sourceEl, event) {
    var e = event || window.event;
    var keyCode = (e && (e.which || e.keyCode)) || 0;
    var searchUI = closestSearchUI(sourceEl);

    if (!searchUI) {
      return true;
    }

    if (keyCode === 13) {
      if (e) {
        e.preventDefault();
      }
      return requestSearch(searchUI, true);
    }

    if (keyCode === 27) {
      if (sourceEl) {
        sourceEl.value = "";
      }
      if (e) {
        e.preventDefault();
      }
      lastQueries.delete(searchUI);
      return requestSearch(searchUI, true);
    }

    return true;
  };

  document.addEventListener("input", function(event) {
    var target = event.target;
    if (target && target.classList && target.classList.contains("giphy_search_input")) {
      window.dizzyHandleGiphyInput(target);
    }
  });

  document.addEventListener("search", function(event) {
    var target = event.target;
    if (target && target.classList && target.classList.contains("giphy_search_input")) {
      window.dizzyHandleGiphyInput(target);
    }
  }, true);

  document.addEventListener("click", function(event) {
    var button = event.target && event.target.closest ? event.target.closest(".giphy_search_btn") : null;
    if (!button) {
      return;
    }
    event.preventDefault();
    window.dizzyRunGiphySearch(button, true);
  });

  document.addEventListener("keydown", function(event) {
    var target = event.target;
    if (target && target.classList && target.classList.contains("giphy_search_input")) {
      window.dizzyHandleGiphyKeydown(target, event);
    }
  });
})(window, document);
