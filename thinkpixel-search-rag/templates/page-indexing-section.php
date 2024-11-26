<?php

namespace ThinkPixel\Core;
?>
<h2><?php esc_html_e('Page Indexing Status', Strings::Domain); ?></h2>
<table class="form-table">
    <tr>
        <th scope="row"><?php esc_html_e('Indexed Pages', Strings::Domain); ?></th>
        <td><?php echo esc_html($processed_count); ?></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Remaining Pages', Strings::Domain); ?></th>
        <td><?php echo esc_html($remaining_count); ?></td>
    </tr>
</table>

<form method="post" action="">
    <?php wp_nonce_field('thinkpixel_bulk_index_action', 'thinkpixel_bulk_index_nonce'); ?>
    <button type="button"
        id="thinkpixel-bulk-index-btn"
        class="button button-primary">
        <?php esc_html_e('Run Bulk Indexing', Strings::Domain); ?>
    </button>
</form>

<!-- Progress bar container -->
<div id="thinkpixel-bulk-progress-container" style="margin-top: 1em; width: 100%; max-width: 400px; border: 1px solid #ccc;">
    <div id="thinkpixel-bulk-progress-bar" style="width: 0; background: #0073aa; color: #fff; text-align: center;">
        0%
    </div>
</div>
<p id="thinkpixel-bulk-status-message" style="margin-top: 0.5em;"></p>
