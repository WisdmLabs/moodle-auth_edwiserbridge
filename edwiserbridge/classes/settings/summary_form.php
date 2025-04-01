<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Summary form.
 * Functionality to Show plugin summary and license form.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_edwiserbridge\settings;
use moodleform;
use webservice;
use moodle_url;
use auth_edwiserbridge\local\eb_pro_license_controller;
// use core\plugin_manager as core_plugin_manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

/**
 * Defines the summary form for the Edwiser Bridge plugin.
 * This form is used to display the plugin summary and license information.
 */
class summary_form extends moodleform {
    
    /**
     * This method is responsible for defining the summary form for the Edwiser Bridge plugin. 
     * It sets up the form fields and handles the display of plugin summary and license information. 
     * The method retrieves various data points such as the plugin version, web service details, and license information, and populates the form accordingly. 
     * It also checks the web service user's capabilities and displays appropriate messages based on the findings.
     */
    public function definition() {
        global $CFG;
        $servicename   = '';
        $pluginsvdata  = $this->get_plugin_version_data();
        $mform         = $this->_form;

        $edwiser_bridge_last_created_token = get_config('auth_edwiserbridge', 'edwiser_bridge_last_created_token');
        $ebexistingserviceselect = get_config('auth_edwiserbridge', 'ebexistingserviceselect');

        $token         = !empty($edwiser_bridge_last_created_token) ? $edwiser_bridge_last_created_token : ' - ';
        $service       = !empty($ebexistingserviceselect) ? $ebexistingserviceselect : '';
        $missingcapmsg = '<span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003;</span>';
        $url           = $CFG->wwwroot . "/admin/webservice/service_users.php?id=$service";
        $functionspage = "<a href='$url' target='_blank'>here</a>";

        // Handle license request.
        $this->handle_license_action();

        // Check web service user have a capability to use the web service.
        $webservicemanager = new webservice();
        if (!empty($service)) {
            $allowedusers = $webservicemanager->get_ws_authorised_users($service);
            $usersmissingcaps = $webservicemanager->get_missing_capabilities_by_users($allowedusers, $service);
            $webservicemanager->get_external_service_by_id($service);
            foreach ($allowedusers as &$alloweduser) {
                if (!is_siteadmin($alloweduser->id) && array_key_exists($alloweduser->id, $usersmissingcaps)) {
                    $missingcapmsg = "<span class='summ_error'>" . get_string('incomplete_caps_error', 'auth_edwiserbridge') . $functionspage . get_string('incomplete_caps_error_know_more', 'auth_edwiserbridge') . "</span>";
                }
            }

            // Get the web service name.
            $serviceobj = $webservicemanager->get_external_service_by_id($service);
            if (isset($serviceobj->name)) {
                $servicename = $serviceobj->name;
            }

            // If service is empty then show just the blank text with dash.
            $tokenfield = $token;
            if (!empty($service)) {
                // If the token available then show the token.
                $tokenfield = auth_edwiserbridge_create_token_field($service, $token);
            }
            $eb_connection_settings = auth_edwiserbridge_get_connection_settings();
            if(isset($eb_connection_settings['eb_connection_settings'])){
                $sites = $eb_connection_settings['eb_connection_settings'];
                foreach ($sites as $value) {
                    $wp_url = $value['wp_url'];
                    $token  = $value['wp_token'];
                    break;
                }
            }
        } else {
            $missingcapmsg = "<span class='summ_error'>" . get_string('incomplete_caps_error', 'auth_edwiserbridge') . $functionspage . get_string('incomplete_caps_error_know_more', 'auth_edwiserbridge') . "</span>";
        }

        $summaryarray = [
            'edwiser_bridge_plugin_summary'  => [
                '' => [
                    'label'          => '',
                    'expected_value' => 'static',
                    'value'          => $this->get_plugin_fetch_link(),
                ],
                'mdl_edwiser_bridge' => [
                    'label'          => '<strong>' . get_string('mdl_edwiser_bridge_lbl', 'auth_edwiserbridge'). '</strong>',
                    'expected_value' => 'static',
                    'value'          => $pluginsvdata['edwiserbridge'],
                ],
                'eb_pro_license' => [
                    'label'          => '<strong>'
                                        . get_string('eb_pro_license_lbl', 'auth_edwiserbridge')
                                        . '</strong><p>'
                                        . get_string('eb_pro_license_desc', 'auth_edwiserbridge')
                                        . '<a href="https://edwiser.org/my-account/">'
                                        . get_string('here', 'auth_edwiserbridge') . '</a></p>',
                    'expected_value' => 'static',
                    'value'          => $this->get_license_data(),
                ],
            ],
            'summary_setting_section' => [
                'webserviceprotocols' => [
                    'label'          => get_string('sum_rest_protocol', 'auth_edwiserbridge'),
                    'expected_value' => 'dynamic',
                    'value'          => 1,
                    'error_msg'      => get_string('sum_error_rest_protocol', 'auth_edwiserbridge'),
                    'error_link'     => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=settings",
                ],
                'enablewebservices'   => [
                    'expected_value' => 1,
                    'label'          => get_string('sum_web_services', 'auth_edwiserbridge'),
                    'error_msg'      => get_string('sum_error_web_services', 'auth_edwiserbridge'),
                    'error_link'     => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=settings",
                ],
                'passwordpolicy'     => [
                    'expected_value' => 0,
                    'label'          => get_string('sum_pass_policy', 'auth_edwiserbridge'),
                    'error_msg'      => get_string('sum_error_pass_policy', 'auth_edwiserbridge'),
                    'error_link'     => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=settings",
                ],
                'extendedusernamechars' => [
                    'expected_value' => 1,
                    'label'          => get_string('sum_extended_char', 'auth_edwiserbridge'),
                    'error_msg'      => get_string('sum_error_extended_char', 'auth_edwiserbridge'),
                    'error_link'     => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=settings",
                ],
                'uptodatewebservicefunction' => [
                    'expected_value' => 'static',
                    'label'          => get_string('web_service_status', 'auth_edwiserbridge'),
                    'value'             => "<div id='web_service_status' data-serviceid='$service'>Checking...</div>",
                ],
                'webservicecap' => [
                    'expected_value' => 'static',
                    'label'          => get_string('web_service_cap', 'auth_edwiserbridge'),
                    'value'          => "<div id='web_service_status'>$missingcapmsg</div>",
                ],
            ],
            'summary_connection_section'  => [
                'url' => [
                    'label'          => get_string('mdl_url', 'auth_edwiserbridge'),
                    'expected_value' => 'static',
                    'value'          => '<div class="eb_copy_text_wrap" data> <span class="eb_copy_text" title="'
                        . get_string('click_to_copy', 'auth_edwiserbridge') . '">' . $CFG->wwwroot . '</span>'
                        . ' <span class="eb_copy_btn">' . get_string('copy', 'auth_edwiserbridge') . '</span></div>',

                ],
                'service_name' => [
                    'label'          => get_string('web_service_name', 'auth_edwiserbridge'),
                    'expected_value' => 'static',
                    'value'          => '<div class="eb_copy_text_wrap"> <span class="eb_copy_text" title="'
                        . get_string('click_to_copy', 'auth_edwiserbridge') . '">' . $servicename . '</span>'
                        . ' <span class="eb_copy_btn">' . get_string('copy', 'auth_edwiserbridge') . '</span></div>',
                ],
                'token' => [
                    'label'          => get_string('token', 'auth_edwiserbridge'),
                    'expected_value' => 'static',
                    'value'          => '<div class="eb_copy_text_wrap"> <span class="eb_copy_text" id="eb_wp_token" title="'
                        . get_string('click_to_copy', 'auth_edwiserbridge') . '">' . $token
                        . '</span> <span class="eb_copy_btn">' . get_string('copy', 'auth_edwiserbridge') . '</span></div>',
                ],
                'wp_url' => [
                    'label'          => get_string('wordpress_url', 'auth_edwiserbridge'),
                    'expected_value' => 'static',
                    'value'          => '<div class="eb_copy_text_wrap"> <span class="eb_copy_text" id="eb_wp_url" title="'
                        . get_string('click_to_copy', 'auth_edwiserbridge') . '">' . $wp_url . '</span>'
                        . ' <span class="eb_copy_btn">' . get_string('copy', 'auth_edwiserbridge') . '</span></div>'
                ],
                'testconnectionstatus' => [
                    'expected_value' => 'static',
                    'label'          => get_string('test_connection_status', 'auth_edwiserbridge'),
                    'value'          => '<div id="test_connection_status">' . get_string('checking', 'auth_edwiserbridge') . '</div>'
                ],
                'lang_code' => [
                    'label'          => get_string('lang_label', 'auth_edwiserbridge'),
                    'expected_value' => 'static',
                    'value'         => '<div class="eb_copy_text_wrap"> <span class="eb_copy_text" title="'
                        . get_string('click_to_copy', 'auth_edwiserbridge') . '">' . $CFG->lang
                        . '</span> <span class="eb_copy_btn">' . get_string('copy', 'auth_edwiserbridge') . '</span></div>',
                ],
            ],
        ];

        $html = '';

        foreach ($summaryarray as $sectionkey => $section) {
            $html .= '<div class="summary_section"> <div class="summary_section_title">'
                . get_string($sectionkey, 'auth_edwiserbridge') . '</div>';
            $html .= '<table class="summary_section_tbl">';

            foreach ($section as $key => $value) {
                $html .= "<tr id='$key'><td class='sum_label'>";
                $html .= $value['label'];
                $html .= '</td>';

                if ($value['expected_value'] === 'static') {
                    $html .= '<td class="sum_status">' . $value['value'] . '<td>';
                } else if ($value['expected_value'] === 'dynamic') {
                    if ($key == 'webserviceprotocols') {
                        $activewebservices = empty($CFG->webserviceprotocols) ? [] : explode(',', $CFG->webserviceprotocols);
                        if (!in_array('rest', $activewebservices)) {
                            $html .= '<td class="sum_status">
                                <span class="summ_error"> ' . $value['error_msg'] . '<a href="' . $value['error_link'] . '" target="_blank" >'
                                . get_string('here', 'auth_edwiserbridge') . '</a> </span>
                            </td>';
                            $error = 1;
                        } else {
                            $successmsg = get_string('settingdisabled', 'auth_edwiserbridge');
                            if ($value['expected_value']) {
                                $successmsg = get_string('settingenabled', 'auth_edwiserbridge');
                            }

                            $html .= '<td class="sum_status">
                                <span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003;
                                </span>
                                <span style="color: #7ad03a;"> ' . $successmsg . ' </span>
                            </td>';
                        }
                    }
                } else if (isset($CFG->$key) && $value['expected_value'] == $CFG->$key) {

                    $successmsg = get_string('settingdisabled', 'auth_edwiserbridge');
                    if ($value['expected_value']) {
                        $successmsg = get_string('settingenabled', 'auth_edwiserbridge');
                    }

                    $html .= '<td class="sum_status">
                                <span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003; </span>
                                <span style="color: #7ad03a;"> ' . $successmsg . ' </span>
                            </td>';
                } else {
                    $html .= '<td class="sum_status" id="' . $key . '">
                                <span class="summ_error"> ' . $value['error_msg'] . '<a href="' . $value['error_link']
                        . '" target="_blank" >' . get_string('here', 'auth_edwiserbridge') . '</a> </span>
                            </td>';
                    $error = 1;
                }
                $html .= '</td>
                        </tr>';
            }

            $html .= '</table>';
            $html .= ' </div>';
        }

        $mform->addElement(
            'html',
            $html
        );
    }

