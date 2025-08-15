/**
 * bbPress Support Toolkit - Combined JavaScript
 * Combines all functionality into a single optimized file
 */

(function($) {
    'use strict';

    // Global variables
    var bbps = {
        preview_timer: null,
        preview_visible: false
    };

    $(document).ready(function() {
        bbps.init();
    });

    bbps.init = function() {
        bbps.initCannedReplies();
        bbps.initLivePreview();
        bbps.initMarkAsRead();
        bbps.initTopicLock();
        bbps.initAdminNotes();
        bbps.initReportContent();
    };

    // Canned Replies Functionality
    bbps.initCannedReplies = function() {
        var toggle = $('.bbps-toggle-canned-replies');
        var list = $('.bbps-canned-replies-list');
        
        // Toggle canned replies list
        $(document).on('click', '.bbps-toggle-canned-replies', function(e) {
            e.preventDefault();
            var $this = $(this);
            var targetList = $this.siblings('.bbps-canned-replies-list');
            if (targetList.length === 0) {
                targetList = $this.parent().find('.bbps-canned-replies-list');
            }
            targetList.slideToggle();
        });
        
        // Insert canned reply
        $(document).on('click', '.bbps-canned-reply-item', function(e) {
            e.preventDefault();
            
            var content = $(this).data('content') || $(this).find('p').text() || $(this).text();
            
            var reply_box = $('#bbp_reply_content, #bbp_topic_content');
            
            if (reply_box.length && content) {
                var current_val = reply_box.val() || '';
                var new_val = current_val === '' ? content : current_val + "\n\n" + content;
                reply_box.val(new_val);
                
                // Support TinyMCE
                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                    var current_content = tinyMCE.activeEditor.getContent();
                    var new_content = current_content === '' ? content : current_content + '<br><br>' + content;
                    tinyMCE.activeEditor.setContent(new_content);
                }
            }
            
            // Visual feedback
            $(this).addClass('bbps-inserted');
            setTimeout(function() {
                $('.bbps-canned-reply-item, .bbp-canned-reply-insert').removeClass('bbps-inserted');
            }, 1000);
            
            // Hide list
            list.slideToggle();
            toggle.toggle();
        });
        
        // Hover effects
        $('.bbps-canned-reply-item, .bbp-canned-reply-insert').hover(
            function() { $(this).addClass('bbps-hover'); },
            function() { $(this).removeClass('bbps-hover'); }
        );
    };

    // Live Preview Functionality
    bbps.initLivePreview = function() {
        // Preview button click
        $('#bbps-preview-btn, .bbp-preview-button').on('click', function(e) {
            e.preventDefault();
            bbps.showPreview();
        });
        
        // Auto preview on typing (with delay)
        $(document).on('keyup', '#bbp_topic_content, #bbp_reply_content', function() {
            var textarea = $(this);
            var previewDiv = $('#bbp-post-preview, #bbps-preview-content');
            if (previewDiv.length) {
                previewDiv.addClass('loading');
                bbps.previewPost(textarea.val(), textarea.attr('id').replace('bbp_', '').replace('_content', ''));
            }
        });
        
        // Quicktags support
        $('.wp-editor-container').on('click', '.quicktags-toolbar', function(e) {
            if ('link' === e.target.value) return;
            
            var textarea = $(this).parent().find('textarea');
            $('#bbp-post-preview, #bbps-preview-content').addClass('loading');
            bbps.previewPost(textarea.val(), textarea.attr('id').split('_')[1]);
        });
        
        // TinyMCE support
        if (typeof tinyMCE !== 'undefined') {
            $(document).on('tinymce-editor-init', function(event, editor) {
                editor.on('KeyUp', function() {
                    $('#bbp-post-preview, #bbps-preview-content').addClass('loading');
                    bbps.previewPost(editor.getContent(), 'tinymce', true);
                });
            });
        }
    };
    
    bbps.previewPost = function(text, type, tinymce) {
        tinymce = typeof tinymce !== 'undefined';
        clearTimeout(bbps.preview_timer);
        
        bbps.preview_timer = setTimeout(function() {
            $.ajax({
                url: bbps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbps_live_preview',
                    text: text,
                    type: type,
                    tinymce: tinymce,
                    nonce: bbps_ajax.nonce
                },
                success: function(response) {
                    var preview = $('#bbp-post-preview, #bbps-preview-content');
                    var wrapper = $('#bbp-post-preview-wrapper, #bbps-preview-wrapper');
                    
                    if (response.success) {
                        preview.html(response.data);
                    } else {
                        preview.html('<p>' + (bbps_ajax.strings.preview_error || 'Preview error') + '</p>');
                    }
                    
                    if (!bbps.preview_visible) {
                        wrapper.slideDown();
                        bbps.preview_visible = true;
                    }
                    
                    preview.removeClass('loading');
                    preview.trigger('loaded.bbps_live_preview');
                },
                error: function() {
                    $('#bbp-post-preview, #bbps-preview-content').html('<p>' + (bbps_ajax.strings.preview_error || 'Preview error') + '</p>');
                }
            });
        }, 1000);
    };
    
    bbps.showPreview = function() {
        var content = '';
        var $previewDiv = $('#bbps-preview-content, #bbp-post-preview');
        
        // Get content from textarea or TinyMCE
        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
            content = tinyMCE.activeEditor.getContent();
        } else {
            content = $('#bbp_topic_content, #bbp_reply_content').val();
        }
        
        if (!content || !content.trim()) {
            alert(bbps_ajax.strings.enter_content || 'Please enter some content to preview.');
            return;
        }
        
        $previewDiv.html('<p>' + (bbps_ajax.strings.loading || 'Loading...') + '</p>').show();
        bbps.previewPost(content, 'manual');
    };

    // Mark as Read Functionality
    bbps.initMarkAsRead = function() {
        $('.bbps-mark-read, .bbp-mark-read').on('click', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var topicId = $link.data('topic') || $link.data('topic-id');
            var action = $link.data('action') || ($link.hasClass('read') ? 'unread' : 'read');
            
            $.ajax({
                url: bbps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbps_mark_read',
                    topic_id: topicId,
                    mark_action: action,
                    nonce: bbps_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $link.text(response.data.new_text || 
                            (action === 'read' ? bbps_ajax.strings.mark_unread : bbps_ajax.strings.mark_read));
                        $link.data('action', response.data.new_action || (action === 'read' ? 'unread' : 'read'));
                        
                        // Update topic class
                        var $topic = $('.bbp-topic-' + topicId + ', #topic-' + topicId);
                        if (action === 'read') {
                            $topic.removeClass('bbps-topic-unread').addClass('bbps-topic-read');
                            $link.removeClass('unread').addClass('read');
                        } else {
                            $topic.removeClass('bbps-topic-read').addClass('bbps-topic-unread');
                            $link.removeClass('read').addClass('unread');
                        }
                    }
                },
                error: function() {
                    alert(bbps_ajax.strings.error);
                }
            });
        });
        
        // Auto-mark as read after viewing for 30 seconds
        if ($('.bbps-topic-unread, .bbp-topic-unread').length > 0) {
            setTimeout(function() {
                $('.bbps-mark-read[data-action="read"], .bbp-mark-read.unread').first().trigger('click');
            }, 30000);
        }
        
        // Mark all as read
        $('.bbps-mark-all-read').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(bbps_ajax.strings.mark_all_read_confirm || 'Mark all topics as read?')) return;
            
            $.ajax({
                url: bbps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbps_mark_all_read',
                    nonce: bbps_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        });
    };

    // Topic Lock Functionality
    bbps.initTopicLock = function() {
        $(document).on('click', '.bbps-topic-lock, .bbps-topic-lock-link', function(e) {
            e.preventDefault();
            
            var link = $(this);
            var topic_id = link.data('topic-id');
            var action = link.data('action');
            
            if (!topic_id) {
                // Try to get topic ID from URL or other sources
                var href = link.attr('href');
                if (href) {
                    var matches = href.match(/topic_id=(\d+)/);
                    if (matches) {
                        topic_id = matches[1];
                    }
                }
                if (!topic_id) {
                    var parent = link.closest('.bbp-topic, .type-topic');
                    if (parent.length) {
                        var id = parent.attr('id');
                        if (id) {
                            var idMatch = id.match(/(\d+)/);
                            if (idMatch) {
                                topic_id = idMatch[1];
                            }
                        }
                    }
                }
            }
            
            if (!topic_id) {
                bbps.utils.showNotification(bbps_ajax.strings.topic_id_not_found || 'Topic ID not found', 'error');
                return;
            }
            
            if (!action) {
                action = link.text().toLowerCase().indexOf('lock') !== -1 ? 'lock' : 'unlock';
            }
            
            $.ajax({
                url: bbps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbps_topic_lock',
                    topic_id: topic_id,
                    lock_action: action,
                    nonce: bbps_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Update link text and action based on server response
                        link.text(response.data.link_text || (response.data.is_locked ? 
                            (bbps_ajax.strings.unlock_topic || 'Unlock Topic') : 
                            (bbps_ajax.strings.lock_topic || 'Lock Topic')));
                        link.data('action', response.data.is_locked ? 'unlock' : 'lock');
                        
                        // Update topic status
                        var topic = link.closest('.bbp-topic, .bbp-reply, .type-topic');
                        if (response.data.is_locked) {
                            topic.addClass('bbp-topic-locked');
                        } else {
                            topic.removeClass('bbp-topic-locked');
                        }
                        
                        var successMsg = response.data.message || 
                            (action === 'lock' ? 
                                (bbps_ajax.strings.topic_locked_success || 'Topic locked successfully') : 
                                (bbps_ajax.strings.topic_unlocked_success || 'Topic unlocked successfully'));
                        bbps.utils.showNotification(successMsg, 'success');
                    } else {
                        var errorMsg = response.data || 
                            (action === 'lock' ? 
                                (bbps_ajax.strings.error_locking_topic || 'Error locking topic') : 
                                (bbps_ajax.strings.error_unlocking_topic || 'Error unlocking topic'));
                        bbps.utils.showNotification(errorMsg, 'error');
                    }
                },
                error: function() {
                    bbps.utils.showNotification(bbps_ajax.strings.ajax_error || 'AJAX error occurred', 'error');
                }
            });
        });
    };

    // Admin Notes Functionality
    bbps.initAdminNotes = function() {
        // Toggle note form
        $(document).on('click', '.bbps-admin-note-toggle, .bbps-add-note-link', function(e) {
            e.preventDefault();
            var $this = $(this);
            var form = $this.siblings('.bbps-admin-note-form, .bbps-note-form');
            if (form.length === 0) {
                form = $this.parent().find('.bbps-admin-note-form, .bbps-note-form');
            }
            form.slideToggle();
        });
        
        // Submit note
        $(document).on('submit', '.bbps-admin-note-form, .bbps-note-form', function(e) {
            e.preventDefault();
            var form = $(this);
            var note = form.find('textarea[name="admin_note"]').val();
            var topic_id = form.find('input[name="topic_id"]').val();
            if (!topic_id) {
                topic_id = form.data('topic-id') || form.closest('[data-topic-id]').data('topic-id');
            }
            if (!note || !note.trim()) {
                bbps.utils.showNotification(bbps_ajax.strings.enter_note || 'Please enter a note', 'error');
                return;
            }
            if (!topic_id) {
                bbps.utils.showNotification(bbps_ajax.strings.topic_id_not_found || 'Topic ID not found', 'error');
                return;
            }
            $.ajax({
                url: bbps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbps_save_note',
                    topic_id: topic_id,
                    admin_note: note,
                    nonce: bbps_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        form.find('textarea[name="admin_note"]').val('');
                        form.slideUp();
                        bbps.utils.showNotification(bbps_ajax.strings.note_saved || 'Note saved successfully', 'success');
                        // Reload the page to show the new note
                        location.reload();
                    } else {
                        bbps.utils.showNotification(response.data || (bbps_ajax.strings.error_saving_note || 'Error saving note'), 'error');
                    }
                },
                error: function() {
                    bbps.utils.showNotification(bbps_ajax.strings.ajax_error || 'AJAX error occurred', 'error');
                }
            });
        });
    };

    // Report Content Functionality
    bbps.initReportContent = function() {
        $(document).on('click', '.bbps-report-link, .bbps-report-content', function(e) {
            e.preventDefault();
            var link = $(this);
            var post_id = link.data('post-id') || link.data('post');
            if (!post_id) {
                var href = link.attr('href');
                if (href) {
                    var matches = href.match(/post_id=(\d+)/);
                    if (matches) {
                        post_id = matches[1];
                    }
                }
                if (!post_id) {
                    var parent = link.closest('.bbp-topic, .bbp-reply, .type-topic, .type-reply');
                    if (parent.length) {
                        var id = parent.attr('id');
                        if (id) {
                            var idMatch = id.match(/(\d+)/);
                            if (idMatch) {
                                post_id = idMatch[1];
                            }
                        }
                    }
                }
            }
            if (!post_id) {
                bbps.utils.showNotification(bbps_ajax.strings.post_id_not_found || 'Post ID not found', 'error');
                return;
            }
            var reason = prompt(bbps_ajax.strings.enter_report_reason || 'Please enter the reason for reporting this content:');
            if (!reason || !reason.trim()) {
                return;
            }
            $.ajax({
                url: bbps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbps_report_content',
                    post_id: post_id,
                    reason: reason,
                    nonce: bbps_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        link.text(bbps_ajax.strings.reported || 'Reported');
                        link.addClass('reported');
                        link.removeClass('bbps-report-link bbps-report-content');
                        bbps.utils.showNotification(bbps_ajax.strings.content_reported || 'Content reported successfully', 'success');
                    } else {
                        bbps.utils.showNotification(response.data || (bbps_ajax.strings.error_reporting || 'Error reporting content'), 'error');
                    }
                },
                error: function() {
                    bbps.utils.showNotification(bbps_ajax.strings.ajax_error || 'AJAX error occurred', 'error');
                }
            });
        });
    };

    // Utility functions
    bbps.utils = {
        // Debounce function
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        // Show notification
        showNotification: function(message, type) {
            type = type || 'info';
            var notification = $('<div class="bbps-notification bbps-' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Expose bbps object globally
    window.bbps = bbps;

})(jQuery);