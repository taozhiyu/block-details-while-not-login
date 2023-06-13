<?php
/*
Plugin Name: block-details-while-not-login
Plugin URI: https://github.com/taozhiyu/block-details-while-not-login
Description: block details page while not login
Version: 1.0
Author: taozhiyu
Author URI: https://github.com/taozhiyu
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) die();

yourls_add_action('require_auth', 'my_checks');


function get_default_i18ns()
{
	$i18n = [
		'zh_CN' => [
			'denied' => '禁止访问',
			'no_permission' => '您无权访问此页面。',
			'block_page_config' => '禁止访问配置',
			'is_enable' => '是否启用?',
			'yes' => '是',
			'no' => '否',
			'display_title' => '显示标题',
			'display_message' => '显示消息',
			'inject_js' => '注入JavaScript',
			'setting_page' => '详情页屏蔽配置',
			'update' => '更新配置',
			'recommend_tip' => '推荐隐藏修改后台入口，参见<a href="https://github.com/YOURLS/YOURLS/pull/2747#issuecomment-689047797">【这里】</a>的说明'
		],
		'en_US' => [
			'denied' => 'Access Denied',
			'no_permission' => 'You do not have permission to access this page.',
			'block_page_config' => 'Block Page Config',
			'is_enable' => 'Enable?',
			'yes' => 'Yes',
			'no' => 'No',
			'display_title' => 'Display Title',
			'display_message' => 'Display Message',
			'inject_js' => 'Inject JavaScript',
			'setting_page' => 'Block-details setting page',
			'update' => 'Update',
			'recommend_tip' => 'Recommend to hide the modified background entry, see instructions <a href="https://github.com/YOURLS/YOURLS/pull/2747#issuecomment-689047797"> [here] </a>'
		],
	];
	$lang = YOURLS_LANG;
	if (!array_key_exists($lang, $i18n)) {
		$lang = 'en_US';
	}
	return $i18n[$lang];
}
function my_checks()
{
	if (str_starts_with($_SERVER['PHP_SELF'], parse_url(yourls_admin_url())['path']) || yourls_get_option('tao_isEnable') == "false") return;
	$title = check_option('tao_custom_title', 'denied');
	$message = check_option('tao_custom_msg', 'no_permission');
	$header_code = 403;
	yourls_status_header($header_code);

	if (!yourls_did_action('html_head')) {
		yourls_html_head();
?>
		<header role="banner">
			<h1>
				<a href="#" title="YOURLS"><span>YOURLS</span>: <span>Y</span>our <span>O</span>wn <span>URL</span> <span>S</span>hortener<br />
					<img src="<?php yourls_site_url(); ?>/images/yourls-logo.svg" id="yourls-logo" alt="YOURLS" title="YOURLS" /></a>
			</h1>
		</header>
<?php
	}
	echo '<div id="login">';
	echo yourls_apply_filter('die_title', "<h2>$title</h2>");
	echo yourls_apply_filter('die_message', "<p>$message</p>");
	echo '</div>';
	$js = yourls_get_option('tao_custom_js');
	if ($js) {
		echo <<<HTML
<script>$js</script>
HTML;
	}
	die(1);
}


yourls_add_action('plugins_loaded', 'add_page');
function add_page()
{
	yourls_register_plugin_page('blocker_page', get_default_i18ns()['setting_page'], 'do_page');
}

function do_page()
{
	if (isset($_POST['tao_isEnable'])) {
		yourls_verify_nonce('blocker_page');
		update_option();
	}

	build_page();
}


function update_option()
{
	yourls_update_option('tao_isEnable', $_POST['tao_isEnable']);
	yourls_update_option('tao_custom_title', $_POST['tao_custom_title']);
	yourls_update_option('tao_custom_msg', $_POST['tao_custom_msg']);
	yourls_update_option('tao_custom_js', $_POST['tao_custom_js']);
}


function check_option($name, $id)
{
	return  yourls_get_option($name) ? yourls_get_option($name) : get_default_i18ns()[$id];
}


function build_page()
{
	$default_i18ns = get_default_i18ns();
	$isEnable = yourls_get_option('tao_isEnable') === 'true' ? true : false;
	$e1 = $isEnable ? 'checked' : '';
	$e2 = !$isEnable ? 'checked' : '';
	$custom_title = check_option('tao_custom_title', 'denied');
	$custom_msg = check_option('tao_custom_msg', 'no_permission');
	$custom_js = yourls_get_option('tao_custom_js');
	if (!$custom_js) {
		$custom_js = 'setTimeout(()=>location.pathname=\'\',5000)';
	}
	$nonce = yourls_create_nonce('blocker_page');

	echo <<<HTML
<form method="post">
<input type="hidden" name="nonce" value="$nonce" />
<style>
	.l {
	display: inline-block;
	width: 150px;
	text-align: right;
	}
</style>
<p style="font-size:20px"><svg viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20"><path d="M512 106.667A405.333 405.333 0 1 1 106.667 512 405.333 405.333 0 0 1 512 106.667m0-64A469.333 469.333 0 1 0 981.333 512 469.333 469.333 0 0 0 512 42.667z" fill="#e16531"></path><path d="M512 800a32 32 0 0 1-32-32V448a32 32 0 0 1 64 0v320a32 32 0 0 1-32 32zM469.333 298.667a42.667 42.667 0 1 0 85.334 0 42.667 42.667 0 1 0-85.334 0z" fill="#e16531"></path></svg> {$default_i18ns['recommend_tip']}</p>
<fieldset>
	<legend>{$default_i18ns['block_page_config']}</legend>
	<label class="l">{$default_i18ns['is_enable']}</label>
	<label for="isEnableY">
	<input type="radio" name="tao_isEnable" id="isEnableY" value="true" $e1>
	{$default_i18ns['yes']}
	</label>
	<label for="isEnableN">
	<input type="radio" name="tao_isEnable" id="isEnableN" value="false" $e2>
	{$default_i18ns['no']}
	</label>
	<br>
	<label class="l" for="custom_title">{$default_i18ns['display_title']}</label>
	<input id="custom_title" name="tao_custom_title" type="text" value="$custom_title">
	<br>
	<label class="l" for="custom_msg">{$default_i18ns['display_message']}</label>
	<input id="custom_msg" name="tao_custom_msg" type="text" value="$custom_msg">
	<br>
	<label class="l" for="custom_js">{$default_i18ns['inject_js']}</label>
	<textarea id="custom_js" name="tao_custom_js">$custom_js</textarea>
</fieldset>
<input type="submit" value="{$default_i18ns['update']}" />
</form>
HTML;
}
