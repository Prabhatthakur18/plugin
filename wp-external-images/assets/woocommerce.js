jQuery(document).ready(function($) {
    var galleryIndex = $('.external-gallery-item').length;
    
    // Toggle section visibility
    $('.external-toggle-btn').on('click', function() {
        var $btn = $(this);
        var target = $btn.data('target');
        var $content = $('#' + target);
        
        $content.toggleClass('collapsed');
        $btn.toggleClass('collapsed');
    });
    
    // Preview external featured image
    $('.preview-external-featured').on('click', function() {
        var url = $('#external-featured-url').val().trim();
        if (!url) {
            showNotice('Please enter an image URL', 'error');
            return;
        }
        
        previewImage(url, function(success, data) {
            if (success) {
                updateFeaturedPreview(url);
                $('#external-featured-url').addClass('success').removeClass('error');
            } else {
                $('#external-featured-url').addClass('error').removeClass('success');
                showNotice(data, 'error');
            }
        });
    });
    
    // Set external featured image
    $('.set-external-featured').on('click', function() {
        var url = $('#external-featured-url').val().trim();
        if (!url) {
            showNotice('Please enter an image URL', 'error');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Setting...');
        
        previewImage(url, function(success, data) {
            if (success) {
                updateFeaturedPreview(url);
                updateFeaturedData(url);
                $('#external-featured-url').addClass('success').removeClass('error');
                showNotice('Featured image set successfully!', 'success');
            } else {
                $('#external-featured-url').addClass('error').removeClass('success');
                showNotice(data, 'error');
            }
            $btn.prop('disabled', false).text('Set Featured Image');
        });
    });
    
    // Remove external featured image
    $(document).on('click', '.remove-external-featured', function() {
        $('.external-featured-preview').html(getPlaceholderHTML('No external featured image set'));
        $('#external-featured-url').val('').removeClass('success error');
        updateFeaturedData('');
        showNotice('Featured image removed', 'success');
    });
    
    // Preview external gallery image
    $('.preview-external-gallery').on('click', function() {
        var url = $('#external-gallery-url').val().trim();
        if (!url) {
            showNotice('Please enter an image URL', 'error');
            return;
        }
        
        previewImage(url, function(success, data) {
            if (success) {
                $('#external-gallery-url').addClass('success').removeClass('error');
                showNotice('Image URL is valid!', 'success');
            } else {
                $('#external-gallery-url').addClass('error').removeClass('success');
                showNotice(data, 'error');
            }
        });
    });
    
    // Add external gallery image
    $('.add-external-gallery').on('click', function() {
        var url = $('#external-gallery-url').val().trim();
        if (!url) {
            showNotice('Please enter an image URL', 'error');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Adding...');
        
        previewImage(url, function(success, data) {
            if (success) {
                addGalleryImage(url);
                $('#external-gallery-url').val('').removeClass('success error');
                showNotice('Image added to gallery!', 'success');
            } else {
                $('#external-gallery-url').addClass('error').removeClass('success');
                showNotice(data, 'error');
            }
            $btn.prop('disabled', false).text('Add to Gallery');
        });
    });
    
    // Remove gallery image
    $(document).on('click', '.remove-external-gallery-item', function() {
        $(this).closest('.external-gallery-item').fadeOut(300, function() {
            $(this).remove();
            updateGalleryIndices();
        });
        showNotice('Image removed from gallery', 'success');
    });
    
    // URL input validation on blur
    $('.external-url-input').on('blur', function() {
        var $input = $(this);
        var url = $input.val().trim();
        
        if (url && !isValidUrl(url)) {
            $input.addClass('error').removeClass('success');
        } else if (url) {
            $input.removeClass('error success');
        }
    });
    
    // Real-time URL validation
    $('.external-url-input').on('input', function() {
        var $input = $(this);
        var url = $input.val().trim();
        
        if (url && isValidUrl(url)) {
            $input.removeClass('error');
        } else if (url) {
            $input.addClass('error');
        } else {
            $input.removeClass('error success');
        }
    });
    
    // Helper Functions
    function previewImage(url, callback) {
        $.ajax({
            url: wpExternalImages.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preview_external_image',
                url: url,
                nonce: wpExternalImages.nonce
            },
            success: function(response) {
                if (response.success) {
                    callback(true, response.data);
                } else {
                    callback(false, response.data);
                }
            },
            error: function() {
                callback(false, 'Failed to validate image URL');
            }
        });
    }
    
    function updateFeaturedPreview(url) {
        var previewHTML = '<div class="external-image-preview">' +
            '<img src="' + url + '" alt="Featured Image Preview">' +
            '<div class="external-image-overlay">' +
                '<button type="button" class="button button-secondary remove-external-featured">' +
                    'Remove Image' +
                '</button>' +
            '</div>' +
        '</div>';
        
        $('.external-featured-preview').html(previewHTML);
    }
    
    function updateFeaturedData(url) {
        var data = url ? { url: url, alt: '' } : '';
        $('#external-featured-data').val(JSON.stringify(data));
    }
    
    function addGalleryImage(url) {
        var imageHTML = '<div class="external-gallery-item" data-index="' + galleryIndex + '">' +
            '<div class="external-gallery-preview">' +
                '<img src="' + url + '" alt="Gallery Image">' +
                '<div class="external-gallery-overlay">' +
                    '<button type="button" class="button remove-external-gallery-item">' +
                        '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<input type="hidden" name="external_gallery_images[' + galleryIndex + '][url]" value="' + url + '">' +
            '<input type="hidden" name="external_gallery_images[' + galleryIndex + '][alt]" value="">' +
        '</div>';
        
        $('#external-gallery-container').append(imageHTML);
        galleryIndex++;
    }
    
    function updateGalleryIndices() {
        $('#external-gallery-container .external-gallery-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input[type="hidden"]').each(function() {
                var name = $(this).attr('name');
                var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                $(this).attr('name', newName);
            });
        });
        galleryIndex = $('#external-gallery-container .external-gallery-item').length;
    }
    
    function getPlaceholderHTML(text) {
        return '<div class="external-image-placeholder">' +
            '<div class="external-placeholder-content">' +
                '<span class="dashicons dashicons-format-image"></span>' +
                '<p>' + text + '</p>' +
            '</div>' +
        '</div>';
    }
    
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    function showNotice(message, type) {
        // Remove existing notices
        $('.external-notice').remove();
        
        var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        var notice = '<div class="notice ' + noticeClass + ' is-dismissible external-notice" style="margin: 10px 0;">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
            '</button>' +
        '</div>';
        
        $('.external-images-container').prepend(notice);
        
        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            $('.external-notice').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Handle notice dismiss
    $(document).on('click', '.external-notice .notice-dismiss', function() {
        $(this).closest('.external-notice').fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    // Initialize collapsed state
    $('.external-section-content').removeClass('collapsed');
    $('.external-toggle-btn').removeClass('collapsed');
});