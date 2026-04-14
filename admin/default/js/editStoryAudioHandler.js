(function ($) {
    "use strict";

    $(document).ready(function () {
        const form = $("#storyAudioEditForm");
        const container = $("#storyAudioEditContainer");
        const fileInput = $("#story_audio_edit_file");
        const selectedFile = $("#storyAudioEditSelectedFile");

        const loaderHTML = `
            <div class="loaderWrapper">
                <div class="loaderContainer">
                    <div class="loader">
                        <div class="i_loading product_page_loading">
                            <div class="dot-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>`;

        fileInput.on("change", function () {
            const fileName = this.files && this.files.length ? this.files[0].name : "";
            selectedFile.text(fileName);
        });

        form.on("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            $.ajax({
                type: "POST",
                url: siteurl + "request/request.php",
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $(".warning_story_audio_title, .warning_story_audio_format, .warning_story_audio_size").hide();
                    container.append(loaderHTML);
                    form.find(":input[type=submit]").prop("disabled", true);
                },
                success: function (data) {
                    setTimeout(() => {
                        form.find(":input[type=submit]").prop("disabled", false);
                    }, 1500);

                    data = $.trim(data);
                    if (data === "200") {
                        location.reload();
                    } else if (data === "1") {
                        $(".warning_story_audio_title").show();
                    } else if (data === "2") {
                        $(".warning_story_audio_format").show();
                    } else if (data === "3") {
                        $(".warning_story_audio_size").show();
                    } else {
                        const errorMsg = `
                            <div class="nnauthority">
                                <div class="no_permis flex_ c3 border_one tabing">${data}</div>
                            </div>`;
                        $("body").append(errorMsg);
                        setTimeout(() => {
                            $(".nnauthority").remove();
                        }, 8000);
                    }

                    $(".loaderWrapper").remove();
                },
                error: function () {
                    form.find(":input[type=submit]").prop("disabled", false);
                    $(".loaderWrapper").remove();
                }
            });
        });
    });
})(jQuery);
