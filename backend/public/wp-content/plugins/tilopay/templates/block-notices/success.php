<?php

/**
 * Show success messages
 *
 * This template is to override files from /woocommerce/block-notices/error.php.
 *
 * @package Tilopay
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!$notices) {
	return;
}

?>

<?php foreach ($notices as $notice) : ?>
	<div class="wc-block-components-notice-banner is-success wc-block-components-notices-tilopay" <?php echo wc_get_notice_data_attr($notice); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
																?> role="alert">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
			<path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path>
		</svg>
		<div class="wc-block-components-notice-banner__content">
			<?php echo wc_kses_notice($notice['notice']); ?>
		</div>
		<button type="button" class="components-button wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained has-text has-icon" aria-label="<?php esc_attr_e('Dismiss this notice', 'woocommerce'); ?>" onClick="closeTilopayErrorMessage(this)">
			<svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
			</svg>
			<span class="wc-block-components-button__text"></span>
		</button>
	</div>
<?php endforeach; ?>
