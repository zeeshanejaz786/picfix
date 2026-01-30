jQuery(document).ready(function($) {
    'use strict';

    // Tab navigation
    $('#picfix-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('#picfix-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('#picfix-tab-content .picfix-tab-pane').removeClass('active');
        $(target).addClass('active');
    });

    function showLoader(container, text) {
        container.find('.picfix-loader').show().html('<div class="picfix-loader-content"><span class="spinner is-active"></span><p>' + text + '</p></div>');
        container.find('.picfix-results').empty().hide();
    }

    function hideLoader(container) {
        container.find('.picfix-loader').hide().empty();
        container.find('.picfix-results').show();
    }

    // --- Scan Handlers ---

    $('#scan-unused-btn').on('click', function() {
        var container = $('#unused-images');
        showLoader(container, 'Scanning posts and pages for unused images...');
        $.post(picfix_ajax.ajax_url, { action: 'picfix_scan_unused', nonce: picfix_ajax.nonce }, function(response) {
            hideLoader(container);
            if (response.success) {
                displayUnusedResults(response.data);
            } else {
                $('#unused-images-results').html('<div class="notice notice-error inline"><p>An error occurred during the scan.</p></div>');
            }
        });
    });

    $('#scan-duplicates-btn').on('click', function() {
        var container = $('#duplicate-images');
        showLoader(container, 'Analyzing file hashes to find duplicates. This may take a moment...');
        $.post(picfix_ajax.ajax_url, { action: 'picfix_scan_duplicates', nonce: picfix_ajax.nonce }, function(response) {
            hideLoader(container);
            if (response.success) {
                displayDuplicateResults(response.data);
            } else {
                $('#duplicate-images-results').html('<div class="notice notice-error inline"><p>An error occurred during the scan.</p></div>');
            }
        });
    });

    $('#scan-non-webp-btn').on('click', function() {
        var container = $('#convert-to-webp');
        showLoader(container, 'Checking your media library for images that can be converted...');
        $.post(picfix_ajax.ajax_url, { action: 'picfix_scan_non_webp', nonce: picfix_ajax.nonce }, function(response) {
            hideLoader(container);
            if (response.success) {
                displayNonWebpResults(response.data);
            } else {
                $('#non-webp-images-results').html('<div class="notice notice-error inline"><p>An error occurred during the scan.</p></div>');
            }
        });
    });

    // --- Result Display Functions ---

    function displayUnusedResults(images) {
        var resultsDiv = $('#unused-images-results');
        if (images.length === 0) {
            resultsDiv.html('<div class="notice notice-success inline"><p><strong>All Clean!</strong> No unused images were found.</p></div>');
            return;
        }
        var html = `<p>Found ${images.length} unused image(s). Review carefully before deleting.</p><div class="picfix-card-grid">`;
        images.forEach(function(image) {
            html += `<div class="picfix-card" data-id="${image.id}">
                <div class="picfix-card-thumb"><img src="${image.thumb}" alt="${image.filename}"></div>
                <div class="picfix-card-info">
                    <a href="${image.url}" target="_blank">${image.filename}</a>
                </div>
                <div class="picfix-card-actions">
                    <button class="button button-link-delete delete-attachment-btn">Delete Permanently</button>
                    <span class="spinner"></span>
                </div>
            </div>`;
        });
        html += '</div>';
        resultsDiv.html(html);
    }

    function displayDuplicateResults(groups) {
        var resultsDiv = $('#duplicate-images-results');
        if (groups.length === 0) {
            resultsDiv.html('<div class="notice notice-success inline"><p><strong>No Duplicates Found!</strong> Your media library is lean.</p></div>');
            return;
        }
        var html = `<p>Found ${groups.length} set(s) of duplicate images. You can delete the extra copies.</p>`;
        groups.forEach(function(group, index) {
            html += `<div class="picfix-duplicate-group postbox">
                        <h2 class="hndle"><span>Duplicate Set #${index + 1}</span></h2>
                        <div class="inside"><div class="picfix-card-grid">`;
            group.forEach(function(image) {
                 html += `<div class="picfix-card" data-id="${image.id}">
                    <div class="picfix-card-thumb"><img src="${image.thumb}" alt="${image.filename}"></div>
                    <div class="picfix-card-info">
                        <strong>${image.filename}</strong>
                        <small>${image.path}</small>
                    </div>
                    <div class="picfix-card-actions">
                        <button class="button button-link-delete delete-attachment-btn">Delete Permanently</button>
                        <span class="spinner"></span>
                    </div>
                </div>`;
            });
            html += '</div></div></div>';
        });
        resultsDiv.html(html);
    }

    function displayNonWebpResults(images) {
        var resultsDiv = $('#non-webp-images-results');
        if (images.length === 0) {
            resultsDiv.html('<div class="notice notice-success inline"><p><strong>All Set!</strong> No images need conversion to WebP.</p></div>');
            return;
        }
        var html = `<p>Found ${images.length} image(s) that can be converted to WebP.</p><div class="picfix-card-grid">`;
        images.forEach(function(image) {
            html += `<div class="picfix-card" data-path="${image.path}">
                <div class="picfix-card-thumb"><img src="${image.thumb}" alt="${image.filename}"></div>
                <div class="picfix-card-info">
                    <span>${image.filename}</span>
                </div>
                <div class="picfix-card-actions">
                    <button class="button button-primary convert-webp-btn">Convert</button>
                    <span class="spinner"></span>
                    <div class="picfix-status"></div>
                </div>
            </div>`;
        });
        html += '</div>';
        resultsDiv.html(html);
    }

    // --- Action Handlers (Delegated) ---

    $(document).on('click', '.delete-attachment-btn', function() {
        if (!confirm('Are you absolutely sure you want to permanently delete this image and all its data? This action cannot be undone.')) {
            return;
        }

        var btn = $(this);
        var card = btn.closest('.picfix-card');
        var id = card.data('id');
        var spinner = card.find('.spinner');

        spinner.addClass('is-active');
        btn.prop('disabled', true);

        $.post(picfix_ajax.ajax_url, { action: 'picfix_delete_attachment', nonce: picfix_ajax.nonce, id: id }, function(response) {
            spinner.removeClass('is-active');
            if (response.success) {
                card.css('border-color', '#d63638').fadeOut(500, function() { $(this).remove(); });
            } else {
                alert('Error: ' + response.data);
                btn.prop('disabled', false);
            }
        });
    });
    
    $(document).on('click', '.convert-webp-btn', function() {
        var btn = $(this);
        var card = btn.closest('.picfix-card');
        var path = card.data('path');
        var spinner = card.find('.spinner');
        var status = card.find('.picfix-status');

        spinner.addClass('is-active');
        btn.prop('disabled', true);
        status.empty();

        $.post(picfix_ajax.ajax_url, { action: 'picfix_convert_to_webp', nonce: picfix_ajax.nonce, path: path }, function(response) {
            spinner.removeClass('is-active');
            if (response.success) {
                card.addClass('converted');
                btn.remove();
                status.html(`<span class="dashicons dashicons-yes-alt"></span> Converted!<br><small>Saved ${response.data.percent_saved}% (${response.data.new_size})</small>`);
            } else {
                status.html(`<span class="dashicons dashicons-warning"></span> Error: ${response.data}`).addClass('error');
                btn.prop('disabled', false);
            }
        });
    });
});
