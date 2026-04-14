(function($) {
	  "use strict";

	  $(document).on("click", ".search_vl", function() {
	    const value = $("#srcMe").val();
	    const filter = $("#boostFilter").val() || "all";
	    if (value) {
	      const url = window.location.origin + '/admin/manage_boosted_posts?page-id=1&sr=' + encodeURIComponent(value) + '&st=' + encodeURIComponent(filter);
	      window.location.href = url;
	    } else {
	      const url = window.location.origin + '/admin/manage_boosted_posts?page-id=1&st=' + encodeURIComponent(filter);
	      window.location.href = url;
	    }
	  });

	  $(document).on("change", "#boostFilter", function() {
	    $(".search_vl").trigger("click");
	  });

	  $(document).on("click", ".cleanup_boosts", function() {
	    var type = "cleanupBoostedPosts";
	    var data = "f=" + type;
	    var csrf = $('input[name=csrf_token]').first().val();
	    if (csrf) {
	      data += "&csrf_token=" + encodeURIComponent(csrf);
	    }
	    $.ajax({
	      type: "POST",
	      url: siteurl + "request/request.php",
	      data: data,
	      cache: false,
	      beforeSend: function() {
	        $("#general_conf").append(plreLoadingAnimationPlus);
	      },
	      success: function(response) {
	        $(".loaderWrapper").remove();
	        try {
	          var parsed = typeof response === "string" ? JSON.parse(response) : response;
	          if (parsed && parsed.status === "200") {
	            location.reload();
	            return;
	          }
	        } catch (e) {}
	        $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
	        setTimeout(() => {
	          $(".nnauthority").remove();
	        }, 5000);
	      }
	    });
	  });

	})(jQuery);
