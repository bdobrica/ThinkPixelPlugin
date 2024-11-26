<?php

namespace ThinkPixel\Core;
?>
<h2><?php esc_html_e('API Health Status', Strings::Domain); ?></h2>

<div id="thinkpixel-api-health">
    <p><strong><?php esc_html_e('Status:', Strings::Domain); ?></strong>
        <span id="thinkpixel-api-status"><?php esc_html_e('Checking ThinkPixel API availability...', Strings::Domain); ?></span>
    </p>
    <p><strong><?php esc_html_e('Version:', Strings::Domain); ?></strong>
        <span id="thinkpixel-api-version"><?php esc_html_e('Unknown', Strings::Domain); ?></span>
    </p>
</div>
