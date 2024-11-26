jQuery(document).ready(function ($) {
    function checkAPIHealth() {
        $.ajax({
            url: thinkpixelSettings.ping_url,
            type: 'POST',
            headers: {
                'X-WP-Nonce': thinkpixelSettings.wp_rest_nonce
            },
            success: function (response) {
                console.log(response);
                if (response.status === "ok" && response.version) {
                    $('#thinkpixel-api-status').text('OK');
                    $('#thinkpixel-api-version').text(response.version);
                } else {
                    $('#thinkpixel-api-status').text('Error');
                    $('#thinkpixel-api-version').text('-');
                }
            },
            error: function () {
                $('#thinkpixel-api-status').text('Error');
                $('#thinkpixel-api-version').text('-');
            }
        });
    }

    // Check once on page load
    checkAPIHealth();

    // Check every 20 seconds
    setInterval(checkAPIHealth, 20000);


    $('#thinkpixel-skip-search-btn').on('click', function () {
        let query = $('#thinkpixel-skip-search').val();
        if (query.length < 2) {
            alert('Please enter at least 2 characters.');
            return;
        }

        $.ajax({
            url: thinkpixelSettings.skip_search_url,
            method: 'POST',
            headers: {
                'X-WP-Nonce': thinkpixelSettings.wp_rest_nonce
            },
            dataType: 'json',
            data: {
                query: query
            },
            success: function (response) {
                if (response.success) {
                    let rowsHtml = '';
                    $.each(response.data, function (index, item) {
                        rowsHtml += '<tr>' +
                            '<th class="check-column"><input type="checkbox" name="skip_ids[]" value="' + item.post_id + '"></th>' +
                            '<td>' + item.title + '</td>' +
                            '<td>' + (item.skip_flag ? 'Skipped' : 'Not skipped') + '</td>' +
                            '</tr>';
                    });
                    $('#thinkpixel-skip-results tbody').html(rowsHtml);
                }
            },
            error: function () {
                console.log('Error searching pages/posts');
            }
        });
    });

    // Check/uncheck all
    $('#thinkpixel-skip-check-all').on('click', function () {
        let checked = $(this).prop('checked');
        $('#thinkpixel-skip-results tbody input[type="checkbox"]').prop('checked', checked);
    });

    // Bulk Processing
    const $bulkIndexButton = $('#thinkpixel-bulk-index-btn');
    const $progressBar = $('#thinkpixel-bulk-progress-bar');
    const $statusMessage = $('#thinkpixel-bulk-status-message');
    const $progressContainer = $('#thinkpixel-bulk-progress-container');

    // Hide progress bar until needed
    $progressContainer.hide();

    // Called after each request to update UI (progress bar, status message, button states)
    function updateProgress(processedCount, unprocessedCount) {
        let total = processedCount + unprocessedCount;
        let percentage = total > 0 ? Math.round((processedCount / total) * 100) : 100;

        $progressBar.css('width', percentage + '%');
        $progressBar.text(percentage + '%');
        $statusMessage.text(
            'Processed: ' + processedCount + ' | Remaining: ' + unprocessedCount
        );
    }

    async function doBulkIndexing() {
        try {
            // Disable button during processing
            $bulkIndexButton.prop('disabled', true);

            // Show the progress bar
            $progressContainer.show();

            let keepProcessing = true;

            while (keepProcessing) {
                const response = await fetch(thinkpixelSettings.bulk_post_processing_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': thinkpixelSettings.wp_rest_nonce
                    },
                    body: JSON.stringify({})
                });

                if (!response.ok) {
                    // If the response is not 200-299, handle error
                    throw new Error('Error calling bulk processing API');
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error('Bulk processing API returned an error');
                }

                let processedCount = data.processed_count;
                let unprocessedCount = data.unprocessed_count;

                // Update progress bar
                updateProgress(processedCount, unprocessedCount);

                // If there are still unprocessed items, wait 10s then repeat
                if (unprocessedCount > 0) {
                    // Wait 10 seconds
                    await new Promise((resolve) => setTimeout(resolve, 10000));
                } else {
                    keepProcessing = false;
                }
            }

            // Processing completed
            $statusMessage.append(' - All done!');
        } catch (error) {
            console.error(error);
            $statusMessage.text('An error occurred during bulk indexing: ' + error.message);
        } finally {
            // Re-enable button
            $bulkIndexButton.prop('disabled', false);
        }
    }

    // On button click, start the indexing process
    $bulkIndexButton.on('click', function () {
        doBulkIndexing();
    });
});
