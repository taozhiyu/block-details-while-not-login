<?php
/*
Plugin Name: Block Details While Not Login
Plugin URI: https://github.com/taozhiyu/block-details-while-not-login
Description: Restrict access to the URL details/stats page (yourls-infos.php) for unauthenticated visitors. Compatible with YOURLS 1.10+ and PHP 7.4–8.5.
Version: 2.0.0
Author: taozhiyu
Author URI: https://github.com/taozhiyu
*/

declare(strict_types=1);

if (!defined('YOURLS_ABSPATH')) die();

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

const BDWNL_VERSION    = '2.0.0';
const BDWNL_OPT_PREFIX = 'bdwnl_';
const BDWNL_PAGE_SLUG  = 'blocker_page';
const BDWNL_TEXTDOMAIN = 'block-details-while-not-login';

yourls_add_action('plugins_loaded', 'bdwnl_load_textdomain');
function bdwnl_load_textdomain(): void {
    yourls_load_custom_textdomain(BDWNL_TEXTDOMAIN, dirname(__FILE__) . '/languages');
}

function bdwnl_defaults(): array {
    return [
        'enabled'        => '1',
        'http_code'      => '403',
        'title'          => '',
        'message'        => '',
        'redirect_url'   => '',
        'redirect_after' => '0',
        'inject_js'      => '',
        'show_branding'  => '1',
    ];
}

function bdwnl_opt(string $key): string {
    $defaults = bdwnl_defaults();
    if (!array_key_exists($key, $defaults)) return '';
    $value = yourls_get_option(BDWNL_OPT_PREFIX . $key);
    return ($value === false || $value === null) ? (string) $defaults[$key] : (string) $value;
}

function bdwnl_is_enabled(): bool {
    return bdwnl_opt('enabled') === '1';
}

/**
 * Determine authentication WITHOUT triggering yourls_is_valid_user().
 *
 * Calling yourls_is_valid_user() inside an auth-pipeline action causes the
 * entire auth flow (including admin_login nonce verification) to run twice
 * on YOURLS 1.10+, where yourls_redirect() no longer exits. The second run
 * computes the nonce against the now-defined YOURLS_USER, mismatching the
 * placeholder '-1' baked into the form, which dies with
 * "Unauthorized action or expired link".
 */
function bdwnl_is_authenticated(): bool {
    if (defined('YOURLS_USER')) return true;

    $cookie_name = yourls_cookie_name();
    if (empty($_COOKIE[$cookie_name])) return false;

    return yourls_check_auth_cookie();
}

yourls_add_action('plugins_loaded', 'bdwnl_migrate_legacy_options');
function bdwnl_migrate_legacy_options(): void {
    if (yourls_get_option(BDWNL_OPT_PREFIX . 'migrated') === '1') return;

    $legacy = [
        'tao_isEnable'     => 'enabled',
        'tao_custom_title' => 'title',
        'tao_custom_msg'   => 'message',
        'tao_custom_js'    => 'inject_js',
    ];

    foreach ($legacy as $old => $new) {
        $value = yourls_get_option($old);
        if ($value === false || $value === null) continue;
        if ($new === 'enabled') {
            $value = ($value === 'true' || $value === '1') ? '1' : '0';
        }
        yourls_update_option(BDWNL_OPT_PREFIX . $new, (string) $value);
    }
    yourls_update_option(BDWNL_OPT_PREFIX . 'migrated', '1');
}

yourls_add_action('pre_yourls_infos', 'bdwnl_guard_infos');
function bdwnl_guard_infos(): void {
    if (!bdwnl_is_enabled()) return;
    if (bdwnl_is_authenticated()) return;
    bdwnl_render_block_page();
}

function bdwnl_render_block_page(): void {
    $code = (int) filter_var(bdwnl_opt('http_code'), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 100, 'max_range' => 599, 'default' => 403],
    ]);
    yourls_status_header($code);

    $redirect = trim(bdwnl_opt('redirect_url'));
    $delay    = max(0, (int) bdwnl_opt('redirect_after'));

    if ($redirect !== '' && filter_var($redirect, FILTER_VALIDATE_URL) && $delay === 0) {
        yourls_redirect($redirect, 302);
        die();
    }

    bdwnl_render_message_page($redirect !== '' ? $redirect : null, $delay);
    die();
}

