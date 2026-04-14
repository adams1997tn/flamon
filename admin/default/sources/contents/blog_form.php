<?php
$isEdit = isset($blogFormMode) && $blogFormMode === 'edit' && !empty($blogDetails);
$formTitle = $isEdit ? $LANG['edit_blog_post'] : $LANG['create_blog_post'];
$actionValue = $isEdit ? 'updateBlogPost' : 'createBlogPost';
$blogID = $isEdit ? (int) $blogDetails['blog_id'] : 0;
$titleVal = $isEdit ? $blogDetails['title'] : '';
$slugVal = $isEdit ? $blogDetails['slug'] : '';
$excerptVal = $isEdit ? $blogDetails['excerpt'] : '';
$contentVal = $isEdit ? $blogDetails['content_html'] : '';
$statusVal = $isEdit ? $blogDetails['status'] : 'draft';
$isFeaturedVal = $isEdit ? $blogDetails['is_featured'] : '0';
$allowReactionsVal = $isEdit ? $blogDetails['allow_reactions'] : '1';
$coverUrlVal = $isEdit ? $blogDetails['cover_url'] : '';
$metaTitleVal = $isEdit ? $blogDetails['meta_title'] : '';
$metaDescVal = $isEdit ? $blogDetails['meta_description'] : '';
$publishTs = $isEdit ? ($blogDetails['published_at'] ?? null) : null;
$publishVal = '';
if (!empty($publishTs)) {
    $publishVal = date('Y-m-d\TH:i', (int)$publishTs);
}
$coverPreview = $coverUrlVal;
if (empty($coverPreview) && $isEdit && !empty($blogDetails['cover_upload_id'])) {
    $file = $iN->iN_GetUploadedFileDetails($blogDetails['cover_upload_id']);
    if ($file) {
        $path = $file['upload_tumbnail_file_path'] ?? $file['uploaded_file_path'] ?? '';
        if ($path !== '') {
            $coverPreview = str_replace(APP_ROOT_PATH, rtrim($base_url, '/'), $path);
            $coverPreview = str_replace(DIRECTORY_SEPARATOR, '/', $coverPreview);
        }
    }
}
?>
<div class="i_contents_container blog-form-page">
  <div class="i_general_white_board border_one column flex_ tabing__justify">
    <div class="i_general_title_box">
      <?php echo iN_HelpSecure($formTitle); ?>
    </div>

    <div class="i_general_row_box column flex_ white_board_padding_ blog-admin-form" id="general_conf">
      <form enctype="multipart/form-data" method="post" id="blogPostForm">
        <?php echo csrf_token_field(); ?>
        <input type="hidden" name="f" value="<?php echo iN_HelpSecure($actionValue); ?>">
        <?php if ($isEdit) { ?>
            <input type="hidden" name="blog_id" value="<?php echo iN_HelpSecure($blogID); ?>">
        <?php } ?>
        <div class="blog-admin-grid">
          <div class="blog-admin-main">
            <div class="blog-card">
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_title']); ?></div>
              <input type="text" name="blog_title" class="i_input flex_" value="<?php echo iN_HelpSecure($titleVal); ?>">
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_title_help']); ?></div>
              <div class="warning_wrapper warning_one"><?php echo iN_HelpSecure($LANG['blog_title']); ?></div>
            </div>

            <div class="blog-card two-col">
              <div>
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_slug']); ?></div>
                <input type="text" name="blog_slug" class="i_input flex_" value="<?php echo iN_HelpSecure($slugVal); ?>" placeholder="<?php echo iN_HelpSecure($LANG['blog_slug']); ?>">
                <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_slug_help']); ?></div>
              </div>
              <div>
                <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_publish_date']); ?></div>
                <input type="datetime-local" name="blog_publish_date" class="i_input flex_" value="<?php echo iN_HelpSecure($publishVal); ?>">
                <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_publish_help']); ?></div>
              </div>
            </div>

            <div class="blog-card">
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_excerpt']); ?></div>
              <textarea name="blog_excerpt" class="i_textarea flex_"><?php echo iN_SecureTextareaOutput($excerptVal); ?></textarea>
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_excerpt_help']); ?></div>
            </div>

            <div class="blog-card">
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_content']); ?></div>
              <textarea name="blog_content" id="blog_content_editor"><?php echo htmlspecialchars($contentVal, ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_content_help']); ?></div>
              <div class="warning_wrapper warning_two"><?php echo iN_HelpSecure($LANG['blog_content']); ?></div>
            </div>
          </div>

          <div class="blog-admin-side">
            <div class="blog-card">
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_status']); ?></div>
              <select name="blog_status" class="i_input flex_">
                <option value="draft" <?php echo $statusVal === 'draft' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['blog_draft']); ?></option>
                <option value="published" <?php echo $statusVal === 'published' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['blog_published']); ?></option>
              </select>
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_status_help']); ?></div>
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_featured']); ?></div>
              <select name="blog_featured" class="i_input flex_">
                <option value="0" <?php echo $isFeaturedVal === '0' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['no'] ?? 'No'); ?></option>
                <option value="1" <?php echo $isFeaturedVal === '1' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['yes'] ?? 'Yes'); ?></option>
              </select>
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_featured_help']); ?></div>
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_allow_reactions']); ?></div>
              <select name="blog_allow_reactions" class="i_input flex_">
                <option value="1" <?php echo $allowReactionsVal === '1' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['yes'] ?? 'Yes'); ?></option>
                <option value="0" <?php echo $allowReactionsVal === '0' ? 'selected' : ''; ?>><?php echo iN_HelpSecure($LANG['no'] ?? 'No'); ?></option>
              </select>
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_react_help']); ?></div>
            </div>

            <div class="blog-card">
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_cover_image']); ?></div>
              <input type="file" name="blog_cover_file" accept=".jpg,.jpeg,.png,.gif,.webp" class="i_input flex_">
              <?php if ($isEdit && !empty($blogDetails['cover_upload_id'])) { ?>
                  <div class="box_not"><?php echo iN_HelpSecure($LANG['blog_cover_image']); ?> ID: <?php echo iN_HelpSecure($blogDetails['cover_upload_id']); ?></div>
              <?php } ?>
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_cover_image_url']); ?></div>
              <input type="text" name="blog_cover_url" class="i_input flex_" value="<?php echo iN_HelpSecure($coverUrlVal); ?>" placeholder="https://">
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_cover_help']); ?></div>
              <div class="blog-cover-preview">
                <div class="blog-cover-preview-img" style="background-image:url('<?php echo iN_HelpSecure($coverPreview ?: $base_url . 'img/placeholder.png'); ?>');"></div>
              </div>
            </div>

            <div class="blog-card">
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_meta_title']); ?></div>
              <input type="text" name="blog_meta_title" class="i_input flex_" value="<?php echo iN_HelpSecure($metaTitleVal); ?>">
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_meta_title_help']); ?></div>
              <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['blog_meta_description']); ?></div>
              <textarea name="blog_meta_description" class="i_textarea flex_"><?php echo iN_SecureTextareaOutput($metaDescVal); ?></textarea>
              <div class="blog-hint"><?php echo iN_HelpSecure($LANG['blog_meta_desc_help']); ?></div>
            </div>
          </div>
        </div>

        <div class="admin_approve_post_footer">
          <div class="i_become_creator_box_footer">
            <button type="submit" name="submit" class="i_nex_btn_btn transition" id="save_blog_post">
              <?php echo iN_HelpSecure($LANG['blog_save']); ?>
            </button>
            <div class="warning_wrapper warning_">
              <?php echo iN_HelpSecure($LANG['noway_desc']); ?>
            </div>
            <div class="successNot">
              <?php echo iN_HelpSecure($isEdit ? $LANG['blog_updated_success'] : $LANG['blog_created_success']); ?>
            </div>
          </div>
        </div>
      </form>
      <script type="text/javascript" src="<?php echo iN_HelpSecure($base_url); ?>admin/<?php echo iN_HelpSecure($adminTheme); ?>/js/tinymce/tinymce.min.js"></script>
      <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var coverPreviewEl = document.querySelector('.blog-cover-preview-img');
            var coverUrlInput = document.querySelector('input[name="blog_cover_url"]');
            var coverFileInput = document.querySelector('input[name="blog_cover_file"]');
            var objectUrlCache = null;

            function setCoverPreview(url) {
                if (!coverPreviewEl) { return; }
                coverPreviewEl.style.backgroundImage = url ? "url('" + url + "')" : 'none';
            }

            function revokeObjectUrl() {
                if (objectUrlCache) {
                    URL.revokeObjectURL(objectUrlCache);
                    objectUrlCache = null;
                }
            }

            if (coverUrlInput) {
                coverUrlInput.addEventListener('input', function() {
                    var val = this.value.trim();
                    revokeObjectUrl();
                    if (val.match(/^https?:\/\//i)) {
                        setCoverPreview(val);
                    }
                });
            }

            if (coverFileInput) {
                coverFileInput.addEventListener('change', function(e) {
                    revokeObjectUrl();
                    var file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                    if (!file) { return; }
                    objectUrlCache = URL.createObjectURL(file);
                    setCoverPreview(objectUrlCache);
                });
            }

            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#blog_content_editor',
                    height: 460,
                    menubar: false,
                    convert_urls: false,
                    relative_urls: false,
                    remove_script_host: false,
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | checklist numlist bullist indent outdent | removeformat',
                    images_upload_handler: function (blobInfo, success, failure) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', siteurl + 'request/request.php');
                        xhr.onload = function() {
                            if (xhr.status !== 200) {
                                failure('HTTP Error: ' + xhr.status);
                                return;
                            }
                            var json = {};
                            try { json = JSON.parse(xhr.responseText); } catch (e) {}
                            if (!json || typeof json.location !== 'string') {
                                failure('Invalid response');
                                return;
                            }
                            success(json.location);
                        };
                        var formData = new FormData();
                        formData.append('f', 'uploadBlogImage');
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        var csrf = document.querySelector('input[name=\"csrf_token\"]');
                        if (csrf && csrf.value) {
                            formData.append('csrf_token', csrf.value);
                        }
                        xhr.send(formData);
                    },
                    branding: false
                });
            }
        });
      </script>
    </div>
  </div>
</div>
