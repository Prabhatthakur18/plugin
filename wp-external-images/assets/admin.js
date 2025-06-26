jQuery(document).ready(function($) {
    // Add external image button to media uploader
    if (typeof wp !== 'undefined' && wp.media) {
        // Extend the media library
        var ExternalImageState = wp.media.controller.State.extend({
            initialize: function() {
                this.set('id', 'external-image');
                this.set('title', 'Add External Image');
                this.set('content', 'external-image');
                this.set('menu', 'default');
                this.set('menuItem', {
                    text: 'External Image',
                    priority: 60
                });
            }
        });

        // Add to media modal
        wp.media.view.MediaFrame.Select.prototype.initialize = function() {
            wp.media.view.MediaFrame.prototype.initialize.apply(this, arguments);
            
            this.states.add([
                new ExternalImageState(),
            ]);
        };

        // Create external image content view
        wp.media.view.ExternalImage = wp.media.View.extend({
            className: 'external-image-content',
            template: wp.template('external-image-form'),
            
            events: {
                'click .add-external-image': 'addExternalImage'
            },
            
            addExternalImage: function(e) {
                e.preventDefault();
                var url = this.$('#external-image-url').val();
                
                if (!url) {
                    alert('Please enter an image URL');
                    return;
                }
                
                var $button = $(e.target);
                $button.prop('disabled', true).text('Adding...');
                
                $.ajax({
                    url: wpExternalImages.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'add_external_image',
                        url: url,
                        nonce: wpExternalImages.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Trigger selection in media modal
                            var attachment = wp.media.model.Attachment.create(response.data);
                            wp.media.frame.state().get('selection').add(attachment);
                            wp.media.frame.close();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Failed to add external image');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Add External Image');
                    }
                });
            }
        });

        // Register the content view
        wp.media.view.settings.post.mimeTypes = wp.media.view.settings.post.mimeTypes || {};
        
        // Override content region for external image state
        var originalContent = wp.media.view.MediaFrame.Select.prototype.content;
        wp.media.view.MediaFrame.Select.prototype.content = function() {
            if (this.state().get('id') === 'external-image') {
                this.content.set(new wp.media.view.ExternalImage());
            } else {
                originalContent.apply(this, arguments);
            }
        };
    }

    // Add quick external image option to media library
    $('.media-toolbar-primary').append(
        '<button type="button" class="button media-button button-primary button-large external-image-quick-add">' +
        'Add External Image</button>'
    );

    $(document).on('click', '.external-image-quick-add', function() {
        var url = prompt('Enter external image URL:');
        if (url) {
            addExternalImageDirectly(url);
        }
    });

    function addExternalImageDirectly(url) {
        $.ajax({
            url: wpExternalImages.ajaxUrl,
            type: 'POST',
            data: {
                action: 'add_external_image',
                url: url,
                nonce: wpExternalImages.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('External image added successfully!');
                    location.reload(); // Refresh media library
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to add external image');
            }
        });
    }
});

// Template for external image form
wp.media.template('external-image-form', 
    '<div class="external-image-form">' +
        '<h2>Add External Image</h2>' +
        '<p>Enter the URL of an external image to add it to your media library without downloading:</p>' +
        '<label for="external-image-url">Image URL:</label>' +
        '<input type="url" id="external-image-url" placeholder="https://example.com/image.jpg" style="width: 100%; margin: 10px 0;">' +
        '<p class="description">The image will be referenced directly from its external location.</p>' +
        '<button type="button" class="button button-primary add-external-image">Add External Image</button>' +
    '</div>'
);