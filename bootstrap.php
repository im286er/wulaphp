<?php
/**
 * Project:     Wulaphp: another mvc framework of php based on php 5.6.0+ .
 * File:        bootstrap.php
 *
 * 此文件用于引导wulaphp framework.
 *
 * @link      https://www.wulaphp.com/
 * @author    leo <windywany@163.com>
 * @package   wulaphp
 * @version   1.6.0
 * @since     1.0.0
 */

use wulaphp\app\App;
use wulaphp\cache\RtCache;

if (version_compare('5.6.0', phpversion(), '>')) {
	die (sprintf('Your php version is %s,but wulaphp required  PHP 5.6.0 or higher', phpversion()));
}
define('WULA_STARTTIME', microtime(true));
defined('APPROOT') or die ('please define APPROOT');
defined('WWWROOT') or die ('please define WWWROOT');
define('WULA_VERSION', '1.6.0');
define('WULA_RELEASE', 'rc');
/* 常用目录定义 */
define('DS', DIRECTORY_SEPARATOR);
define('WULA_ROOT', __DIR__ . DS);
defined('MODULE_DIR') or define('MODULE_DIR', 'modules');
defined('THEME_DIR') or define('THEME_DIR', 'themes');
defined('CONF_DIR') or define('CONF_DIR', 'conf');
defined('LIBS_DIR') or define('LIBS_DIR', 'includes');
defined('EXTENSION_DIR') or define('EXTENSION_DIR', 'extensions');
defined('WWWROOT_DIR') or define('WWWROOT_DIR', '/');
defined('PUBLIC_DIR') or define('PUBLIC_DIR', 'wwwroot');
defined('ASSETS_DIR') or define('ASSETS_DIR', 'assets');
defined('VENDOR_DIR') or define('VENDOR_DIR', 'assets');
defined('TMP_DIR') or define('TMP_DIR', 'tmp');
defined('LOGS_DIR') or define('LOGS_DIR', 'logs');
define('WEB_ROOT', WWWROOT);//alias of WWWROOT
define('EXTENSIONS_PATH', APPROOT . EXTENSION_DIR . DS);
define('LIBS_PATH', APPROOT . LIBS_DIR . DS);
define('TMP_PATH', APPROOT . TMP_DIR . DS);
define('CONFIG_PATH', APPROOT . CONF_DIR . DS);
define('MODULES_PATH', WWWROOT . MODULE_DIR . DS);
define('MODULE_ROOT', MODULES_PATH);
define('THEME_PATH', WWWROOT . THEME_DIR . DS);
define('LOGS_PATH', APPROOT . LOGS_DIR . DS);
defined('MODULE_LOADER_CLASS') or define('MODULE_LOADER_CLASS', 'wulaphp\app\ModuleLoader');
defined('EXTENSION_LOADER_CLASS') or define('EXTENSION_LOADER_CLASS', 'wulaphp\app\ExtensionLoader');
defined('CONFIG_LOADER_CLASS') or define('CONFIG_LOADER_CLASS', 'wulaphp\conf\ConfigurationLoader ');
define('DEBUG_OFF', 1000);
define('DEBUG_ERROR', 400);
define('DEBUG_WARN', 300);
define('DEBUG_INFO', 200);
define('DEBUG_DEBUG', 100);
if (!defined('APP_MODE')) {
	if (isset($_SERVER['APPMODE']) && $_SERVER['APPMODE']) {
		define('APP_MODE', $_SERVER['APPMODE']);
	} else {
		define('APP_MODE', 'dev');
	}
}
if (@ini_get('register_globals')) {
	die ('please close "register_globals" in php.ini file.');
}
if (defined('MAX_RUNTIME_LIMIT')) {
	set_time_limit(intval(MAX_RUNTIME_LIMIT));
}
if (!defined('RUNTIME_MEMORY_LIMIT')) {
	define('RUNTIME_MEMORY_LIMIT', '128M');
}
if (function_exists('memory_get_usage') && (( int )@ini_get('memory_limit') < abs(intval(RUNTIME_MEMORY_LIMIT)))) {
	@ini_set('memory_limit', RUNTIME_MEMORY_LIMIT);
}
if (!function_exists('mb_internal_encoding')) {
	die ('mb_string extension is required!');
}
if (!function_exists('json_decode')) {
	die ('json extension is required!');
}
if (!function_exists('spl_autoload_register')) {
	die ('SPL extension is required!');
}
if (!function_exists('curl_init')) {
	die ('curl extension is required!');
}
@ob_start();
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
@ini_set('session.bug_compat_warn', 0);
@ini_set('session.bug_compat_42', 0);
/** @global string[] $_wula_classpath none-namespace classpath. */
global $_wula_classpath;
/** @global  string[] $_wula_namespace_classpath psr-4 classpath. */
global $_wula_namespace_classpath;

