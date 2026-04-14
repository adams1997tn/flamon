<div class="i_contents_container">
    <div class="i_general_white_board border_one column flex_ tabing__justify">
        <div class="i_general_title_box">
            <?php echo iN_HelpSecure($LANG['live_streaming_settings']); ?>
        </div>
        <div class="i_general_row_box column flex_" id="general_conf">
            <form enctype="multipart/form-data" method="post" id="liveSettings">
                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="sstat">
                            <input type="checkbox" name="s3Status" class="sstat" id="sstat" <?php echo iN_HelpSecure($agoraStatus) == '1' ? 'value="1" checked="checked"' : 'value="0"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_s_status']); ?></div>
                        <input type="hidden" name="s3Status" id="stats3" value="<?php echo iN_HelpSecure($agoraStatus); ?>">
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['live_s_not']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="sfstat">
                            <input type="checkbox" name="sPlStatus" class="sfstat" id="sfstat" <?php echo iN_HelpSecure($freeLiveStreamingStatus) == '1' ? 'value="1" checked="checked"' : 'value="0"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['free_live_streaming_status']); ?></div>
                        <input type="hidden" name="sPlStatus" id="sftats3" value="<?php echo iN_HelpSecure($freeLiveStreamingStatus); ?>">
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['free_live_streaming_status_not']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="spstat">
                            <input type="checkbox" name="sflStatus" class="spstat" id="spstat" <?php echo iN_HelpSecure($paidLiveStreamingStatus) == '1' ? 'value="1" checked="checked"' : 'value="0"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['paid_live_streaming_status']); ?></div>
                        <input type="hidden" name="sflStatus" id="sptats3" value="<?php echo iN_HelpSecure($paidLiveStreamingStatus); ?>">
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['paid_live_streaming_status_not']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="lpstat">
                            <input type="checkbox" name="livePollStatus" class="lpstat" id="lpstat" <?php echo iN_HelpSecure($livePollStatus ?? '1') == '1' ? 'value="1" checked="checked"' : 'value="0"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_poll_feature_status']); ?></div>
                        <input type="hidden" name="livePollStatus" id="livePollStatusValue" value="<?php echo iN_HelpSecure($livePollStatus ?? '1'); ?>">
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['live_poll_feature_status_not']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="lgstat">
                            <input type="checkbox" name="liveGiftStatus" class="lgstat" id="lgstat" <?php echo iN_HelpSecure($liveGiftStatus ?? '1') == '1' ? 'value="1" checked="checked"' : 'value="0"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_gift_feature_status']); ?></div>
                        <input type="hidden" name="liveGiftStatus" id="liveGiftStatusValue" value="<?php echo iN_HelpSecure($liveGiftStatus ?? '1'); ?>">
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['live_gift_feature_status_not']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="lqstat">
                            <input type="checkbox" name="liveQAStatus" class="lqstat" id="lqstat" <?php echo iN_HelpSecure($liveQAStatus ?? '1') == '1' ? 'value="1" checked="checked"' : 'value="0"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_qa_feature_status']); ?></div>
                        <input type="hidden" name="liveQAStatus" id="liveQAStatusValue" value="<?php echo iN_HelpSecure($liveQAStatus ?? '1'); ?>">
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['live_qa_feature_status_not']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ column tabing__justify">
                    <div class="i_checkbox_wrapper flex_ tabing_non_justify">
                        <label class="el-switch el-switch-yellow" for="lcstat">
                            <input type="checkbox" name="liveChatStatus" class="lcstat" id="lcstat" <?php echo iN_HelpSecure($liveChatStatus ?? '1') == '1' ? 'value="1" checked="checked"' : 'value="0"'; ?>>
                            <span class="el-switch-style"></span>
                        </label>
                        <div class="i_chck_text"><?php echo iN_HelpSecure($LANG['live_chat_feature_status']); ?></div>
                        <input type="hidden" name="liveChatStatus" id="liveChatStatusValue" value="<?php echo iN_HelpSecure($liveChatStatus ?? '1'); ?>">
                    </div>
                    <div class="rec_not box_not_padding_left"><?php echo iN_HelpSecure($LANG['live_chat_feature_status_not']); ?></div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['free_live_stream_time']); ?></div>
                    <div class="irow_box_right">
                        <div class="i_box_limit flex_ column">
                            <div class="i_limit" data-type="cp_limit">
                                <span class="lppt"><?php echo iN_HelpSecure($freeLiveTime); ?></span>
                                <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
                            </div>
                            <div class="i_limit_list_cp_container">
                                <div class="i_countries_list border_one column flex_">
                                    <?php foreach ($LIVETIMELIMIT as $cpLimit) { ?>
                                        <div class="i_s_limit transition border_one gsearch <?php echo iN_HelpSecure($freeLiveTime) == iN_HelpSecure($cpLimit) ? 'choosed' : ''; ?>" id="<?php echo iN_HelpSecure($cpLimit); ?>" data-c="<?php echo iN_HelpSecure($cpLimit); ?>" data-type="postLimit">
                                            <?php echo iN_HelpSecure($cpLimit); ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="post_show_limit" id="uppLimit" value="<?php echo iN_HelpSecure($freeLiveTime); ?>">
                            </div>
                            <div class="rec_not box_not_padding_top"><?php echo iN_HelpSecure($LANG['not_for_time']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['live_stream_price_point']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="liveMinPrice" class="i_input flex_" value="<?php echo iN_HelpSecure($minimumLiveStreamingFee); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['live_provider'] ?? 'Live Provider'); ?></div>
                    <div class="irow_box_right">
                        <select name="rt_provider" id="rt_provider_select" class="i_input flex_">
                            <option value="agora" <?php echo $rtProvider === 'agora' ? 'selected' : ''; ?>>Agora</option>
                            <option value="livekit" <?php echo $rtProvider === 'livekit' ? 'selected' : ''; ?>>LiveKit</option>
                        </select>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify livekit-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['livekit_api_key'] ?? 'LiveKit API Key'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="livekit_api_key" class="i_input flex_" value="<?php echo iN_HelpSecure($livekitAPIKey); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify livekit-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['livekit_api_secret'] ?? 'LiveKit API Secret'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="livekit_api_secret" class="i_input flex_" value="<?php echo iN_HelpSecure($livekitAPISecret); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify livekit-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['livekit_ws_url'] ?? 'LiveKit WebSocket URL'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="livekit_ws_url" class="i_input flex_" value="<?php echo iN_HelpSecure($livekitWSUrl); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify agora-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['agora_app_id']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="appID" class="i_input flex_" value="<?php echo iN_HelpSecure($agoraAppID); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify agora-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['agora_certificate']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="appCertificate" class="i_input flex_" value="<?php echo iN_HelpSecure($agoraCertificate); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify agora-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['agora_customer_id']); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="appCustomerID" class="i_input flex_" value="<?php echo iN_HelpSecure($agoraCustomerID); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['call_provider'] ?? 'Call Provider'); ?></div>
                    <div class="irow_box_right">
                        <select name="call_provider" id="call_provider_select" class="i_input flex_">
                            <option value="agora" <?php echo ($callProvider ?? 'agora') === 'agora' ? 'selected' : ''; ?>>Agora</option>
                            <option value="livekit" <?php echo ($callProvider ?? 'agora') === 'livekit' ? 'selected' : ''; ?>>LiveKit</option>
                            <option value="isometrik" <?php echo ($callProvider ?? 'agora') === 'isometrik' ? 'selected' : ''; ?>>Isometrik</option>
                        </select>
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify isometrik-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['isometrik_api_key'] ?? 'Isometrik API Key'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="isometrik_api_key" class="i_input flex_" value="<?php echo iN_HelpSecure($isometrikAPIKey ?? ''); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify isometrik-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['isometrik_api_secret'] ?? 'Isometrik API Secret'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="isometrik_api_secret" class="i_input flex_" value="<?php echo iN_HelpSecure($isometrikAPISecret ?? ''); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify isometrik-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['isometrik_project_id'] ?? 'Isometrik Project ID'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="isometrik_project_id" class="i_input flex_" value="<?php echo iN_HelpSecure($isometrikProjectId ?? ''); ?>">
                    </div>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify isometrik-fields">
                    <div class="irow_box_left tabing flex_"><?php echo iN_HelpSecure($LANG['isometrik_ws_url'] ?? 'Isometrik WebSocket URL'); ?></div>
                    <div class="irow_box_right">
                        <input type="text" name="isometrik_ws_url" class="i_input flex_" value="<?php echo iN_HelpSecure($isometrikWSUrl ?? ''); ?>">
                    </div>
                </div>

                <div class="i_settings_wrapper_item successNot">
                    <?php echo iN_HelpSecure($LANG['updated_successfully']); ?>
                </div>

                <div class="i_general_row_box_item flex_ tabing_non_justify">
                    <input type="hidden" name="f" value="updateLiveSettings">
                    <button type="submit" name="submit" class="i_nex_btn_btn transition" id="updateGeneralSettings">
                        <?php echo iN_HelpSecure($LANG['save_edit']); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
  (function() {
    const providerSelect = document.getElementById("rt_provider_select");
    const livekitFields = document.querySelectorAll(".livekit-fields");
    const agoraFields = document.querySelectorAll(".agora-fields");
    const callProviderSelect = document.getElementById("call_provider_select");
    const isometrikFields = document.querySelectorAll(".isometrik-fields");

    function toggleProviderFields() {
      const provider = providerSelect ? providerSelect.value : "agora";
      livekitFields.forEach(el => { el.style.display = provider === "livekit" ? "flex" : "none"; });
      agoraFields.forEach(el => { el.style.display = provider === "livekit" ? "none" : "flex"; });
    }

    function toggleCallProviderFields() {
      const provider = callProviderSelect ? callProviderSelect.value : "agora";
      isometrikFields.forEach(el => { el.style.display = provider === "isometrik" ? "flex" : "none"; });
    }

    if (providerSelect) {
      providerSelect.addEventListener("change", toggleProviderFields);
      toggleProviderFields();
    }
    if (callProviderSelect) {
      callProviderSelect.addEventListener("change", toggleCallProviderFields);
      toggleCallProviderFields();
    }
  })();
</script>
