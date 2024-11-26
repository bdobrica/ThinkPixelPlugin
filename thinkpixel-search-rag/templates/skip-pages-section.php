<?php

namespace ThinkPixel\Core;
?>
<h2><?php esc_html_e('Skip Pages', Strings::Domain); ?></h2>
<p><?php esc_html_e('Search for pages and posts to skip from indexing.', Strings::Domain); ?></p>

<div>
    <input type="text" id="thinkpixel-skip-search" placeholder="<?php esc_attr_e('Enter page/post title...', Strings::Domain); ?>" />
    <button id="thinkpixel-skip-search-btn" class="button"><?php esc_html_e('Search', Strings::Domain); ?></button>
</div>

<form id="thinkpixel-skip-form" method="post">
    <?php wp_nonce_field('thinkpixel_skip_pages_action', 'thinkpixel_skip_pages_nonce'); ?>
    <table class="widefat" id="thinkpixel-skip-results" style="margin-top: 1em;">
        <thead>
            <tr>
                <th class="check-column"><input type="checkbox" id="thinkpixel-skip-check-all"></th>
                <th><?php esc_html_e('Title', Strings::Domain); ?></th>
                <th><?php esc_html_e('Skip Flag', Strings::Domain); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be appended here via AJAX -->
        </tbody>
    </table>
    <button type="submit" id="thinkpixel-skip-submit" class="button button-primary" style="margin-top: 1em;">
        <?php esc_html_e('Skip/Unskip Selected Pages', Strings::Domain); ?>
    </button>
</form>
