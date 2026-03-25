<?php

namespace SearchPixel\Core;
?>
<h2><?php esc_html_e('Page Indexing Status', Strings::Domain); ?></h2>
<table class="form-table">
    <tr>
        <th scope="row"><?php esc_html_e('Indexed Pages', Strings::Domain); ?></th>
        <td id="searchpixel-processed-count"><?php echo esc_html($processed_count); ?></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Remaining Pages', Strings::Domain); ?></th>
        <td id="searchpixel-remaining-count"><?php echo esc_html($remaining_count); ?></td>
    </tr>
</table>

<button type="button"
    id="searchpixel-bulk-index-btn"
    class="button button-primary">
    <?php esc_html_e('Run Bulk Indexing', Strings::Domain); ?>
</button>

<!-- Progress bar container -->
<div id="searchpixel-bulk-progress-container" style="margin-top: 1em; width: 100%; max-width: 400px; border: 1px solid #ccc; display: none;">
    <div id="searchpixel-bulk-progress-bar" style="width: 0; background: #0073aa; color: #fff; text-align: center;">
        0%
    </div>
</div>
<p id="searchpixel-bulk-status-message" style="margin-top: 0.5em;"></p>