    /**
     * Get the URL for fetching plugin information.
     *
     * This function generates the URL for fetching the latest information about the Edwiser Bridge plugin,
     * including the current version and any available updates. The URL is constructed using the global
     * $CFG object, which contains the root URL of the Moodle installation.
     *
     * @return string The URL for fetching plugin information.
     */
    private function get_plugin_fetch_link() {
        global $CFG;
        $url = $CFG->wwwroot . '/auth/edwiserbridge/edwiserbridge.php?tab=summary&fetch_data=true';
        return "<a href='{$url}'><i class='fa fa-refresh'></i> "
            . get_string('mdl_edwiser_bridge_fetch_info', 'auth_edwiserbridge')
            . "</a>";
    }

    /**
     * Retrieves the version information for the Edwiser Bridge plugin.
     *
     * This function fetches the current version of the Edwiser Bridge plugin installed on the Moodle site,
     * as well as the latest available version from the remote server. It then constructs an array of version
     * information, including any available updates, and returns it.
     *
     * @return array An associative array containing the version information for the Edwiser Bridge plugin.
     */
    private function get_plugin_version_data() {
        $pluginsdata = [];
        $pluginman   = \core_plugin_manager::instance();

        $authplugin                 = $pluginman->get_plugins_of_type('auth');
        $pluginsdata['edwiserbridge'] = get_string('mdl_edwiser_bridge_txt_not_avbl', 'auth_edwiserbridge');
        if (isset($authplugin['edwiserbridge'])) {
            $pluginsdata['edwiserbridge'] = $authplugin['edwiserbridge']->release;
        }

        $fetchdata = optional_param('tab', '', PARAM_RAW);
        $fetchdata  = 'true' === $fetchdata ? true : false;
        $remotedata = $this->get_remote_plugins_data($fetchdata);

        $versioninfo = [
            'edwiserbridge'        => $pluginsdata['edwiserbridge'] . "<span style='padding-left:1rem;color:limegreen;'>"
                . get_string('mdl_edwiser_bridge_txt_latest', 'auth_edwiserbridge') . " </span>",
        ];

        if (false !== $remotedata) {
            if (
                isset($remotedata->moodle_edwiser_bridge->version) &&
                version_compare($pluginsdata['edwiserbridge'], $remotedata->moodle_edwiser_bridge->version, "<")
            ) {
                global $CFG;
                $updateurl = new moodle_url(
                    $CFG->wwwroot . '/auth/edwiserbridge/install_update.php',
                    ['installupdate' => 'auth_edwiserbridge', 'sesskey' => sesskey()]
                );

                $versioninfo['edwiserbridge'] = $pluginsdata['edwiserbridge'] . "<span  style='padding-left:1rem;'>("
                    . $remotedata->moodle_edwiser_bridge->version . ")<a href='"
                    . $remotedata->moodle_edwiser_bridge->url . "' title='"
                    . get_string('mdl_edwiser_bridge_txt_download_help', 'auth_edwiserbridge') . "'>"
                    . get_string('mdl_edwiser_bridge_txt_download', 'auth_edwiserbridge') . "</a> "
                    . get_string('plugin_or', 'auth_edwiserbridge') . " <a href='" . $updateurl . "' title='"
                    . get_string('plugin_update_help_text', 'auth_edwiserbridge') . "' >"
                    . get_string('plugin_update', 'auth_edwiserbridge') . "</a></span>";

                auth_edwiserbridge_prepare_plugin_update_notification($remotedata->moodle_edwiser_bridge);
            }
        }
        return $versioninfo;
    }

