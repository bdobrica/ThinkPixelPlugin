<?php

namespace SearchPixel\Core;
?>
<h2><?php esc_html_e('Skip Pages and Posts', Strings::Domain); ?></h2>
<p><?php esc_html_e('Search for pages and posts to skip from indexing.', Strings::Domain); ?></p>

<div>
    <input type="text" id="searchpixel-skip-search" placeholder="<?php esc_attr_e('Enter page/post title...', Strings::Domain); ?>" />
    <button id="searchpixel-skip-search-btn" class="button"><?php esc_html_e('Search', Strings::Domain); ?></button>
    <button id="searchpixel-skip-reset-btn" class="button"><?php esc_html_e('Reset', Strings::Domain); ?></button>
</div>

<form id="searchpixel-skip-form" method="post">
    <?php wp_nonce_field(Strings::SkipItemsAction, Strings::SkipItemsNonce); ?>
    <input type="hidden" name="skip_ids" id="searchpixel-skip-ids-field" value="" />
    <table class="widefat" id="searchpixel-skip-results" style="margin-top: 1em;">
        <thead>
            <tr>
                <th class="check-column"><input type="checkbox" id="searchpixel-skip-check-all"></th>
                <th><?php esc_html_e('Title', Strings::Domain); ?></th>
                <th><?php esc_html_e('Indexed', Strings::Domain); ?></th>
                <th><?php esc_html_e('Updated At', Strings::Domain); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be appended here via AJAX -->
        </tbody>
    </table>
    <div id="searchpixel-skip-pagination" class="tablenav"></div>
    <button type="submit" name="searchpixel_skip_selected_items" id="searchpixel-skip-submit" class="button button-primary" style="margin-top: 1em;">
        <?php esc_html_e('Skip Selected Pages', Strings::Domain); ?>
    </button>
</form>