function bdwnl_render_message_page(?string $redirect, int $delay): void {
    $stored_title = bdwnl_opt('title');
    $stored_msg   = bdwnl_opt('message');
    $title = $stored_title !== '' ? $stored_title : yourls__('Access Denied', BDWNL_TEXTDOMAIN);
    $msg   = $stored_msg   !== '' ? $stored_msg   : yourls__('You do not have permission to access this page.', BDWNL_TEXTDOMAIN);
    $js    = bdwnl_opt('inject_js');

    if (!yourls_did_action('html_head')) {
        yourls_html_head();
        if (bdwnl_opt('show_branding') === '1') {
            $logo = yourls_site_url(false) . '/images/yourls-logo.svg';
            echo '<header role="banner"><h1>'
               . '<a href="#" title="YOURLS">'
               . '<span>YOURLS</span>: <span>Y</span>our <span>O</span>wn <span>URL</span> <span>S</span>hortener<br/>'
               . '<img src="' . yourls_esc_attr($logo) . '" id="yourls-logo" alt="YOURLS"/>'
               . '</a></h1></header>';
        }
    }

    if ($redirect !== null && $delay > 0 && filter_var($redirect, FILTER_VALIDATE_URL)) {
        echo '<meta http-equiv="refresh" content="' . $delay
           . '; url=' . yourls_esc_attr($redirect) . '">';
    }

    echo '<div id="login" class="bdwnl-blocked">';
    echo yourls_apply_filter('die_title',   '<h2>' . yourls_esc_html($title) . '</h2>');
    echo yourls_apply_filter('die_message', '<p>' . yourls_esc_html($msg) . '</p>');
    if ($redirect !== null && $delay > 0) {
        echo '<p><small>' . yourls_esc_html(yourls_s(yourls__('Redirecting in %s seconds…', BDWNL_TEXTDOMAIN), $delay)) . '</small></p>';
    }
    echo '</div>';

    if ($js !== '') {
        echo "\n<script>(function(){ {$js} })();</script>\n";
    }
}

yourls_add_action('plugins_loaded', 'bdwnl_register_admin_page');
function bdwnl_register_admin_page(): void {
    // The plugin name (menu item) is intentionally NOT translated, matching
    // the plugin's GitHub identity and keeping the admin sidebar stable
    // regardless of YOURLS_LANG.
    yourls_register_plugin_page(
        BDWNL_PAGE_SLUG,
        'block-details-while-not-login',
        'bdwnl_settings_controller'
    );
}

function bdwnl_settings_controller(): void {
    if (isset($_POST['bdwnl_save'])) {
        yourls_verify_nonce(BDWNL_PAGE_SLUG);
        bdwnl_save_settings($_POST);
        yourls_add_notice(yourls_esc_html(yourls__('Settings saved.', BDWNL_TEXTDOMAIN)));
    }
    if (isset($_POST['bdwnl_reset'])) {
        yourls_verify_nonce(BDWNL_PAGE_SLUG);
        bdwnl_reset_settings();
        yourls_add_notice(yourls_esc_html(yourls__('Settings reset to defaults.', BDWNL_TEXTDOMAIN)));
    }

    bdwnl_render_settings_form();
}

function bdwnl_save_settings(array $post): void {
    foreach (array_keys(bdwnl_defaults()) as $key) {
        $field = BDWNL_OPT_PREFIX . $key;
        if (!array_key_exists($field, $post)) {
            if ($key === 'enabled' || $key === 'show_branding') {
                yourls_update_option($field, '0');
            }
            continue;
        }
        yourls_update_option($field, (string) $post[$field]);
    }
}

function bdwnl_reset_settings(): void {
    foreach (bdwnl_defaults() as $key => $default) {
        yourls_update_option(BDWNL_OPT_PREFIX . $key, (string) $default);
    }
}

