(function($) {
  "use strict";

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute('content') || '') : '';
  }

  function openHighlightModal(highlightId) {
    const data = { f: "highlight_manage" };
    if (highlightId) {
      data.id = highlightId;
    }
    $(".highlight-modal").remove();
    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      data: data,
      cache: false,
      success: function(response) {
        if (response) {
          $("body").append(response);
          setTimeout(function() {
            $(".highlight-modal").addClass("i_modal_display_in");
          }, 200);
        }
      }
    });
  }

  function setHighlightError($modal, message) {
    const $error = $modal.find(".highlight-error");
    if (!$error.length) {
      return;
    }
    $error.text(message || "").toggleClass("is-visible", Boolean(message));
  }

  function collectSelectedStories($modal) {
    const ids = [];
    $modal.find(".highlight-story-checkbox:checked").each(function() {
      const id = Number($(this).val() || 0);
      if (id > 0) {
        ids.push(id);
      }
    });
    return ids;
  }

  $(document).on("click", ".highlight-add, .highlight-edit", function(e) {
    e.preventDefault();
    e.stopPropagation();
    const highlightId = $(this).data("highlight-id") || "";
    openHighlightModal(highlightId);
  });

  $(document).on("click", ".highlight-save", function() {
    const $modal = $(this).closest(".highlight-modal");
    const highlightId = Number($(this).data("highlight-id") || 0);
    const title = ($modal.find(".highlight-title-input").val() || "").trim();
    const storyIds = collectSelectedStories($modal);

    if (!title) {
      setHighlightError($modal, $modal.data("highlight-title-required") || "Please add a title.");
      return;
    }
    if (!storyIds.length) {
      setHighlightError($modal, $modal.data("highlight-story-required") || "Select at least one story.");
      return;
    }
    setHighlightError($modal, "");

    const formData = new FormData();
    formData.append("f", highlightId > 0 ? "highlight_update" : "highlight_create");
    formData.append("title", title);
    formData.append("stories", JSON.stringify(storyIds));
    formData.append("cover_story_id", storyIds[0]);
    if (highlightId > 0) {
      formData.append("highlight_id", highlightId);
    }
    const coverInput = $modal.find(".highlight-cover-input").get(0);
    if (coverInput && coverInput.files && coverInput.files[0]) {
      formData.append("highlight_cover", coverInput.files[0]);
    }
    const csrfToken = getCsrfToken();
    if (csrfToken) {
      formData.append("csrf_token", csrfToken);
    }

    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      cache: false,
      success: function(response) {
        if (response && response.status === "success") {
          window.location.reload();
        } else {
          const message = response && response.message ? response.message : "Unable to save highlight.";
          setHighlightError($modal, message);
        }
      },
      error: function() {
        setHighlightError($modal, "Unable to save highlight.");
      }
    });
  });

  $(document).on("click", ".highlight-delete", function() {
    const highlightId = Number($(this).data("highlight-id") || 0);
    if (!highlightId) {
      return;
    }
    const $modal = $(this).closest(".highlight-modal");
    const confirmText = $modal.data("highlight-delete-confirm") || "Delete this highlight?";
    if (!window.confirm(confirmText)) {
      return;
    }
    const payload = {
      f: "highlight_delete",
      highlight_id: highlightId
    };
    const csrfToken = getCsrfToken();
    if (csrfToken) {
      payload.csrf_token = csrfToken;
    }
    $.ajax({
      type: "POST",
      url: siteurl + "requests/request.php",
      data: payload,
      dataType: "json",
      cache: false,
      success: function(response) {
        if (response && response.status === "success") {
          window.location.reload();
        } else {
          const message = response && response.message ? response.message : "Unable to delete highlight.";
          setHighlightError($modal, message);
        }
      },
      error: function() {
        setHighlightError($modal, "Unable to delete highlight.");
      }
    });
  });

  $(document).on("change", ".highlight-cover-input", function() {
    const $modal = $(this).closest(".highlight-modal");
    const $preview = $modal.find(".highlight-cover-preview");
    if (!$preview.length) {
      return;
    }
    const file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) {
      const existing = $preview.data("cover-url") || "";
      if (existing) {
        $preview.css("background-image", "url(" + existing + ")").addClass("has-cover");
      } else {
        $preview.css("background-image", "").removeClass("has-cover");
      }
      return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
      if (e && e.target && e.target.result) {
        $preview.css("background-image", "url(" + e.target.result + ")").addClass("has-cover");
      }
    };
    reader.readAsDataURL(file);
  });
})(jQuery);
