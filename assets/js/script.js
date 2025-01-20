jQuery(document).ready(function($) {
    const form = $('#iover-upload-form');
    const responseDiv = $('#iover-response');
    const responseContent = $('.iover-response-content');

    form.on('submit', function(e) {
        e.preventDefault();

        // Show loading state
        responseDiv.show();
        responseContent.html('<div class="iover-loading"></div>');

        const formData = new FormData(this);
        formData.append('action', 'iover_process_upload');
        formData.append('nonce', ioverAjax.nonce);

        $.ajax({
            url: ioverAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Create table for Q&A pairs
                    const table = $('<table class="iover-qa-table"></table>');
                    const thead = $('<thead><tr><th>Question</th><th>Answer</th></tr></thead>');
                    const tbody = $('<tbody></tbody>');

                    response.data.qa_pairs.forEach(function(pair) {
                        const row = $('<tr></tr>');
                        row.append($('<td></td>').text(pair.question));
                        row.append($('<td></td>').text(pair.answer));
                        tbody.append(row);
                    });

                    table.append(thead).append(tbody);
                    responseContent.html(table);
                } else {
                    responseContent.html(
                        $('<div class="iover-error"></div>').text(response.data.message)
                    );
                }
            },
            error: function(xhr, status, error) {
                responseContent.html(
                    $('<div class="iover-error"></div>').text('Error: ' + error)
                );
            }
        });
    });

    // Preview image on selection
    $('#iover-image').on('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $('<img>').attr({
                    src: e.target.result,
                    alt: 'Preview',
                    style: 'max-width: 100%; height: auto; margin-top: 10px;'
                });
                
                // Remove any existing preview
                $('.iover-image-preview').remove();
                
                // Add new preview
                $('<div class="iover-image-preview"></div>')
                    .append(preview)
                    .insertAfter('#iover-image');
            };
            reader.readAsDataURL(file);
        }
    });
});
