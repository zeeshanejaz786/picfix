<?php
/**
 * Provide an admin area view for the plugin.
 *
 * @package    Picfix
 * @subpackage Picfix/admin/partials
 */
?>
<div class="wrap picfix-wrap">
    <h1><span class="dashicons-before dashicons-camera"></span> PicFix Image Tools</h1>
    <p class="picfix-intro">Your toolkit for a faster, cleaner WordPress media library.</p>

    <div id="picfix-tabs" class="nav-tab-wrapper">
        <a href="#unused-images" class="nav-tab nav-tab-active"><span class="dashicons dashicons-search"></span> Unused Images</a>
        <a href="#duplicate-images" class="nav-tab"><span class="dashicons dashicons-admin-page"></span> Duplicate Images</a>
        <a href="#convert-to-webp" class="nav-tab"><span class="dashicons dashicons-performance"></span> Convert to WebP</a>
    </div>

    <div id="picfix-tab-content">
        <!-- Unused Images Tab -->
        <div id="unused-images" class="picfix-tab-pane active">
            <div class="picfix-tool-header">
                <h2><span class="dashicons dashicons-search"></span> Find Unused Images</h2>
                <p>Scan your media library to find images that are not referenced in any published posts or pages. This helps you safely remove clutter.</p>
                <div class="notice notice-warning inline">
                    <p><strong>Heads up:</strong> This scan checks post/page content. Images used directly in theme files, widgets, or some complex page builders might be flagged as unused. <strong>Always double-check before deleting.</strong></p>
                </div>
            </div>
            <button id="scan-unused-btn" class="button button-primary button-hero"><span class="dashicons dashicons-image-filter"></span> Start Scan</button>
            <div class="picfix-results-container">
                <div class="picfix-loader" style="display:none;"></div>
                <div id="unused-images-results" class="picfix-results"></div>
            </div>
        </div>

        <!-- Duplicate Images Tab -->
        <div id="duplicate-images" class="picfix-tab-pane">
            <div class="picfix-tool-header">
                <h2><span class="dashicons dashicons-admin-page"></span> Find Duplicate Images</h2>
                <p>Scan your uploads folder to find identical image files, even if they have different names. You can delete redundant copies to save server space.</p>
            </div>
            <button id="scan-duplicates-btn" class="button button-primary button-hero"><span class="dashicons dashicons-image-flip-horizontal"></span> Find Duplicates</button>
            <div class="picfix-results-container">
                 <div class="picfix-loader" style="display:none;"></div>
                <div id="duplicate-images-results" class="picfix-results"></div>
            </div>
        </div>

        <!-- Convert to WebP Tab -->
        <div id="convert-to-webp" class="picfix-tab-pane">
            <div class="picfix-tool-header">
                <h2><span class="dashicons dashicons-performance"></span> Convert Images to WebP</h2>
                <p>Find all JPG and PNG images that don't have a WebP version and convert them. This creates smaller, faster-loading images without deleting the originals.</p>
            </div>
            <?php if (!function_exists('imagewebp')): ?>
                <div class="notice notice-error">
                    <p><strong>Server Configuration Error:</strong> The `imagewebp` function (part of the GD library) is not enabled on your server. WebP conversion is not possible. Please contact your hosting provider to enable the GD extension.</p>
                </div>
            <?php else: ?>
                <button id="scan-non-webp-btn" class="button button-primary button-hero"><span class="dashicons dashicons-admin-generic"></span> Find Images to Convert</button>
                <div class="picfix-results-container">
                    <div class="picfix-loader" style="display:none;"></div>
                    <div id="non-webp-images-results" class="picfix-results"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