    /**
     * Returns the remote plugin data.
     *
     * @param bool $fetchdata Whether to fetch the data from the remote server.
     * @return object The remote plugin data.
     */
    private function get_remote_plugins_data($fetchdata) {
        global $CFG;
        include_once($CFG->libdir . '/filelib.php'); // Ensure Moodle's curl class is available.

        $data = get_config('auth_edwiserbridge', 'edwiserbridge_plugins_versions');
        $requestdata = true;

        if ($data || $fetchdata) {
            $data = json_decode($data);
            if (isset($data->data) && isset($data->time) && $data->time > time()) {
                $output = json_decode($data->data);
                $requestdata = false;
            }
        }

        if ($requestdata) {
            // Use Moodle's curl class.
            $curl = new \curl();

            // Construct a user agent string.
            $useragent = 'Moodle/' . $CFG->version . ' (' . $CFG->wwwroot . ') Edwiser Bridge Update Checker';

            // Set headers and options.
            $curl->setHeader('User-Agent: ' . $useragent);
            $options = [
                'CURLOPT_RETURNTRANSFER' => true,
                'CURLOPT_TIMEOUT' => 100,
                'CURLOPT_SSL_VERIFYHOST' => false,
                'CURLOPT_SSL_VERIFYPEER' => false,
            ];

            // Execute GET request.
            $url = "https://edwiser.org/edwiserdemoimporter/bridge-free-plugin-info.json";
            $response = $curl->get($url, [], $options);

            // Check the response.
            $httpcode = $curl->info['http_code'];
            if ($httpcode === 200) {
                $data = [
                    'time' => time() + (60 * 60 * 24),
                    'data' => $response,
                ];
                set_config('edwiserbridge_plugins_versions', json_encode($data), 'auth_edwiserbridge');
            }

            $output = json_decode($response);
        }

        return $output;

    }

