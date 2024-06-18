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
 * Setup Wizard.
 * Functionality to manage setup wizard.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge;
/**
 * Handles API requests and response from WordPress.
 *
 * @package     auth_edwiserbridge
 * @copyright   2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_wizard {

    /**
     * Get setup wizard steps.
     *
     * @return array $steps Setup wizard steps.
     */
    public function eb_setup_wizard_get_steps() {

        // Loop through the steps.
        // Ajax call for each of the steps and save.
        // step change logic.
        // load data on step change.
        $steps = [
            'installation_guide' => [
                'name'        => 'Edwiser Bridge FREE plugin installation guide',
                'title'       => 'Edwiser Bridge FREE plugin installation guide',
                'function'    => 'eb_setup_installation_guide',
                'parent_step' => 'installation_guide',
                'priority'    => 10,
                'sub_step'    => 0,
            ],
            'mdl_plugin_config' => [
                'name'        => 'Edwiser Bridge Moodle Plugin configuration',
                'title'       => 'Edwiser Bridge - Moodle Plugin configuration',
                'function'    => 'eb_setup_plugin_configuration',
                'parent_step' => 'mdl_plugin_config',
                'priority'    => 20,
                'sub_step'    => 0,
            ],
            'web_service' => [
                'name'        => 'Setting up Web service',
                'title'       => 'Setting up Web service',
                'function'    => 'eb_setup_web_service',
                'parent_step' => 'web_service',
                'priority'    => 30,
                'sub_step'    => 0,
            ],
            'wordpress_site_details' => [
                'name'        => 'WordPress site details',
                'title'       => 'WordPress site details',
                'function'    => 'eb_setup_wordpress_site_details',
                'parent_step' => 'wordpress_site_details',
                'priority'    => 40,
                'sub_step'    => 0,
            ],
            'check_permalink' => [
                'name'        => 'Check permalink structure',
                'title'       => 'Check permalink structure',
                'function'    => 'eb_setup_check_permalink',
                'parent_step' => 'wordpress_site_details',
                'priority'    => 50,
                'sub_step'    => 0,
            ],
            'test_connection' => [
                'name'        => 'Test connection between Moodle and WordPress',
                'title'       => 'Test connection between Moodle and WordPress',
                'function'    => 'eb_setup_test_connection',
                'parent_step' => 'wordpress_site_details',
                'priority'    => 60,
                'sub_step'    => 0,
            ],
            'user_and_course_sync' => [
                'name'        => 'Setting up User and course sync',
                'title'       => 'Setting up User and course sync',
                'function'    => 'eb_setup_user_and_course_sync',
                'parent_step' => 'user_and_course_sync',
                'priority'    => 70,
                'sub_step'    => 0,
            ],
            'complete_details' => [
                'name'        => 'Edwiser Bridge FREE Moodle plugin setup complete',
                'title'       => 'Edwiser Bridge FREE Moodle plugin setup complete',
                'function'    => 'eb_setup_complete_details',
                'parent_step' => 'user_and_course_sync',
                'priority'    => 80,
                'sub_step'    => 0,
            ],
        ];
        return $steps;
    }



    /**
     * Setup Wizard Steps HTML content
     */
    public function eb_setup_steps_html($currentstep = '') {
        global $CFG, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $steps = $this->eb_setup_wizard_get_steps();

        $progress = isset($CFG->eb_setup_progress) ? $CFG->eb_setup_progress : '';
        $completed = !empty($progress) ? 1 : 0;

        $templatecontext = [
            'steps' => [],
        ];

        foreach ($steps as $key => $step) {
            $istoplevel = !$step['sub_step'];

            if ($istoplevel) {
                $class = '';
                $htmlicon = '<span class="eb-setup-step-circle eb_setup_sidebar_progress_icons"></span>';

                if ($completed === 1) {
                    $class = 'eb-setup-step-completed';
                    $htmlicon = '<i class="fa-solid fa-circle-check eb_setup_sidebar_progress_icons"></i>';
                }

                if ($currentstep === $key) {
                    $class = 'eb-setup-step-active';
                    $htmlicon = '<i class="fa-solid fa-circle-chevron-right eb_setup_sidebar_progress_icons"></i>';
                }

                $templatecontext['steps'][] = [
                    'top_level' => true,
                    'is_completed' => ($completed === 1),
                    'is_active' => ($currentstep === $key),
                    'html_icon' => $htmlicon,
                    'key' => $key,
                    'name' => $step['name'],
                ];

                if ($key === $progress) {
                    $completed = 0;
                }
            } else {
                if ($key === $progress) {
                    $completed = 0;
                }
            }
        }

        return $renderer->render_from_template('auth_edwiserbridge/setup_steps', $templatecontext);
    }

    /**
     * Setup Wizard get step title.
     *
     * @param string $step Step name.
     */
    public function eb_get_step_title($step) {
        $steps = $this->eb_setup_wizard_get_steps();
        return isset($steps[$step]['title']) ? $steps[$step]['title'] : '';
    }



    /**
     * Setup Wizard Page submission or refresh handler
     */
    public function eb_setup_handle_page_submission_or_refresh() {
        global $CFG;
        $steps = $this->eb_setup_wizard_get_steps();
        $step  = 'installation_guide';

        // Handle page refresh.
        $currentstep = optional_param('current_step', '', PARAM_TEXT);
        if (isset($currentstep) && !empty($currentstep)) {
            $step = $currentstep;
        } else if (isset($CFG->eb_setup_progress) && !empty($CFG->eb_setup_progress) && !isset($step)) {
            $step = $this->get_next_step($CFG->eb_setup_progress);
        } else {
            $step = 'installation_guide';
        }

        return $step;
    }



    /**
     * Get setup wizard step content.
     *
     * @param string $step Step name.
     */
    public function eb_setup_wizard_template($step = 'installation_guide') {
        global $PAGE;
        // Get current step.
        $contentclass = "";

        $steps = $this->eb_setup_wizard_get_steps();
        $step = $this->eb_setup_handle_page_submission_or_refresh();
        $title = $this->eb_get_step_title($step);

        $this->setup_wizard_header($title);

        // Sidebar HTML.
        $sidebar = $this->eb_setup_steps_html($step);

        // Content HTML.
        ob_start();
        $function = $steps[$step]['function'];
        $this->$function(0);
        $content = ob_get_clean();

        // Mustache template context.
        $templatecontext = [
            'sidebar' => $sidebar,
            'content' => $content,
            'contentclass' => $contentclass,
        ];

        // Render the setup_wizard_template.mustache template.
        $renderer = $PAGE->get_renderer('core');
        echo $renderer->render_from_template('auth_edwiserbridge/setup_wizard_template', $templatecontext);

        // Footer part.
        $this->setup_wizard_footer();
    }

    /**
     * Setup Wizard Header.
     */
    public function setup_wizard_header($title = '') {

        global $CFG, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        // Template context.
        $data = [
            'pageTitle' => get_string('edwiserbridge', 'auth_edwiserbridge'),
            'logoSrc' => 'images/moodle-logo.png',
            'headerTitle' => $title,
        ];

        // Render the template with the data.
        echo $renderer->render_from_template('auth_edwiserbridge/setup_wizard_header', $data);
    }

    /**
     * Setup Wizard Footer.
     */
    public function setup_wizard_footer() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        // Template context.
        $data = [
            'footerText' => get_string('setup_footer', 'auth_edwiserbridge'),
            'contactUsText' => get_string('setup_contact_us', 'auth_edwiserbridge'),
            'closeSetup' => $this->eb_setup_close_setup(),
        ];

        // Render the template with the data.
        echo $renderer->render_from_template('auth_edwiserbridge/setup_wizard_footer', $data);
    }

    /**
     * Get next step.
     * @param string $currentstep Current step.
     * @return string $step Next step.
     */
    public function get_next_step($currentstep) {
        $steps = $this->eb_setup_wizard_get_steps();
        $step = '';
        $foundstep = 0;

        foreach ($steps as $key => $value) {
            if ($foundstep) {
                $step = $key;
                break;
            }

            if ($currentstep == $key) {
                $foundstep = 1;
            }
        }

        return $step;
    }

    /**
     * Get previous step.
     * @param string $currentstep Current step.
     * @return string $step Previous step.
     */
    public function get_prev_step($currentstep) {

        $steps = $this->eb_setup_wizard_get_steps();
        $step = '';
        $foundstep = 0;
        $prevkey = '';
        foreach ($steps as $key => $value) {
            if ($currentstep == $key) {
                $foundstep = 1;
            }

            if ($foundstep) {
                $step = $prevkey;
                break;
            }
            $prevkey = $key;
        }

        return $step;
    }

    /**
     * Installaion guide.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_installation_guide($ajax = 1) {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        // Template context.
        $data = [
            'installationNote1' => get_string('setup_installation_note1', 'auth_edwiserbridge'),
            'moduleNameFreeWPPlugin' => get_string('modulename', 'auth_edwiserbridge') . ' '
                . get_string('setup_free', 'auth_edwiserbridge') . ' '
                . get_string('setup_wp_plugin', 'auth_edwiserbridge'),
            'moduleNameFreeMDLPlugin' => get_string('modulename', 'auth_edwiserbridge') . ' '
                . get_string('setup_free', 'auth_edwiserbridge') . ' '
                . get_string('setup_mdl_plugin', 'auth_edwiserbridge'),
            'installationNote2' => get_string('setup_installation_note2', 'auth_edwiserbridge'),
            'step' => 'installation_guide',
            'nextStep' => $this->get_next_step('installation_guide'),
            'isNextSubStep' => 0,
            'continueBtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
            'installationFaq' => get_string('setup_installation_faq', 'auth_edwiserbridge'),
            'faqDownloadPlugin' => get_string('setup_faq_download_plugin', 'auth_edwiserbridge'),
            'faqSteps' => get_string('setup_faq_steps', 'auth_edwiserbridge'),
            'faqStep1' => get_string('setup_faq_step1', 'auth_edwiserbridge'),
            'faqStep2' => get_string('setup_faq_step2', 'auth_edwiserbridge'),
            'faqStep3' => get_string('setup_faq_step3', 'auth_edwiserbridge'),
            'faqStep4' => get_string('setup_faq_step4', 'auth_edwiserbridge'),
        ];

        // Render the template with the data.
        $output = $renderer->render_from_template('auth_edwiserbridge/installation_guide', $data);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Plugin configuration.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_plugin_configuration($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'mdl_plugin_config';
        $isnextsubstep = 0;

        $settingenabled = "color:#1AB900;";
        $protocols = $CFG->webserviceprotocols;
        $protocols = in_array('rest', explode(',', $protocols)) ? 1 : 0;
        $webservice = $CFG->enablewebservices === '1' ? 1 : 0;
        $passwordpolicy = $CFG->passwordpolicy === '0' ? 1 : 0;
        $extendedchar = $CFG->extendedusernamechars === '1' ? 1 : 0;

        $allenabled = ($protocols && $webservice && $passwordpolicy && $extendedchar) ? 1 : 0;
        $nextstep = $this->get_next_step($step);

        $checks = [
            [
                'icon_class' => 'eb_enable_rest_protocol',
                'style' => $protocols === 1 ? $settingenabled : '',
                'check_text' => get_string('no_1', 'auth_edwiserbridge') . ". "
                    . get_string('setup_mdl_plugin_check1', 'auth_edwiserbridge'),
                'tooltip_text' => get_string('enabling_rest_tip', 'auth_edwiserbridge'),
            ],
            [
                'icon_class' => 'eb_enable_web_service',
                'style' => $webservice === 1 ? $settingenabled : '',
                'check_text' => get_string('no_2', 'auth_edwiserbridge') . ". "
                    . get_string('setup_mdl_plugin_check2', 'auth_edwiserbridge'),
                'tooltip_text' => get_string('enabling_service_tip', 'auth_edwiserbridge'),
            ],
            [
                'icon_class' => 'eb_disable_pwd_policy',
                'style' => $passwordpolicy === 1 ? $settingenabled : '',
                'check_text' => get_string('no_3', 'auth_edwiserbridge') . ". "
                    . get_string('setup_mdl_plugin_check3', 'auth_edwiserbridge'),
                'tooltip_text' => get_string('disable_passw_policy_tip', 'auth_edwiserbridge'),
            ],
            [
                'icon_class' => 'eb_allow_extended_char',
                'style' => $extendedchar === 1 ? $settingenabled : '',
                'check_text' => get_string('no_4', 'auth_edwiserbridge') . ". "
                    . get_string('setup_mdl_plugin_check4', 'auth_edwiserbridge'),
                'tooltip_text' => get_string('allow_exte_char_tip', 'auth_edwiserbridge'),
            ],
        ];

        $templatecontext = (object)[
            'setup_mdl_plugin_note1' => get_string('setup_mdl_plugin_note1', 'auth_edwiserbridge'),
            'checks' => $checks,
            'setup_mdl_settings_success_msg' => get_string('setup_mdl_settings_success_msg', 'auth_edwiserbridge'),
            'display_note' => $allenabled === 1 ? 'display:none;' : '',
            'setup_mdl_plugin_note2' => get_string('setup_mdl_plugin_note2', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'setup_enable_settings' => get_string('setup_enable_settings', 'auth_edwiserbridge'),
            'display_continue' => $allenabled === 1 ? 'display:initial;' : '',
            'setup_continue_btn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/plugin_configuration', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }



    /**
     * Web service setup.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_web_service($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'web_service';
        $disable = 'disabled';
        $isnextsubstep = 0;

        $nextstep = $this->get_next_step($step);

        $existingservices = auth_edwiserbridge_get_existing_services();
        $selectedservice = isset($CFG->ebexistingserviceselect) ? $CFG->ebexistingserviceselect : '';

        $services = [];
        foreach ($existingservices as $key => $value) {
            $services[] = [
                'key' => $key,
                'value' => $value,
                'selected' => $key == $selectedservice ? 'selected' : '',
            ];
            if ($key == $selectedservice) {
                $disable = '';
            }
        }

        $templatecontext = (object)[
            'setup_web_service_note1' => get_string('setup_web_service_note1', 'auth_edwiserbridge'),
            'setup_web_service_h1' => get_string('setup_web_service_h1', 'auth_edwiserbridge'),
            'or' => get_string('or', 'auth_edwiserbridge'),
            'setup_web_service_h2' => get_string('setup_web_service_h2', 'auth_edwiserbridge'),
            'sum_web_services' => get_string('sum_web_services', 'auth_edwiserbridge'),
            'web_service_tip' => get_string('web_service_tip', 'auth_edwiserbridge'),
            'existingservices' => $services,
            'new_service_inp_lbl' => get_string('new_service_inp_lbl', 'auth_edwiserbridge'),
            'name_web_service_tip' => get_string('name_web_service_tip', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'disable' => $disable,
            'setup_continue_btn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/web_service', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }


    /**
     * WordPress site details.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_wordpress_site_details($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'wordpress_site_details';
        $class = 'eb_setup_wp_site_details_wrap';
        $btnclass = 'disabled';
        $isnextsubstep = 1;
        $sites = auth_edwiserbridge_get_site_list();

        $nextstep = $this->get_next_step($step);
        $prevstep = $this->get_prev_step($step);
        $prevurl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $prevstep;

        $sitename = isset($CFG->eb_setup_wp_site_name) ? $CFG->eb_setup_wp_site_name : '';

        $wpsites = auth_edwiserbridge_get_connection_settings();
        $wpsites = $wpsites['eb_connection_settings'];

        $selectedname = '';
        $selectedurl = '';

        if (!empty($sitename) && isset($wpsites[$sitename])) {
            $selectedname = $sitename;
            $selectedurl = $wpsites[$sitename]['wp_url'];
            $class = '';
            $btnclass = '';
        }

        $sitesoptions = [];
        foreach ($sites as $key => $value) {
            $sitesoptions[] = [
                'key' => $key,
                'name' => $value,
                'url' => isset($wpsites[$key]) ? $wpsites[$key]['wp_url'] : '',
                'selected' => $key == $sitename ? 'selected' : '',
            ];
        }

        $templatecontext = (object)[
            'setup_wp_site_note1' => get_string('setup_wp_site_note1', 'auth_edwiserbridge'),
            'setup_wp_site_dropdown' => get_string('setup_wp_site_dropdown', 'auth_edwiserbridge'),
            'wp_site_tip' => get_string('wp_site_tip', 'auth_edwiserbridge'),
            'select' => get_string('select', 'auth_edwiserbridge'),
            'create_wp_site' => get_string('create_wp_site', 'auth_edwiserbridge'),
            'sites' => $sitesoptions,
            'setup_wp_site_note2' => get_string('setup_wp_site_note2', 'auth_edwiserbridge'),
            'name_label' => get_string('name', 'auth_edwiserbridge'),
            'wp_site_name_tip' => get_string('wp_site_name_tip', 'auth_edwiserbridge'),
            'selected_name' => $selectedname,
            'url_label' => get_string('url', 'auth_edwiserbridge'),
            'wp_site_url_tip' => get_string('wp_site_url_tip', 'auth_edwiserbridge'),
            'selected_url' => $selectedurl,
            'prev_url' => $prevurl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'btnclass' => $btnclass,
            'setup_continue_btn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/wordpress_site_details', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }


    /**
     * Check permalink structure.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_check_permalink($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'check_permalink';
        $isnextsubstep = 0;
        $nextstep = $this->get_next_step($step);
        $prevstep = $this->get_prev_step($step);
        $prevurl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $prevstep;

        $sitename = $CFG->eb_setup_wp_site_name;

        $sites = auth_edwiserbridge_get_connection_settings();
        $sites = $sites['eb_connection_settings'];

        $url = '';
        if (isset($sites[$sitename])) {
            $url = $sites[$sitename]['wp_url'];
        }

        if (substr($url, -1) == '/') {
            $url .= 'wp-admin/options-permalink.php';
        } else {
            $url .= '/wp-admin/options-permalink.php';
        }

        $templatecontext = (object)[
            'setup_permalink_note1' => get_string('setup_permalink_note1', 'auth_edwiserbridge'),
            'es_postname' => get_string('es_postname', 'auth_edwiserbridge'),
            'setup_permalink_click' => get_string('setup_permalink_click', 'auth_edwiserbridge'),
            'url' => $url,
            'setup_permalink_note2' => get_string('setup_permalink_note2', 'auth_edwiserbridge'),
            'setup_permalink_note3' => get_string('setup_permalink_note3', 'auth_edwiserbridge'),
            'prevurl' => $prevurl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'confirmed' => get_string('confirmed', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/permalink', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }


    /**
     * Test connection.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_test_connection($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'test_connection';
        $isnextsubstep = 1;
        $sitename = $CFG->eb_setup_wp_site_name;

        $sites = auth_edwiserbridge_get_connection_settings();
        $sites = $sites['eb_connection_settings'];

        $name = '';
        $url = '';
        if (isset($sites[$sitename])) {
            $name = $sitename;
            $url = $sites[$sitename]['wp_url'];
        }

        $nextstep = $this->get_next_step($step);
        $prevstep = $this->get_prev_step($step);
        $prevurl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $prevstep;

        $templatecontext = (object)[
            'wp_site_details_note' => get_string('wp_site_details_note', 'auth_edwiserbridge'),
            'name_label' => get_string('name', 'auth_edwiserbridge'),
            'wp_site_name_tip' => get_string('wp_site_name_tip', 'auth_edwiserbridge'),
            'name' => $name,
            'url_label' => get_string('url', 'auth_edwiserbridge'),
            'wp_site_url_tip' => get_string('wp_site_url_tip', 'auth_edwiserbridge'),
            'url' => $url,
            'prevurl' => $prevurl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'wp_test_conn_btn' => get_string('wp_test_conn_btn', 'auth_edwiserbridge'),
            'setup_continue_btn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/test_connection', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }


    /**
     * User and course sync.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_user_and_course_sync($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'user_and_course_sync';
        $isnextsubstep = 1;

        $nextstep = $this->get_next_step($step);
        $prevstep = $this->get_prev_step($step);
        $prevurl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $prevstep;
        $nexturl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $nextstep;

        $synchsettings = isset($CFG->eb_synch_settings) ? unserialize($CFG->eb_synch_settings) : [];
        $sitename = $CFG->eb_setup_wp_site_name;
        if (isset($synchsettings[$sitename])) {
            $data = $synchsettings[$sitename];
            $oldsettings = [
                "course_enrollment" => isset($data['course_enrollment']) ? $data['course_enrollment'] : 0,
                "course_un_enrollment" => isset($data['course_un_enrollment']) ? $data['course_un_enrollment'] : 0,
                "user_creation" => isset($data['user_creation']) ? $data['user_creation'] : 0,
                "user_deletion" => isset($data['user_deletion']) ? $data['user_deletion'] : 0,
                "course_creation" => isset($data['course_creation']) ? $data['course_creation'] : 0,
                "course_deletion" => isset($data['course_deletion']) ? $data['course_deletion'] : 0,
                "user_updation" => isset($data['user_updation']) ? $data['user_updation'] : 0,
            ];
            $sum = array_sum($oldsettings);
        } else {
            $oldsettings = [
                "course_enrollment" => 1,
                "course_un_enrollment" => 1,
                "user_creation" => 1,
                "user_deletion" => 1,
                "course_creation" => 1,
                "course_deletion" => 1,
                "user_updation" => 1,
            ];
            $sum = 7;
        }

        $templatecontext = (object)[
            'setup_sync_note1' => get_string('setup_sync_note1', 'auth_edwiserbridge'),
            'select_all' => get_string('select_all', 'auth_edwiserbridge'),
            'recommended' => get_string('recommended', 'auth_edwiserbridge'),
            'all_checked' => $sum == 7,
            'sync_settings' => [
                [
                    'name' => 'eb_setup_sync_user_enrollment',
                    'checked' => $oldsettings['course_enrollment'],
                    'label' => get_string('user_enrollment', 'auth_edwiserbridge'),
                    'tip' => get_string('user_enrollment_tip', 'auth_edwiserbridge'),
                ],
                [
                    'name' => 'eb_setup_sync_user_unenrollment',
                    'checked' => $oldsettings['course_un_enrollment'],
                    'label' => get_string('user_unenrollment', 'auth_edwiserbridge'),
                    'tip' => get_string('user_unenrollment_tip', 'auth_edwiserbridge'),
                ],
                [
                    'name' => 'eb_setup_sync_user_creation',
                    'checked' => $oldsettings['user_creation'],
                    'label' => get_string('user_creation', 'auth_edwiserbridge'),
                    'tip' => get_string('user_creation_tip', 'auth_edwiserbridge'),
                ],
                [
                    'name' => 'eb_setup_sync_user_deletion',
                    'checked' => $oldsettings['user_deletion'],
                    'label' => get_string('user_deletion', 'auth_edwiserbridge'),
                    'tip' => get_string('user_deletion_tip', 'auth_edwiserbridge'),
                ],
                [
                    'name' => 'eb_setup_sync_user_update',
                    'checked' => $oldsettings['user_updation'],
                    'label' => get_string('user_update', 'auth_edwiserbridge'),
                    'tip' => get_string('user_update_tip', 'auth_edwiserbridge'),
                ],
                [
                    'name' => 'eb_setup_sync_course_creation',
                    'checked' => $oldsettings['course_creation'],
                    'label' => get_string('course_creation', 'auth_edwiserbridge'),
                    'tip' => get_string('course_creation_tip', 'auth_edwiserbridge'),
                ],
                [
                    'name' => 'eb_setup_sync_course_deletion',
                    'checked' => $oldsettings['course_deletion'],
                    'label' => get_string('course_deletion', 'auth_edwiserbridge'),
                    'tip' => get_string('course_deletion_tip', 'auth_edwiserbridge'),
                ],
            ],
            'prevurl' => $prevurl,
            'nexturl' => $nexturl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'skip' => get_string('skip', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'setup_continue_btn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/user_and_course_sync', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }


    /**
     * Complete details.
     *
     * @param int $ajax Ajax call.
     * @return string $html HTML content.
     */
    public function eb_setup_complete_details($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'complete_details';
        $isnextsubstep = 0;

        $nextstep = $this->get_next_step($step);
        $sitename = $CFG->eb_setup_wp_site_name;

        $sites = auth_edwiserbridge_get_connection_settings();
        $sites = $sites['eb_connection_settings'];

        $url = $CFG->wwwroot;
        $wpurl = '';
        $token = '';
        if (isset($sites[$sitename])) {
            $wpurl = $sites[$sitename]['wp_url'];
            $token = $sites[$sitename]['wp_token'];
        }

        $prevstep = $this->get_prev_step($step);
        $prevurl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $prevstep;

        if (substr($wpurl, -1) == '/') {
            $wpurl = $wpurl . 'wp-admin/admin.php?page=eb-setup-wizard&current_step=test_connection';
        } else {
            $wpurl = $wpurl . '/wp-admin/admin.php?page=eb-setup-wizard&current_step=test_connection';
        }

        $templatecontext = (object)[
            'what_next' => get_string('what_next', 'auth_edwiserbridge'),
            'setup_completion_note1' => get_string('setup_completion_note1', 'auth_edwiserbridge'),
            'setup_completion_note2' => get_string('setup_completion_note2', 'auth_edwiserbridge'),
            'mdl_url' => get_string('mdl_url', 'auth_edwiserbridge'),
            'url' => $url,
            'wp_token' => get_string('wp_token', 'auth_edwiserbridge'),
            'token' => $token,
            'eb_mform_lang_desc' => get_string('eb_mform_lang_desc', 'auth_edwiserbridge'),
            'lang' => $CFG->lang,
            'or' => get_string('or', 'auth_edwiserbridge'),
            'setup_completion_note3' => get_string('setup_completion_note3', 'auth_edwiserbridge'),
            'mdl_edwiser_bridge_txt_download' => get_string('mdl_edwiser_bridge_txt_download', 'auth_edwiserbridge'),
            'setup_completion_note4' => get_string('setup_completion_note4', 'auth_edwiserbridge'),
            'prevurl' => $prevurl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'wpurl' => $wpurl,
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'continue_wp_wizard_btn' => get_string('continue_wp_wizard_btn', 'auth_edwiserbridge'),
            'setup_continue_btn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
            'eb_setup_redirection_popup' => $this->eb_setup_redirection_popup(),
            'eb_setup_completion_popup' => $this->eb_setup_completion_popup(),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/setup_complete_details', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }


    /**
     * Setup Wizard close setup.
     *
     * @return string $html HTML content.
     */
    public function eb_setup_close_setup() {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $templatecontext = [
            'wwwroot' => $CFG->wwwroot,
            'close_quest' => get_string('close_quest', 'auth_edwiserbridge'),
            'yes' => get_string('yes', 'auth_edwiserbridge'),
            'no' => get_string('no', 'auth_edwiserbridge'),
            'note' => get_string('note', 'auth_edwiserbridge'),
            'close_note' => get_string('close_note', 'auth_edwiserbridge'),
        ];

        return $renderer->render_from_template('auth_edwiserbridge/setup_close', $templatecontext);
    }

    /**
     * Setup Wizard close setup.
     *
     * @return string $html HTML content.
     */
    public function eb_setup_redirection_popup() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $templatecontext = (object)[];

        return $renderer->render_from_template('auth_edwiserbridge/setup_redirection_popup', $templatecontext);
    }


    /**
     * Setup Wizard close setup.
     *
     * @return string $html HTML content.
     */
    public function eb_setup_completion_popup() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $templatecontext = [
            'setup_completion_note5' => get_string('setup_completion_note5', 'auth_edwiserbridge'),
        ];

        return $renderer->render_from_template('auth_edwiserbridge/setup_completion_popup', $templatecontext);
    }

}