function bdwnl_render_settings_form(): void {
    $nonce = yourls_create_nonce(BDWNL_PAGE_SLUG);

    echo bdwnl_styles();
    ?>
    <h2>block-details-while-not-login
        <small style="font-weight:normal;color:#888;">v<?php echo BDWNL_VERSION; ?></small></h2>

    <p class="bdwnl-tip">
        <span class="bdwnl-tip-icon">⚠</span>
        <?php echo yourls_s(
            /* translators: %s is an HTML link to the YOURLS PR #2747 comment */
            yourls__('Tip: also rename the %1$s/admin/%2$s directory and create a %1$suser/cache.php%2$s filter to mask the admin URL — see %3$sYOURLS PR #2747%4$s.', BDWNL_TEXTDOMAIN),
            '<code>',
            '</code>',
            '<a href="https://github.com/YOURLS/YOURLS/pull/2747#issuecomment-689047797" target="_blank" rel="noopener noreferrer">',
            '</a>'
        ); ?>
    </p>

    <form method="post" class="bdwnl-form">
        <input type="hidden" name="nonce" value="<?php echo yourls_esc_attr($nonce); ?>" />

        <fieldset>
            <legend><?php yourls_e('Block Page Configuration', BDWNL_TEXTDOMAIN); ?></legend>

            <div class="bdwnl-row bdwnl-row-toggle">
                <label class="bdwnl-toggle">
                    <input type="checkbox" name="<?php echo BDWNL_OPT_PREFIX; ?>enabled" value="1"
                        <?php echo bdwnl_opt('enabled') === '1' ? 'checked' : ''; ?>>
                    <strong><?php yourls_e('Enable blocking', BDWNL_TEXTDOMAIN); ?></strong>
                </label>
            </div>

            <div class="bdwnl-row">
                <label for="bdwnl_http_code"><?php yourls_e('HTTP status code', BDWNL_TEXTDOMAIN); ?></label>
                <select id="bdwnl_http_code" name="<?php echo BDWNL_OPT_PREFIX; ?>http_code">
                    <?php foreach (['401', '403', '404', '410', '451'] as $code) {
                        $sel = bdwnl_opt('http_code') === $code ? ' selected' : '';
                        echo '<option value="' . $code . '"' . $sel . '>' . $code . '</option>';
                    } ?>
                </select>
            </div>

            <div class="bdwnl-row">
                <label for="bdwnl_title"><?php yourls_e('Display title', BDWNL_TEXTDOMAIN); ?></label>
                <input type="text" id="bdwnl_title" name="<?php echo BDWNL_OPT_PREFIX; ?>title"
                       value="<?php echo yourls_esc_attr(bdwnl_opt('title')); ?>"
                       placeholder="<?php echo yourls_esc_attr(yourls__('Access Denied', BDWNL_TEXTDOMAIN)); ?>">
            </div>

            <div class="bdwnl-row">
                <label for="bdwnl_message"><?php yourls_e('Display message', BDWNL_TEXTDOMAIN); ?></label>
                <input type="text" id="bdwnl_message" name="<?php echo BDWNL_OPT_PREFIX; ?>message"
                       value="<?php echo yourls_esc_attr(bdwnl_opt('message')); ?>"
                       placeholder="<?php echo yourls_esc_attr(yourls__('You do not have permission to access this page.', BDWNL_TEXTDOMAIN)); ?>">
            </div>

            <div class="bdwnl-row bdwnl-row-toggle">
                <label class="bdwnl-toggle">
                    <input type="checkbox" name="<?php echo BDWNL_OPT_PREFIX; ?>show_branding" value="1"
                        <?php echo bdwnl_opt('show_branding') === '1' ? 'checked' : ''; ?>>
                    <?php yourls_e('Show YOURLS branding header', BDWNL_TEXTDOMAIN); ?>
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend><?php yourls_e('Redirect (optional)', BDWNL_TEXTDOMAIN); ?></legend>

            <div class="bdwnl-row">
                <label for="bdwnl_redirect_url"><?php yourls_e('Redirect URL', BDWNL_TEXTDOMAIN); ?></label>
                <input type="url" id="bdwnl_redirect_url" name="<?php echo BDWNL_OPT_PREFIX; ?>redirect_url"
                       value="<?php echo yourls_esc_attr(bdwnl_opt('redirect_url')); ?>"
                       placeholder="https://example.com/">
            </div>
            <p class="bdwnl-help"><?php yourls_e('If set with delay 0, guests are redirected immediately. With a delay, the message is shown first.', BDWNL_TEXTDOMAIN); ?></p>

            <div class="bdwnl-row">
                <label for="bdwnl_redirect_after"><?php yourls_e('Delay (seconds)', BDWNL_TEXTDOMAIN); ?></label>
                <input type="number" min="0" max="60" id="bdwnl_redirect_after"
                       name="<?php echo BDWNL_OPT_PREFIX; ?>redirect_after"
                       value="<?php echo yourls_esc_attr(bdwnl_opt('redirect_after')); ?>">
            </div>
        </fieldset>

        <fieldset>
            <legend><?php yourls_e('Advanced', BDWNL_TEXTDOMAIN); ?></legend>

            <div class="bdwnl-row bdwnl-row-textarea">
                <label for="bdwnl_inject_js"><?php yourls_e('Inject JavaScript', BDWNL_TEXTDOMAIN); ?></label>
                <textarea id="bdwnl_inject_js" name="<?php echo BDWNL_OPT_PREFIX; ?>inject_js"
                          rows="4"
                          placeholder="setTimeout(()=>location.pathname='', 5000)"
                ><?php echo yourls_esc_html(bdwnl_opt('inject_js')); ?></textarea>
            </div>
            <p class="bdwnl-help"><?php yourls_e('Wrapped in an IIFE on render. Trusted, admin-supplied code only.', BDWNL_TEXTDOMAIN); ?></p>
        </fieldset>

        <div class="bdwnl-actions">
            <input type="submit" name="bdwnl_save" value="<?php echo yourls_esc_attr(yourls__('Save changes', BDWNL_TEXTDOMAIN)); ?>"
                   class="button-primary">
            <input type="submit" name="bdwnl_reset"
                   value="<?php echo yourls_esc_attr(yourls__('Reset to defaults', BDWNL_TEXTDOMAIN)); ?>"
                   class="button"
                   onclick="return confirm('<?php echo yourls_esc_attr(yourls__('Reset all settings to defaults?', BDWNL_TEXTDOMAIN)); ?>');">
        </div>
    </form>
    <?php
}