    /**
     * Retrieves the license data for the Edwiser Bridge plugin.
     *
     * This function fetches the license key and status from the Moodle database and
     * prepares the data for rendering a license form template.
     *
     * @return string The rendered license form template.
     */
    private function get_license_data() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $pluginslug = 'moodle_edwiser_bridge';

        // Get License Key.
        $licensekey = get_config('auth_edwiserbridge', 'edd_' . $pluginslug . '_license_key');

        // Get License Status.
        $licensestatus = get_config('auth_edwiserbridge', 'edd_' . $pluginslug . '_license_status');

        // Prepare data for Mustache template.
        $templatecontext = [
            'licensekey' => $licensekey,
            'licensestatus' => $licensestatus,
            'isvalidlicense' => ($licensestatus == 'valid'),
            'ebactive' => get_string('eb_active', 'auth_edwiserbridge'),
            'deactivate' => get_string('deactivate', 'auth_edwiserbridge'),
            'activate' => get_string('activate', 'auth_edwiserbridge'),
        ];

        return $renderer->render_from_template('auth_edwiserbridge/license_form', $templatecontext);
    }

    /**
     * Handles the license activation and deactivation actions.
     *
     * This function retrieves the license key and activation/deactivation parameters
     * from the request, and then uses the eb_pro_license_controller to perform the
     * corresponding license action.
     */
    private function handle_license_action() {
        $licensekey = optional_param('eb_license_key', '', PARAM_RAW);
        $activatelicense = optional_param('eb_license_activate', '', PARAM_RAW);
        $deactivatelicense = optional_param('eb_license_deactivate', '', PARAM_RAW);

        $licensecontroller = new eb_pro_license_controller();
        if ($activatelicense) {
            $licensecontroller->activate_license($licensekey);
        } else if ($deactivatelicense) {
            $licensecontroller->deactivate_license();
        }
    }
}
