<?php

/**
 * Show error messages
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

$multiple = count($notices) > 1;

?>

<div class="wc-block-components-notice-banner is-error is-dismissible wc-block-components-notices-tilopay" role="alert" <?php echo $multiple ? '' : wc_get_notice_data_attr($notices[0]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                                                                                        ?>>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
        <path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path>
    </svg>
    <div class="wc-block-components-notice-banner__content">
        <?php if ($multiple) { ?>
            <p class="wc-block-components-notice-banner__summary"><?php esc_html_e('The following problems were found:', 'woocommerce'); ?></p>
            <ul>
                <?php foreach ($notices as $notice) : ?>
                    <li<?php echo wc_get_notice_data_attr($notice); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>>
                        <?php echo wc_kses_notice($notice['notice']); ?>
                        </li>
                    <?php endforeach; ?>
            </ul>
        <?php
        } else {
            echo wc_kses_notice($notices[0]['notice']);
        }
        ?>
    </div>
    <button type="button" class="components-button wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained has-text has-icon" aria-label="<?php esc_attr_e('Dismiss this notice', 'woocommerce'); ?>" onClick="closeTilopayErrorMessage(this)">
        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
        </svg>
        <span class="wc-block-components-button__text"></span>
    </button>
</div>
<?php
