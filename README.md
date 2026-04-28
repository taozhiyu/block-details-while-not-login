# block-details-while-not-login

A plugin for [YOURLS](https://yourls.org/) that blocks the URL details/stats page while a visitor is not logged in.

> 🇨🇳 中文版本请见 [README.zh_CN.md](README.zh_CN.md)

## Features

### Multilingual

| English | 中文 |
|:--:|:--:|
| ![English UI](./imgs/1.png) | ![Chinese UI](./imgs/1_cn.png) |

> Currently, only Simplified Chinese is supported alongside English.

### Safety

| Before | Now |
|:--:|:--:|
| ![Before — public details](./imgs/2.png) | ![After — blocked](./imgs/3.png) |
| :x: Risk of brute-force attacks and malicious requests | :white_check_mark: Safe! |

### Customization

| ![English UI](./imgs/1.png) | ![Chinese UI](./imgs/1_cn.png) |
|:--:|:--:|

Custom reminder text is supported, and you can do much more with JavaScript injection.

> The image shows an example of jumping to the home page after 5 seconds.

## Usage

1. Install YOURLS.
2. Install this plugin into `user/plugins/`.
3. Activate the plugin from **Manage Plugins**.
4. Enable it from the plugin's configuration page.

> As written on the configuration page, it is also recommended to hide the admin entry point — see [YOURLS PR #2747, comment #689047797](https://github.com/YOURLS/YOURLS/pull/2747#issuecomment-689047797).

---

PS: I am also the maintainer of the latest Simplified Chinese translation of YOURLS. See [my translation repo](https://github.com/taozhiyu/yourls-translation-zh_CN).
