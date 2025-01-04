jQuery(document).ready(function($) {
    // Check URL status using HEAD request
    async function checkUrl(url) {
        try {
            const response = await fetch(url, {
                method: 'HEAD',
                mode: 'no-cors',
                cache: 'no-cache',
                redirect: 'follow',
                referrerPolicy: 'no-referrer'
            });
            return true;
        } catch (error) {
            return false;
        }
    }

    // Process URLs in batches
    async function processBatch(urls, batchSize = 5) {
        const results = [];
        for (let i = 0; i < urls.length; i += batchSize) {
            const batch = urls.slice(i, i + batchSize);
            const promises = batch.map(async (url, index) => {
                const position = i + index;
                const row = `<tr>
                    <td>${position + 1}</td>
                    <td><a href="${url}" target="_blank">${url}</a></td>
                    <td><span class="checking">Checking...</span></td>
                </tr>`;
                $('#fbg-results-table tbody').append(row);

                const isWorking = await checkUrl(url);
                const statusCell = $(`#fbg-results-table tr:eq(${position + 1}) td:last`);
                if (isWorking) {
                    statusCell.html('<span class="success">✓</span>');
                } else {
                    statusCell.html('<span class="error">✗</span>');
                }
                return { url, isWorking };
            });

            const batchResults = await Promise.all(promises);
            results.push(...batchResults);
            
            if (i + batchSize < urls.length) {
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        return results;
    }

    $('#fbg-form').on('submit', function(e) {
        e.preventDefault();
        var website_url = $('#fbg-url').val().replace(/\/+$/, '');

        $.ajax({
            url: fbg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'fbg_generate_urls',
                website_url: website_url,
                nonce: fbg_ajax.nonce
            },
            beforeSend: function() {
                $('#fbg-results').html('<div class="fbg-loading">Generating URLs...</div>');
            },
            success: async function(response) {
                if (response.success) {
                    var html = `
                        <table id="fbg-results-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Generated URL</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>`;
                    $('#fbg-results').html(html);

                    var validUrls = response.data.filter(url => /^https?:\/\/[^\s]+$/.test(url));
                    await processBatch(validUrls);

                    var linksText = validUrls.join('\n');
                    $('#fbg-results').append(`
                        <div class="fbg-links-section">
                            <textarea id="fbg-links-textarea" readonly>${linksText}</textarea>
                            <button id="fbg-copy-button">Copy All Links</button>
                        </div>`
                    );
                } else {
                    $('#fbg-results').html('<p class="fbg-error">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#fbg-results').html('<p class="fbg-error">An error occurred. Please try again.</p>');
            }
        });
    });

    // Copy functionality
    $(document).on('click', '#fbg-copy-button', function() {
        var textarea = document.getElementById('fbg-links-textarea');
        textarea.select();
        document.execCommand('copy');
        $(this).text('Copied!').prop('disabled', true);
        setTimeout(() => {
            $('#fbg-copy-button').text('Copy All Links').prop('disabled', false);
        }, 2000);
    });
});