function bdwnl_styles(): string {
    return <<<CSS
<style>
.bdwnl-form fieldset       { margin: 1em 0; padding: 1em 1.25em; border: 1px solid #ccc; border-radius: 4px; }
.bdwnl-form legend         { font-weight: bold; padding: 0 0.5em; }
.bdwnl-row                 { display: grid; grid-template-columns: 200px 1fr; gap: 0.75em; align-items: center; margin: 0.65em 0; }
.bdwnl-row > label         { text-align: right; }
.bdwnl-row input[type=text],
.bdwnl-row input[type=url],
.bdwnl-row input[type=number],
.bdwnl-row select,
.bdwnl-row textarea        { width: 100%; max-width: 480px; padding: 0.4em; box-sizing: border-box; font-size: 0.95em; }
.bdwnl-row textarea        { font-family: monospace; resize: vertical; }
.bdwnl-row-toggle          { grid-template-columns: 1fr; }
.bdwnl-row-toggle .bdwnl-toggle { text-align: left; }
.bdwnl-help                { margin: 0.25em 0 0.5em 200px; font-size: 0.85em; color: #666; }
.bdwnl-actions             { margin-top: 1em; display: flex; gap: 0.5em; }
.bdwnl-tip                 { padding: 0.75em 1em; background: #fff3cd; border-left: 4px solid #e16531; border-radius: 3px; margin: 1em 0; }
.bdwnl-tip-icon            { color: #e16531; font-weight: bold; margin-right: 0.5em; font-size: 1.1em; }
@media (max-width: 720px) {
    .bdwnl-row             { grid-template-columns: 1fr; }
    .bdwnl-row > label     { text-align: left; }
    .bdwnl-help            { margin-left: 0; }
}
</style>
CSS;
}
