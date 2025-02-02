jQuery(document).ready(async function ($) {
    // Check the API health
    const $apiStatus = $('#thinkpixel-api-status');
    const $apiVersion = $('#thinkpixel-api-version');

    // Function to check the API health
    async function checkAPIHealth() {
        try {
            const response = await $.ajax({
                url: thinkpixelSettings.ping_url,
                type: 'POST',
                headers: {
                    'X-WP-Nonce': thinkpixelSettings.wp_rest_nonce
                }
            });

            if (response.status === "ok" && response.version) {
                $apiStatus.text('OK');
                $apiVersion.text(response.version);
            } else {
                $apiStatus.text('Error');
                $apiVersion.text('-');
            }
        } catch (error) {
            $apiStatus.text('Error');
            $apiVersion.text('-');
        }
    }

    // Function to periodically check the API health every 20 seconds
    async function checkAPIHealthPeriodically() {
        await checkAPIHealth();
        setTimeout(checkAPIHealthPeriodically, 20000);
    }

    // If the API status and version elements exist, check the API health every 20 seconds
    if ($apiStatus.length && $apiVersion.length) {
        // Check once on page load and then periodically
        await checkAPIHealthPeriodically();
    }

    // Skip Search
    const $skipSearchButton = $('#thinkpixel-skip-search-btn');
    const $skipSearchResetButton = $('#thinkpixel-skip-reset-btn');
    const $skipSearchTextInput = $('#thinkpixel-skip-search');
    const $skipSearchForm = $('thinkpixel-skip-form');
    const $skipSearchTableBody = $('#thinkpixel-skip-results tbody');
    const $skipSearchPagination = $('#thinkpixel-skip-pagination');
    const $skipIdsInput = $('#thinkpixel-skip-ids-field');
    let skippedIdsCache = [];

    // Function to display pagination for skip search results
    function displaySkipSearchPagination(query, data, $pagination) {
        const offset = data.offset;
        const count = data.count;
        const limit = data.limit;

        const totalPages = Math.ceil(count / limit);

        console.log('Total pages: ' + totalPages);

        if (totalPages < 2) {
            $pagination.html('').hide();
            return;
        }

        const currentPage = Math.floor(offset / limit) + 1;

        let linksHtml = '<ul class="pagination" style="float: right;">';

        if (offset > 0) {
            linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + (offset - limit) + '">&laquo;</a></li>';
        }

        if (totalPages <= 9) {
            for (let i = 1; i <= totalPages; i++) {
                linksHtml += '<li class="page-item' + (i === currentPage ? ' active' : '') + '"><a class="page-link" href="#" data-offset="' + ((i - 1) * limit) + '">' + i + '</a></li>';
            }
        } else {
            if (currentPage <= 3) {
                for (let i = 1; i <= 5; i++) {
                    linksHtml += '<li class="page-item' + (i === currentPage ? ' active' : '') + '"><a class="page-link" href="#" data-offset="' + ((i - 1) * limit) + '">' + i + '</a></li>';
                }
                linksHtml += '<li class="page-item"><span class="page-link">...</span></li>';
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + ((totalPages - 2) * limit) + '">' + (totalPages - 1) + '</a></li>';
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + ((totalPages - 1) * limit) + '">' + totalPages + '</a></li>';
            } else if (currentPage > totalPages - 3) {
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="0">1</a></li>';
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + limit + '">2</a></li>';
                linksHtml += '<li class="page-item"><span class="page-link">...</span></li>';
                for (let i = totalPages - 4; i <= totalPages; i++) {
                    linksHtml += '<li class="page-item' + (i === currentPage ? ' active' : '') + '"><a class="page-link" href="#" data-offset="' + ((i - 1) * limit) + '">' + i + '</a></li>';
                }
            } else {
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="0">1</a></li>';
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + limit + '">2</a></li>';
                linksHtml += '<li class="page-item"><span class="page-link">...</span></li>';
                for (let i = currentPage - 2; i <= currentPage + 2; i++) {
                    linksHtml += '<li class="page-item' + (i === currentPage ? ' active' : '') + '"><a class="page-link" href="#" data-offset="' + ((i - 1) * limit) + '">' + i + '</a></li>';
                }
                linksHtml += '<li class="page-item"><span class="page-link">...</span></li>';
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + ((totalPages - 2) * limit) + '">' + (totalPages - 1) + '</a></li>';
                linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + ((totalPages - 1) * limit) + '">' + totalPages + '</a></li>';
            }
        }

        if (offset + limit < count) {
            linksHtml += '<li class="page-item"><a class="page-link" href="#" data-offset="' + (offset + limit) + '">&raquo;</a></li>';
        }

        linksHtml += '</ul>';

        $pagination
            .html(linksHtml)
            .show()
            // Pagination links click event
            .find('a').on('click', async function (e) {
                e.preventDefault();
                const offset = $(this).data('offset');
                updateSkippedIdsCache();
                await loadSkipSearch(query, offset);
            });
    }

    // Function to display skip search items in the table
    function displaySkipSearchItems(data, $tableBody) {
        const posts = data.results;

        let rowsHtml = '';
        $.each(posts, function (index, item) {
            const postId = item.ID;
            const title = item.post_title;
            const skipFlag = item.skip_flag;
            const processedFlag = item.processed_flag;
            const lastUpdated = item.last_updated;

            rowsHtml += '<tr>' +
                '<th class="check-column"><input type="checkbox" value="' + postId + '"' + (skipFlag ? ' checked' : '') + '></th>' +
                '<td>' + title + '</td>' +
                '<td><input type="checkbox" readonly disabled' + (processedFlag ? ' checked' : '') + '></td>' +
                '<td>' + lastUpdated + '</td>' +
                '</tr>';
        });
        $tableBody.html(rowsHtml);
    }

    // Function to load skip search results
    async function loadSkipSearch(query, offset) {
        try {
            const response = await $.ajax({
                url: thinkpixelSettings.skip_search_url,
                method: 'POST',
                headers: {
                    'X-WP-Nonce': thinkpixelSettings.wp_rest_nonce
                },
                dataType: 'json',
                data: {
                    query: query,
                    offset: offset
                }
            });

            if (response.success) {
                displaySkipSearchItems(response.data, $skipSearchTableBody);
                displaySkipSearchPagination(query, response.data, $skipSearchPagination);
            }
        } catch (error) {
            console.log('Error searching pages/posts');
        }
    }

    // Function to update the cache of skipped IDs
    function updateSkippedIdsCache() {
        skippedIdsCache = [];
        $skipSearchTableBody.find('input[name="skip_ids[]"]:checked').each(function () {
            skippedIdsCache.push($(this).val());
        });
        $skipSearchTableBody.find('input[name="skip_ids[]"]:not(:checked)').each(function () {
            const postId = $(this).val();
            const index = skippedIdsCache.indexOf(postId);
            if (index > -1) {
                skippedIdsCache.splice(index, 1);
            }
        });
        $skipIdsInput.val(JSON.stringify(skippedIdsCache));
    }

    // Debounce function to limit the rate at which a function can fire.
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Search button click event
    $skipSearchButton.on('click', debounce(async function () {
        const query = $('#thinkpixel-skip-search').val();
        if (query.length < 2) {
            alert('Please enter at least 2 characters.');
            return;
        }
        skippedIdsCache = [];
        await loadSkipSearch(query, 0);
    }, 300));

    // Search input keyup event
    $skipSearchTextInput.on('keyup', debounce(async function () {
        const query = $(this).val();
        if (query.length < 2) {
            return;
        }
        skippedIdsCache = [];
        await loadSkipSearch(query, 0);
    }, 300));

    // Load initial data
    if ($skipSearchTableBody.length && $skipSearchPagination.length) {
        skippedIdsCache = [];
        await loadSkipSearch('', 0);
    }

    // Reset button click event
    $skipSearchResetButton.on('click', async function () {
        skippedIdsCache = [];
        await loadSkipSearch('', 0);
    });

    // Update cache on form submit
    $skipSearchForm.on('submit', function (e) {
        updateSkippedIdsCache();
    });

    // Check/uncheck all
    $('#thinkpixel-skip-check-all').on('click', function () {
        const checked = $(this).prop('checked');
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
        const total = processedCount + unprocessedCount;
        const percentage = total > 0 ? Math.round((processedCount / total) * 100) : 100;

        $progressBar.css('width', percentage + '%');
        $progressBar.text(percentage + '%');
        $statusMessage.text(
            'Processed: ' + processedCount + ' | Remaining: ' + unprocessedCount
        );
    }

    // Function to perform bulk indexing
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

                const processedCount = data.processed_count;
                const unprocessedCount = data.unprocessed_count;

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
    $bulkIndexButton.on('click', doBulkIndexing);
});
