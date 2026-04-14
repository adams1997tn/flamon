<div class="stickersContainer">
    <div class="stickers_wrapper">
        <?php
        $giphyEnabled = (string)($giphyStatus ?? '1') === '1';
        $searchQuery = isset($giphyQuery) ? trim((string)$giphyQuery) : '';
        $isSearchMode = $searchQuery !== '';
        $searchPlaceholder = $LANG['giphy_search_placeholder'] ?? (($LANG['search'] ?? 'Search') . ' GIFs');
        $searchEmptyText = $isSearchMode
            ? ($LANG['no_gifs_found_for_search'] ?? 'No GIFs found for your search.')
            : ($LANG['no_trending_gifs'] ?? 'No trending GIFs found.');
        ?>
        <div class="giphy_search_bar" style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
            <div class="giphy_search_form" data-request-type="gifList" data-id="<?php echo iN_HelpSecure($id); ?>" style="display:flex;gap:8px;width:100%;" role="search" aria-label="Giphy search">
                <input
                    type="search"
                    name="giphy_query"
                    class="giphy_search_input"
                    value="<?php echo iN_HelpSecure($searchQuery); ?>"
                    placeholder="<?php echo iN_HelpSecure($searchPlaceholder); ?>"
                    style="flex:1;min-width:0;"
                    autocomplete="off"
                    spellcheck="false"
                    oninput="return window.dizzyHandleGiphyInput ? window.dizzyHandleGiphyInput(this) : true;"
                    onkeydown="return window.dizzyHandleGiphyKeydown ? window.dizzyHandleGiphyKeydown(this, event) : true;"
                    onsearch="return window.dizzyHandleGiphyInput ? window.dizzyHandleGiphyInput(this) : true;"
                >
                <button type="button" class="giphy_search_btn" onclick="return window.dizzyRunGiphySearch ? window.dizzyRunGiphySearch(this, true) : false;"><?php echo iN_HelpSecure($LANG['search'] ?? 'Search'); ?></button>
            </div>
        </div>
        <div class="giphy_results_container">
            <?php
            if (!$giphyEnabled) {
                echo '<div class="no_gif_found">' . iN_HelpSecure($LANG['giphy_disabled']) . '</div>';
            } elseif (!isset($giphyKey) || empty($giphyKey)) {
                echo '<div class="no_gif_found">' . iN_HelpSecure($LANG['api_key_missing']) . '</div>';
            } else {
                $encodedKey = urlencode($giphyKey);
                if ($isSearchMode) {
                    $apiUrl = "https://api.giphy.com/v1/gifs/search?api_key=" . $encodedKey . "&limit=25&rating=pg&q=" . urlencode($searchQuery);
                } else {
                    $apiUrl = "https://api.giphy.com/v1/gifs/trending?api_key=" . $encodedKey . "&limit=25&rating=pg";
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $response = curl_exec($ch);

                if (curl_errno($ch) || !$response) {
                    echo '<div class="no_gif_found">' . iN_HelpSecure($LANG['could_not_retrieve_gifs']) . '</div>';
                } else {
                    $json = json_decode($response);
                    if (!isset($json->data) || !is_array($json->data) || count($json->data) === 0) {
                        echo '<div class="no_gif_found">' . iN_HelpSecure($searchEmptyText) . '</div>';
                    } else {
                        foreach ($json->data as $gif) {
                            $giphyImageUrl = $gif->images->fixed_height->url ?? '';
                            if ($giphyImageUrl) {
                                echo '<img class="rGif transition" data-id="' . iN_HelpSecure($id) . '" src="' . iN_HelpSecure($giphyImageUrl) . '">';
                            }
                        }
                    }
                }
                curl_close($ch);
            }
            ?>
        </div>
    </div> 
</div>
