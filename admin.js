jQuery(document).ready(function($) {
    $('#fbg-test-urls').on('click', function() {
        $.ajax({
            url: fbg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'fbg_test_urls'
            },
            success: function(response) {
                if (response.success) {
                    var html = '<ul>';
                    response.data.forEach(function(result) {
                        html += '<li>' + result + '</li>';
                    });
                    html += '</ul>';
                    $('#fbg-test-results').html(html);
                }
            }
        });
    });
});