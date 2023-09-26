<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Edwiser RemUI
 * @package   auth_edwiserbridge
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/markdown/MarkdownInterface.php');
require_once($CFG->libdir . '/markdown/Markdown.php');
// require_once($CFG->dirroot . '/theme/remui/classes/controller/LicenseController.php');

define('EB_PLUGINS_LIST', "https://edwiser.org/edwiserupdates.json");
define('EB_PLUGIN_UPDATE', "https://edwiser.org/edwiserdemoimporter/bridge-free-plugin-info.json");

use core_plugin_manager;
use moodle_exception;
use Michelf\MarkDown;
use core_component;
use html_writer;
use ZipArchive;
use moodle_url;
use Exception;
use stdClass;
use curl;

/**
 * RemUI one click update class
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update {

    /**
     * Edwiser plugins list
     * @var array
     */
    public $plugins = [];

    /**
     * Error occured while fetching update details
     * @var array
     */
    public $errors = [];

    /**
     * Refresh update cache
     * @var boolean
     */
    public $refresh = false;

    /**
     * Cache object
     * @var null
     */
    public static $cache = null;

    /**
     * Cache file url
     *
     * @var string
     */
    public static $cacheurl = null;


    /**
     * Thin wrapper for the core's download_file_content() function.
     *
     * @param string $url    URL to the file
     * @param string $tofile full path to where to store the downloaded file
     *
     * @return bool
     */
    protected function download_file_content($url, $tofile) {
        // Prepare the parameters for the download_file_content() function.
        $headers = null;
        $postdata = null;
        $fullresponse = false;
        $timeout = 300;
        $connecttimeout = 20;
        $skipcertverify = false;
        $tofile = $tofile;
        $calctimeout = false;
        return download_file_content(
            $url,
            $headers,
            $postdata,
            $fullresponse,
            $timeout,
            $connecttimeout,
            $skipcertverify,
            $tofile,
            $calctimeout
        );
    }

    /**
     * Download the ZIP file with the plugin package from the given location
     *
     * @param string $url    URL to the file
     * @param string $tofile full path to where to store the downloaded file
     *
     * @return bool false on error
     */
    protected function download_plugin_zip_file($url, $tofile) {

        $checkurl = str_replace('download', 'verify-package', $url);

        $curl = new curl();
        $response = $curl->get($checkurl);

        if ($response) {
            $response = json_decode($response, true);
            if ((isset($response['data']) && $response['data']['status'] == 404) ||
                (isset($response['error']) && $response['error'] == true)) {
                $this->errors[] = get_string('errorfetching', 'auth_edwiserbridge', $response['message']);
                return false;
            }
            $status = $this->download_file_content($url, $tofile);
        } else {
            $status = false;
        }

        if (!$status) {
            $this->errors[] = get_string('errorfetching', 'auth_edwiserbridge', $url);
            @unlink($tofile);
            return false;
        }

        return true;
    }

    /**
     * Obtain the plugin ZIP file from the given URL
     *
     * The caller is supposed to know both downloads URL and the MD5 hash of
     * the ZIP contents in advance, typically by using the API requests against
     * the plugins directory.
     *
     * @param object $pluginman plugin manager object
     * @param string $url       url of plugin file
     * @param string $name      name with component of plugin
     *
     * @return string|bool full path to the file, false on error
     */
    public function get_remote_plugin_zip($pluginman, $url, $name) {
        global $CFG;

        if (!empty($CFG->disableupdateautodeploy)) {
            return false;
        }

        // Sanitize and validate the URL.
        $url = str_replace(array("\r", "\n"), '', $url);

        if (!preg_match('|^https?://|i', $url)) {
            $this->errors[] = 'Error fetching plugin ZIP: unsupported transport protocol: '.$url;
            return false;
        }

        $pluginman->zipdirectory = make_temp_directory('core_plugin/code_manager').'/distfiles/';

        // The cache location for the file.
        $distfile = $pluginman->zipdirectory.$name.'.zip';
        if (file_exists($distfile)) {
            return $distfile;
        }

        // Download the file into a temporary location.
        $tempdir = make_request_directory();
        $tempfile = $tempdir.'/plugin.zip';
        $result = $this->download_plugin_zip_file($url, $tempfile);

        if (!$result) {
            return false;
        }

        $md5 = md5_file($tempfile);

        // If the file is empty, something went wrong.
        if ($md5 === 'd41d8cd98f00b204e9800998ecf8427e') {
            return false;
        }

        // Store the file in our cache.
        if (!rename($tempfile, $distfile)) {
            return false;
        }

        return $distfile;
    }

    /**
     * Get plugin details from version.php file
     *
     * @param string $path        path of plugin
     * @param array  $zipcontents zip file contents
     *
     * @return stdClass|bool   plugin details
     */
    public function get_plugin_details($path, $zipcontents) {

        foreach ($zipcontents as $file => $status) {
            if (!$status) {
                return false;
            }
        }
        $root = current(array_keys($zipcontents));
        $root = explode('/', $root)[0];
        $file = $root . '/version.php';
        if (isset($zipcontents[$file]) && $zipcontents[$file] == 1 && file_exists($path . '/' . $file)) {
            $plugin = new stdClass;
            require_once($path . '/' . $file);
            return $plugin;
        }
        return false;
    }

    /**
     * Unzip zip file of plugin file and return its content
     * @param  object $pluginman Plugin manager
     * @param  string $zip       Zip file path
     * @param  string $temp      Temporary path
     * @param  string $root      Root directory path
     * @return array             Zip file content array
     */
    public function unzip_plugin_file($pluginman, $zip, $temp, $root) {
        ini_set('log_errors', 'Off');
        $contents = $pluginman->unzip_plugin_file($zip, $temp, $root);
        ini_set('log_errors', 'On');
        return $contents;
    }

    /**
     * Verify zip file is valid
     *
     * @param object $pluginman core plugin manager
     * @param string $zip       zip file
     * @param string $temp      temporary directory path
     * @param string $name      name of zip file
     *
     * @return bool         True is zip file is valid
     */
    public function verify_zip($pluginman, $zip, $temp, $name) {

        $zipcontents = $this->unzip_plugin_file($pluginman, $zip, $temp, $name);

        if (empty($zipcontents)) {
            $this->errors[] = get_string('invalidzip', 'auth_edwiserbridge', $name);
            return false;
        }

        $zipcount = 0;
        // Check all files from zip is ok and has zip inside zip.
        foreach ($zipcontents as $file => $status) {
            if (!$status) {
                $this->errors[] = get_string('invalidzip', 'auth_edwiserbridge', $name);
                return false;
            }
            if (stripos($file, ".zip") !== false) {
                $zipcount++;
                continue;
            }
            if (stripos($file, ".pdf") !== false || stripos($file, "readme") !== false) {
                unset($zipcontents[$file]);
            }
        }

        // If count is different means only one plugin file is there.
        // Else zip contains multiple plugins.
        if ($zipcount != count($zipcontents)) {
            $plugin = $this->get_plugin_details($temp, $zipcontents);
            if (!$plugin) {
                $this->errors[] = get_string('unabletoloadplugindetails', 'auth_edwiserbridge', $name);
            }
            return [$plugin];
        }

        $zipserror = false;
        $zips = $zipcontents;
        foreach (array_keys($zips) as $file) {
            $name1 = str_replace('.zip', '', $file);
            $path = make_request_directory();
            $zipcontents = $this->unzip_plugin_file($pluginman, $temp . '/' . $file, $path, $name1);
            if (empty($zipcontents)) {
                $this->errors[] = get_string('invalidzip', 'auth_edwiserbridge', $name . '  ->  ' . $name1);
                return false;
            }

            $plugin = $this->get_plugin_details($path, $zipcontents);
            unset($zips[$file]);
            if (!$plugin) {
                $this->errors[] = get_string('unabletoloadplugindetails', 'auth_edwiserbridge', $name . '  ->  ' . $name1);
                $zipserror = true;
            } else {
                $zips[$temp . '/' . $file] = $plugin;
            }
        }
        return $zipserror == true ? false : $zips;
    }


    /**
     * Fetch plugin update from edwiser.org or from cache
     * @return array plugins and errors list
     */
    public function fetch_plugins_update() {
        global $CFG;

        $plugin_data = get_config('auth_edwiserbridge', 'edwiserbridge_update_data');

        return array('auth_edwiserbridge' => json_decode($plugin_data));
    }

    /**
     * Validate zip file before installing plugin
     *
     * @param core_plugin_manager      $pluginman core plugin manager object
     * @param \core\update\remote_info $plugin    plugin information
     * @param string                   $zipfile   zip file path
     * @param bool                     $silent    true if dont wanna show debugg error
     *
     * @return bool                 validation result
     */
    private function validate_plugin_zip($pluginman, $plugin, $zipfile, $silent) {
        global $CFG, $OUTPUT;

        $ok = get_string('ok', 'core');

        $silent or mtrace(get_string('packagesvalidating', 'core_plugin', $plugin->component), ' ... ');

        list($plugintype, $pluginname) = core_component::normalize_component($plugin->component);

        $tmp = make_request_directory();
        $zipcontents = $this->unzip_plugin_file($pluginman, $zipfile, $tmp, $pluginname);

        if (empty($zipcontents)) {
            $silent or mtrace(get_string('error'));
            $silent or mtrace(get_string('unabletounzip', 'auth_edwiserbridge', $zipfile));
            return false;
        }

        $validator = \core\update\validator::instance($tmp, $zipcontents);
        $validator->assert_plugin_type($plugintype);
        $validator->assert_moodle_version($CFG->version);

        // TODO Check for missing dependencies during validation.
        $result = $validator->execute();
        $result ? ($silent or mtrace($ok)) : ($silent or mtrace(get_string('error')));

        if (!$silent) {
            foreach ($validator->get_messages() as $message) {
                if ($message->level === $validator::WARNING || $message->level === $validator::ERROR and !CLI_SCRIPT) {
                    mtrace('  <strong>['.$validator->message_level_name($message->level).']</strong>', ' ');
                } else {
                    mtrace('  ['.$validator->message_level_name($message->level).']', ' ');
                }

                mtrace($validator->message_code_name($message->msgcode), ' ');

                $info = $validator->message_code_info($message->msgcode, $message->addinfo);
                if ($info) {
                    mtrace('['.s($info).']', ' ');
                } else if (is_string($message->addinfo)) {
                    mtrace('['.s($message->addinfo, true).']', ' ');
                } else {
                    mtrace('['.s(json_encode($message->addinfo, true)).']', ' ');
                }

                if ($icon = $validator->message_help_icon($message->msgcode)) {
                    if (CLI_SCRIPT) {
                        mtrace(
                            PHP_EOL.'  ^^^ '.get_string('help').': '. get_string(
                                $icon->identifier.'_help',
                                $icon->component
                            ),
                            ''
                        );
                    } else {
                        mtrace($OUTPUT->render($icon), ' ');
                    }
                }
                mtrace(PHP_EOL, '');
            }
        }
        if (!$result) {
            $silent or mtrace(get_string('packagesvalidatingfailed', 'core_plugin'));
        }
        $silent or mtrace(PHP_EOL, '');
        return $result;
    }

    /**
     * Perform the installation of plugins.
     *
     * If used for installation of remote plugins from the Edwiser Plugins
     * directory, the $plugins must be list of {@link \core\update\remote_info}
     * object that represent installable remote plugins. The caller can use
     * {@link self::filter_installable()} to prepare the list.
     *
     * If used for installation of plugins from locally available ZIP files,
     * the $plugins should be list of objects with properties ->component and
     * ->zipfilepath.
     *
     * The method uses {@link mtrace()} to produce direct output and can be
     * used in both web and cli interfaces.
     *
     * @param  \core\update\remote_info $plugin    list of plugins
     * @param  bool                     $confirmed should the files be really deployed into the dirroot?
     * @param  bool                     $silent    hide debugg errors is set true
     *
     * @return bool                                 true on success
     */
    public function install_plugin(\core\update\remote_info $plugin, $confirmed, $silent) {
        global $CFG;

        $pluginman = core_plugin_manager::instance();
        if (!empty($CFG->disableupdateautodeploy)) {
            return false;
        }

        $ok = get_string('ok', 'core');

        // Let admins know they can expect more verbose output.
        $silent or mtrace(get_string('packagesdebug', 'core_plugin'), PHP_EOL);

        // Download all ZIP packages if we do not have them yet.
        $zip = array();

        $silent or mtrace(get_string('packagesdownloading', 'core_plugin', $plugin->component), ' ... ');

        if (!isset($plugin->version->url) || trim($plugin->version->url) == '') {
            $zip = false;
            $errormsg = get_string('cannotdownloadzipfile', 'core_error');
            if (!empty($plugin->version->msg)) {
                $tag = count($plugin->version->msg) > 1 ? 'ol' : 'ul';
                $errormsg = html_writer::start_tag($tag);
                foreach ($plugin->version->msg as $msg) {
                    $errormsg .= html_writer::tag('li', $msg);
                }
                $errormsg .= html_writer::end_tag($tag);
            }
        } else {
            $url = $plugin->version->url;
            $zip = $this->get_remote_plugin_zip(
                $pluginman,
                $url,
                $plugin->component
            );
        }
        if (!$zip) {
            $silent or mtrace(get_string('errorfetching', 'auth_edwiserbridge', ''));
            return false;
        }

        $silent or mtrace($ok);

        $temp = make_request_directory();
        $zips = $this->verify_zip($pluginman, $zip, $temp, $plugin->component);
        $zipfile = $zip;

        if (!$zips) {
            $silent or mtrace(get_string('unabletounzip', 'auth_edwiserbridge', $zipfile), PHP_EOL);
            return false;
        }
        if (count($zips) == 1) {
            $zips[$zipfile] = $zips[0];
            unset($zips[0]);
        } else {
            unlink($zip);
        }

        $checks = true;
        // Validate all downloaded packages.
        foreach ($zips as $zipfile => $plugindetails) {
            if ($plugindetails->component != $plugin->component) {
                unset($zips[$zipfile]);
                unlink($zipfile);
                continue;
            }
            $checks &= $this->validate_plugin_zip($pluginman, $plugindetails, $zipfile, $silent);
        }
        if (!$checks) {
            return;
        }
        if (!$confirmed) {
            return true;
        }

        if (!is_array($zips)) {
            $zips = [];
            $zips[$zip] = $plugin->component;
        }

        foreach ($zips as $zipfile => $plugin) {
            // Extract all ZIP packs do the dirroot.
            $silent or mtrace(get_string('packagesextracting', 'core_plugin', $plugin->component), ' ... ');
            list($plugintype, $pluginname) = core_component::normalize_component($plugin->component);

            $target = $pluginman->get_plugintype_root($plugintype);
            $plugininfo = $pluginman->get_plugin_info($plugin->component);
            if (file_exists($target.'/'.$pluginname) && $plugininfo) {
                $pluginman->remove_plugin_folder($plugininfo);
            }
            if (!$this->unzip_plugin_file($pluginman, $zipfile, $target, $pluginname)) {
                $silent or mtrace(get_string('error'));
                $silent or mtrace(get_string('unabletounzip', 'auth_edwiserbridge', $zipfile), PHP_EOL);
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                }
                return false;
            }
        }

        $silent or mtrace($ok);
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return true;
    }

    /**
     * Display the continue / cancel widgets for the plugins management pages.
     *
     * @param null|moodle_url $continue URL for the continue button, should it be displayed
     * @param null|moodle_url $cancel URL for the cancel link, defaults to the current page
     * @return string HTML
     */
    public function plugins_management_confirm_buttons(
        moodle_url $continue = null,
        moodle_url $download = null,
        moodle_url $cancel = null
    ) {
        global $OUTPUT;

        $out = html_writer::start_div('plugins-management-confirm-buttons');

        if (!empty($continue)) {
            $out .= $OUTPUT->single_button($continue, get_string('continue'), 'post', array('class' => 'continue'));
        }

        if (!empty($download)) {
            $out .= $OUTPUT->single_button($download, get_string('download'), 'post', array('class' => 'download'));
        }

        if (empty($cancel)) {
            $cancel = $this->page->url;
        }
        $out .= html_writer::div(html_writer::link($cancel, get_string('cancel')), 'cancel');

        return $out;
    }

    /**
     * Helper procedure/macro for installing remote pluginsat block/edwiser_site_monitor/plugin.php
     *
     * Does not return, always redirects or exits.
     *
     * @param \core\update\remote_info  $installable list of \core\update\remote_info
     * @param bool                      $confirmed   false: display the validation screen, true: proceed installation
     * @param string                    $heading     validation screen heading
     * @param mixed                     $continue    URL to proceed with installation at the validation screen
     * @param mixed                     $return      URL to go back on cancelling at the validation screen
     *
     * @return void
     */
    public function upgrade_install_plugin(
        \core\update\remote_info $installable,
        $confirmed,
        $heading='',
        $continue = null,
        $download = null,
        $return = null
    ) {
        global $CFG, $PAGE;

        if (empty($return)) {
            $return = $PAGE->url;
        }

        if (!empty($CFG->disableupdateautodeploy)) {
            redirect($return);
        }

        if (empty($installable)) {
            redirect($return);
        }

        if ($confirmed) {
            // Installation confirmed at the validation results page.
            if (!$this->install_plugin($installable, true, true)) {
                throw new moodle_exception('install_plugins_failed', 'core_plugin', $return);
            }

            // Always redirect to admin/index.php to perform the database upgrade.
            // Do not throw away the existing $PAGE->url parameters such as.
            // confirmupgrade or confirmrelease if $PAGE->url is a superset of the.
            // URL we must go to.
            $mustgoto = new moodle_url('/admin/index.php', array('cache' => 0, 'confirmplugincheck' => 0));
            if ($mustgoto->compare($PAGE->url, URL_MATCH_PARAMS)) {
                redirect($PAGE->url);
            } else {
                redirect($mustgoto);
            }

        } else {
            $output = $PAGE->get_renderer('core', 'admin');
            echo $output->header();
            if ($heading) {
                echo $output->heading($heading, 3);
            }
            echo html_writer::start_tag('pre', array('class' => 'plugin-install-console'));
            $validated = $this->install_plugin($installable, false, false);
            echo html_writer::end_tag('pre');
            if ($validated) {
                echo $this->plugins_management_confirm_buttons($continue, null, $return);
            } else {
                echo html_writer::start_tag('a', array('class' => 'btn btn-secondary', 'href' => $download));
                echo get_string('download', 'core');
                echo html_writer::end_tag('a');
                echo $this->plugins_management_confirm_buttons(null, null, $return);
            }
            echo $output->footer();
        }
    }

    /**
     * Dowload plugin file of requested plugin.
     * @param  object $plugin Plugin object
     * @return bool           Return false if unable to download
     */
    public function download_plugin($plugin) {
        $pluginman = core_plugin_manager::instance();

        if (!isset($plugin->package) || trim($plugin->package) == '') {
            $zip = false;
            $errormsg = get_string('cannotdownloadzipfile', 'core_error');
            if (!empty($plugin->msg)) {
                $tag = count($plugin->msg) > 1 ? 'ol' : 'ul';
                $errormsg = html_writer::start_tag($tag);
                foreach ($plugin->msg as $msg) {
                    $errormsg .= html_writer::tag('li', $msg);
                }
                $errormsg .= html_writer::end_tag($tag);
            }
        } else {
            $url = EB_PLUGIN_UPDATE . '/download/' . $plugin->package;
            $zip = $this->get_remote_plugin_zip(
                $pluginman,
                $url,
                $plugin->component
            );
        }
        if (!$zip) {
            return false;
        }

        $temp = make_request_directory();
        $zips = $this->verify_zip($pluginman, $zip, $temp, $plugin->component);
        if (count($zips) == 1) {
            $zips[$zip] = $zips[0];
            unset($zips[0]);
        } else {
            unlink($zip);
        }
        $zipfile = $zip;

        if (!$zips) {
            mtrace(get_string('error'));
            mtrace(get_string('unabletounzip', 'auth_edwiserbridge', $zipfile), PHP_EOL);
            return false;
        }
        $checks = true;
        // Validate all downloaded packages.
        foreach ($zips as $zipfile => $plugindetails) {
            if ($plugindetails->component != $plugin->component) {
                unset($zips[$zipfile]);
                unlink($zipfile);
                continue;
            }
            // Force download.
            send_file($zipfile, $plugin->component . '.zip', null , 0, false, true);
        }
    }

    /**
     * Get plugin update details for install update page
     * @param  array $params Plugin details parameter
     * @return array         Plugin update details
     */
    public function get_plugin_update($params) {
        $component = $params['installupdate'];
        $plugins = $this->fetch_plugins_update();
        if (!isset($plugins[$component])) {
            return false;
        }

        $plugin = $plugins[$component];

        if ($params['download'] == true) {
            $this->download_plugin($plugin);
        }

        return $plugin;
    }
}