$_wula_namespace_classpath [] = WULA_ROOT;
if (is_dir(EXTENSIONS_PATH)) {
	$_wula_namespace_classpath [] = EXTENSIONS_PATH;
}
if (is_dir(WEB_ROOT . VENDOR_DIR)) {
	$_wula_namespace_classpath [] = WEB_ROOT . VENDOR_DIR . DS;
}
$_wula_namespace_classpath [] = WULA_ROOT . 'vendors' . DS;
$_wula_classpath []           = WULA_ROOT . 'vendors' . DS;
include WULA_ROOT . 'wulaphp/conf/Configuration.php';
include WULA_ROOT . 'wulaphp/conf/CacheConfiguration.php';
include WULA_ROOT . 'wulaphp/conf/ClusterConfiguration.php';
include WULA_ROOT . 'wulaphp/conf/RedisConfiguration.php';
include WULA_ROOT . 'wulaphp/conf/BaseConfigurationLoader.php';
include WULA_ROOT . 'wulaphp/conf/ConfigurationLoader.php';
include WULA_ROOT . 'wulaphp/cache/Cache.php';
include WULA_ROOT . 'wulaphp/cache/ApcCacher.php';
include WULA_ROOT . 'wulaphp/cache/YacCache.php';
include WULA_ROOT . 'wulaphp/cache/XCacheCacher.php';
include WULA_ROOT . 'wulaphp/cache/RedisCache.php';
include WULA_ROOT . 'wulaphp/cache/MemcachedCache.php';
include WULA_ROOT . 'wulaphp/cache/RtCache.php';
include WULA_ROOT . 'wulaphp/util/ObjectCaller.php';
spl_autoload_register(function ($clz) {
	global $_wula_classpath, $_wula_namespace_classpath;
	$key      = $clz . '.class';
	$clz_file = RtCache::get($key);
	if (is_file($clz_file)) {
		@include $clz_file;

		return;
	}
	if (strpos($clz, '\\') > 0) {
		$clzf = str_replace('\\', DS, $clz);
		foreach ($_wula_namespace_classpath as $cp) {
			$clz_file = $cp . $clzf . '.php';
			if (is_file($clz_file)) {
				RtCache::add($key, $clz_file);
				@include $clz_file;

				return;
			}
		}
		//从模块加载
		$clz_file = App::loadClass($clz);
		if ($clz_file && is_file($clz_file)) {
			RtCache::add($key, $clz_file);
			@include $clz_file;

			return;
		}
	}
	foreach ($_wula_classpath as $path) {
		$clz_file = $path . DS . $clz . '.php';
		if (is_file($clz_file)) {
			RtCache::add($key, $clz_file);
			@include $clz_file;

			return;
		}
	}
	fire('loader\loadClass', $clz);
});
require WULA_ROOT . 'includes/common.php';
if (is_file(LIBS_PATH . 'common.php')) {
	require LIBS_PATH . 'common.php';
}
set_exception_handler('wula_exception_handler');
register_shutdown_function('wula_shutdown_function');
App::start();
define('WULA_BOOTSTRAPPED', microtime(true));
fire('wula\bootstrapped');
//end of bootstrap.php