# block-details-while-not-login | 未登录时屏蔽详情页

A plugin for [YOURLS](https://yourls.org/) that blocks the URL details/stats page (`yourls-infos.php`) for unauthenticated visitors.

[YOURLS](https://yourls.org/) 的插件，在未登录时屏蔽详情页

> **v2.0.0 — Compatible with YOURLS 1.10+ and PHP 7.4 through 8.5**
>
> v1.x users: this version fixes the *"Unauthorized action or expired link"* login error caused by the old `require_auth` hook on YOURLS 1.10+. Existing `tao_*` settings are migrated automatically — no reconfiguration required.

# Features | 特性

## Multilingual | 多语言

| English | 中文 | Nederlands |
|:--:|:--:|:--:|
|![Alt text](./imgs/1.png)|![Alt text](./imgs/1_cn.png)|*nl_NL added in 2.0.0*|

> Translations are loaded via standard YOURLS gettext (`yourls_load_custom_textdomain`). Shipped: `en_US` (built-in), `zh_CN`, `nl_NL`. Other locales fall back to English.
>
> Adding a language: copy `languages/block-details-while-not-login.pot` to `block-details-while-not-login-<locale>.po`, translate, then `msgfmt -o block-details-while-not-login-<locale>.mo block-details-while-not-login-<locale>.po`.

## Safety | 安全

| Before \| 曾经 | Now \| 现状 |
|:--:|:--:|
|![Alt text](./imgs/2.png)|![Alt text](./imgs/3.png)|
| :x: Risk of brute-force attacks and malicious requests | :white_check_mark: Safe! |
| :x: 后台存在爆破和恶意请求风险 | :white_check_mark: 安全！|

## Customization | 自定义

- Configurable HTTP status code (401 / 403 / 404 / 410 / 451)
- Custom title and message text
- Optional redirect URL with configurable delay
- Optional inline JavaScript injection (wrapped in an IIFE)
- Toggle the YOURLS branding header
- "Reset to defaults" button

| ![Alt text](./imgs/1.png) | ![Alt text](./imgs/1_cn.png) |
|:--:|:--:|

> The image shows an example of a jump to the home page after 5 seconds
>
> 图片上显示的是5秒后跳转到主页的例子

# Installation | 安装

1. Download the plugin (or `git clone` this repo) into `user/plugins/block-details-while-not-login/`
2. Activate it in **Manage Plugins** in your YOURLS admin
3. Visit **Block Details Page** in the plugin list to configure

安装YOURLS，安装插件，打开插件，配置里启用

# Hardening recommendation | 加固建议

Even with this plugin, a guest can still find your `/admin/` login form by guessing the URL. To remove that surface entirely, follow ozh's two-step trick from [YOURLS PR #2747 comment 689047797](https://github.com/YOURLS/YOURLS/pull/2747#issuecomment-689047797):

1. Rename your `admin/` directory to a secret name, e.g. `OMGSECRETURL/`.
2. Create `user/cache.php` (loaded extremely early, before plugins) with:

   ```php
   <?php
   yourls_add_filter('admin_url', 'custom_admin_url');
   function custom_admin_url($url) {
       return str_replace('/admin/', '/OMGSECRETURL/', $url);
   }
   ```

This rewrites every YOURLS-generated admin URL to your secret name so links keep working, while crawlers and brute-forcers can't find the login form at the conventional path.

> 正如插件配置页说的那样，推荐隐藏修改后台入口，参见[【这里】](https://github.com/YOURLS/YOURLS/pull/2747#issuecomment-689047797)
>
> As written in the plugin configuration page, it is recommended to hide the modified backend entry, see [[here]](https://github.com/YOURLS/YOURLS/pull/2747#issuecomment-689047797)

# Compatibility | 兼容性

| YOURLS  | PHP            | Status |
|:-------:|:--------------:|:------:|
| 1.7+    | 7.4 / 8.0–8.5  | ✓ Supported (v2.0.0) |
| 1.10+   | 8.0–8.5        | ✓ Recommended |
| 1.10+   | (v1.x plugin)  | :x: Login broken — upgrade to v2.0.0 |

# License

GPL-3.0. See [LICENSE](LICENSE).

---

PS: YOURLS最新版的汉化也是我，可以访问[我的翻译仓库](https://github.com/taozhiyu/yourls-translation-zh_CN)
