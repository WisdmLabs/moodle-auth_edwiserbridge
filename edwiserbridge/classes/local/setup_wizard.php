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

namespace auth_edwiserbridge\local;
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
                'name'        => get_string('installation_free_guide', 'auth_edwiserbridge'),
                'title'       => get_string('installation_free_guide', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_installation_guide',
                'parent_step' => 'installation_guide',
                'priority'    => 10,
                'sub_step'    => 0,
            ],
            'mdl_plugin_config' => [
                'name'        => get_string('mdl_plugin_config', 'auth_edwiserbridge'),
                'title'       => get_string('mdl_plugin_config', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_plugin_configuration',
                'parent_step' => 'mdl_plugin_config',
                'priority'    => 20,
                'sub_step'    => 0,
            ],
            'web_service' => [
                'name'        => get_string('web_service_setup', 'auth_edwiserbridge'),
                'title'       => get_string('web_service_setup', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_web_service',
                'parent_step' => 'web_service',
                'priority'    => 30,
                'sub_step'    => 0,
            ],
            'wordpress_site_details' => [
                'name'        => get_string('wordpress_site_details', 'auth_edwiserbridge'),
                'title'       => get_string('wordpress_site_details', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_wordpress_site_details',
                'parent_step' => 'wordpress_site_details',
                'priority'    => 40,
                'sub_step'    => 0,
            ],
            'check_permalink' => [
                'name'        => get_string('check_permalink', 'auth_edwiserbridge'),
                'title'       => get_string('check_permalink', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_check_permalink',
                'parent_step' => 'wordpress_site_details',
                'priority'    => 50,
                'sub_step'    => 0,
            ],
            'test_connection' => [
                'name'        => get_string('test_connection', 'auth_edwiserbridge'),
                'title'       => get_string('test_connection', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_test_connection',
                'parent_step' => 'wordpress_site_details',
                'priority'    => 60,
                'sub_step'    => 0,
            ],
            'user_and_course_sync' => [
                'name'        => get_string('user_and_course_sync', 'auth_edwiserbridge'),
                'title'       => get_string('user_and_course_sync', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_user_and_course_sync',
                'parent_step' => 'user_and_course_sync',
                'priority'    => 70,
                'sub_step'    => 0,
            ],
            'complete_details' => [
                'name'        => get_string('complete_details', 'auth_edwiserbridge'),
                'title'       => get_string('complete_details', 'auth_edwiserbridge'),
                'function'    => 'eb_setup_complete_details',
                'parent_step' => 'user_and_course_sync',
                'priority'    => 80,
                'sub_step'    => 0,
            ],
        ];
        return $steps;
    }

    /**
     * Generates the HTML content for the setup wizard steps.
     *
     * @param string $currentstep The current step in the setup wizard.
     * @return string The HTML content for the setup wizard steps.
     */
    public function eb_setup_steps_html($currentstep = '') {
        global $CFG, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $steps = $this->eb_setup_wizard_get_steps();

        $eb_setup_progress = get_config('auth_edwiserbridge', 'eb_setup_progress');

        $progress = !empty($eb_setup_progress) ? $eb_setup_progress : '';
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
                    'toplevel' => true,
                    'iscompleted' => ($completed === 1),
                    'isactive' => ($currentstep === $key),
                    'htmlicon' => $htmlicon,
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
     * Get the title of the specified setup wizard step.
     *
     * @param string $step The name of the setup wizard step.
     * @return string The title of the specified step, or an empty string if the step is not found.
     */
    public function eb_get_step_title($step) {
        $steps = $this->eb_setup_wizard_get_steps();
        return isset($steps[$step]['title']) ? $steps[$step]['title'] : '';
    }

    /**
     * Handles the submission or refresh of the setup wizard page.
     *
     * This function determines the current step of the setup wizard based on the
     * request parameters or the saved progress in the configuration.
     *
     * @return string The name of the current setup wizard step.
     */
    public function eb_setup_handle_page_submission_or_refresh() {
        $steps = $this->eb_setup_wizard_get_steps();
        $step  = 'installation_guide';
        $eb_setup_progress = get_config('auth_edwiserbridge', 'eb_setup_progress');
        // Handle page refresh.
        $currentstep = optional_param('current_step', '', PARAM_TEXT);
        if (isset($currentstep) && !empty($currentstep)) {
            $step = $currentstep;
        } else if (isset($eb_setup_progress) && !empty($eb_setup_progress) && !isset($step)) {
            $step = $this->get_next_step($eb_setup_progress);
        } else {
            $step = 'installation_guide';
        }

        return $step;
    }

    /**
     * Renders the setup wizard template.
     *
     * This function is responsible for rendering the setup wizard template, which
     * includes the sidebar and content sections. It determines the current step of
     * the setup wizard and calls the appropriate function to generate the content
     * for that step.
     *
     * @param string $step The name of the current setup wizard step, defaulting to
     *                     'installation_guide'.
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
     * Renders the setup wizard header.
     *
     * This function is responsible for rendering the header section of the setup wizard
     * template. It takes an optional $title parameter to set the page title.
     *
     * @param string $title The title to display in the header.
     */
    public function setup_wizard_header($title = '') {

        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        // Template context.
        $data = [
            'pagetitle' => get_string('edwiserbridge', 'auth_edwiserbridge'),
            'logosrc' => 'images/moodle-logo.png',
            'headertitle' => $title,
        ];

        // Render the template with the data.
        echo $renderer->render_from_template('auth_edwiserbridge/setup_wizard_header', $data);
    }

    /**
     * Renders the setup wizard footer.
     *
     * This function is responsible for rendering the footer section of the setup wizard
     * template. It sets up the template context with the necessary data and then
     * renders the 'auth_edwiserbridge/setup_wizard_footer' template.
     */
    public function setup_wizard_footer() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        // Template context.
        $data = [
            'footertext' => get_string('setup_footer', 'auth_edwiserbridge'),
            'contactustext' => get_string('setup_contact_us', 'auth_edwiserbridge'),
            'closesetup' => $this->eb_setup_close_setup(),
        ];

        // Render the template with the data.
        echo $renderer->render_from_template('auth_edwiserbridge/setup_wizard_footer', $data);
    }

    /**
     * Get the next step in the setup wizard.
     *
     * This function retrieves the next step in the setup wizard based on the current step.
     *
     * @param string $currentstep The current step in the setup wizard.
     * @return string The next step in the setup wizard.
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
     * Get the previous step in the setup wizard.
     *
     * This function retrieves the previous step in the setup wizard based on the current step.
     *
     * @param string $currentstep The current step in the setup wizard.
     * @return string The previous step in the setup wizard.
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
     * Displays the installation guide for the Edwiser Bridge plugin.
     *
     * This function renders the installation guide template with the necessary data and
     * outputs the HTML content. If the $ajax parameter is set to 1, the function will
     * return the HTML content instead of directly echoing it.
     *
     * @param int $ajax Whether the call is an AJAX request (1) or not (0).
     * @return string The HTML content of the installation guide.
     */
    public function eb_setup_installation_guide($ajax = 1) {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        // Template context.
        $data = [
            'installationnote1' => get_string('setup_installation_note1', 'auth_edwiserbridge'),
            'modulenamefreewpplugin' => get_string('modulename', 'auth_edwiserbridge') . ' '
                . get_string('setup_free', 'auth_edwiserbridge') . ' '
                . get_string('setup_wp_plugin', 'auth_edwiserbridge'),
            'modulenamefreemdlplugin' => get_string('modulename', 'auth_edwiserbridge') . ' '
                . get_string('setup_free', 'auth_edwiserbridge') . ' '
                . get_string('setup_mdl_plugin', 'auth_edwiserbridge'),
            'installationnote2' => get_string('setup_installation_note2', 'auth_edwiserbridge'),
            'step' => 'installation_guide',
            'nextstep' => $this->get_next_step('installation_guide'),
            'isnextsubstep' => 0,
            'continuebtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
            'installationfaq' => get_string('setup_installation_faq', 'auth_edwiserbridge'),
            'faqdownloadplugin' => get_string('setup_faq_download_plugin', 'auth_edwiserbridge'),
            'faqsteps' => get_string('setup_faq_steps', 'auth_edwiserbridge'),
            'faqstep1' => get_string('setup_faq_step1', 'auth_edwiserbridge'),
            'faqstep2' => get_string('setup_faq_step2', 'auth_edwiserbridge'),
            'faqstep3' => get_string('setup_faq_step3', 'auth_edwiserbridge'),
            'faqstep4' => get_string('setup_faq_step4', 'auth_edwiserbridge'),
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
     * Outputs the HTML content for the plugin configuration page. If the $ajax parameter is set to 1, the function will
     * return the HTML content instead of directly echoing it.
     *
     * @param int $ajax Whether the call is an AJAX request (1) or not (0).
     * @return string The HTML content of the plugin configuration page.
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
            'setupmdlpluginnote1' => get_string('setup_mdl_plugin_note1', 'auth_edwiserbridge'),
            'checks' => $checks,
            'setupmdlsettingssuccessmsg' => get_string('setup_mdl_settings_success_msg', 'auth_edwiserbridge'),
            'displaynote' => $allenabled === 1 ? 'display:none;' : '',
            'setupmdlpluginnote2' => get_string('setup_mdl_plugin_note2', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'setupenablesettings' => get_string('setup_enable_settings', 'auth_edwiserbridge'),
            'displaycontinue' => $allenabled === 1 ? 'display:initial;' : '',
            'setupcontinuebtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/plugin_configuration', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Handles the web service setup for the Edwiser Bridge plugin.
     *
     * @param int $ajax Indicates whether the request is an AJAX call.
     * @return string $html HTML content for the web service setup.
     */
    public function eb_setup_web_service($ajax = 1) {
        global $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'web_service';
        $disable = 'disabled';
        $isnextsubstep = 0;

        $nextstep = $this->get_next_step($step);

        $existingservices = auth_edwiserbridge_get_existing_services();
        $ebexistingserviceselect = get_config('auth_edwiserbridge', 'ebexistingserviceselect');
        $selectedservice = !empty($ebexistingserviceselect) ? $ebexistingserviceselect : '';

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
            'setupwebservicenote1' => get_string('setup_web_service_note1', 'auth_edwiserbridge'),
            'setupwebserviceh1' => get_string('setup_web_service_h1', 'auth_edwiserbridge'),
            'or' => get_string('or', 'auth_edwiserbridge'),
            'setupwebserviceh2' => get_string('setup_web_service_h2', 'auth_edwiserbridge'),
            'sumwebservices' => get_string('sum_web_services', 'auth_edwiserbridge'),
            'webservicetip' => get_string('web_service_tip', 'auth_edwiserbridge'),
            'existingservices' => $services,
            'newserviceinplbl' => get_string('new_service_inp_lbl', 'auth_edwiserbridge'),
            'namewebservicetip' => get_string('name_web_service_tip', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'disable' => $disable,
            'setupcontinuebtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/web_service', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Displays the WordPress site details step in the setup wizard.
     *
     * @param int $ajax Whether the function is called via AJAX (1) or not (0).
     * @return string $html HTML content for the WordPress site details step.
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

        $eb_setup_wp_site_name = get_config('auth_edwiserbridge', 'eb_setup_wp_site_name');

        $sitename = !empty($eb_setup_wp_site_name) ? $eb_setup_wp_site_name : '';

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
            'setupwpsitenote1' => get_string('setup_wp_site_note1', 'auth_edwiserbridge'),
            'setupwpsitedropdown' => get_string('setup_wp_site_dropdown', 'auth_edwiserbridge'),
            'wpsitetip' => get_string('wp_site_tip', 'auth_edwiserbridge'),
            'select' => get_string('select', 'auth_edwiserbridge'),
            'createwpsite' => get_string('create_wp_site', 'auth_edwiserbridge'),
            'sites' => $sitesoptions,
            'setupwpsitenote2' => get_string('setup_wp_site_note2', 'auth_edwiserbridge'),
            'namelabel' => get_string('name', 'auth_edwiserbridge'),
            'wpsitenametip' => get_string('wp_site_name_tip', 'auth_edwiserbridge'),
            'selectedname' => $selectedname,
            'urllabel' => get_string('url', 'auth_edwiserbridge'),
            'wpsiteurltip' => get_string('wp_site_url_tip', 'auth_edwiserbridge'),
            'selectedurl' => $selectedurl,
            'prevurl' => $prevurl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'btnclass' => $btnclass,
            'setupcontinuebtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/wordpress_site_details', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Checks the permalink structure of the WordPress site.
     *
     * @param int $ajax Indicates whether the call is an AJAX request (1) or not (0).
     * @return string $html HTML content to be displayed.
     */
    public function eb_setup_check_permalink($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'check_permalink';
        $isnextsubstep = 0;
        $nextstep = $this->get_next_step($step);
        $prevstep = $this->get_prev_step($step);
        $prevurl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $prevstep;

        $eb_setup_wp_site_name = get_config('auth_edwiserbridge', 'eb_setup_wp_site_name');

        $sitename = $eb_setup_wp_site_name;

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
            'setuppermalinknote1' => get_string('setup_permalink_note1', 'auth_edwiserbridge'),
            'espostname' => get_string('es_postname', 'auth_edwiserbridge'),
            'setuppermalinkclick' => get_string('setup_permalink_click', 'auth_edwiserbridge'),
            'url' => $url,
            'setuppermalinknote2' => get_string('setup_permalink_note2', 'auth_edwiserbridge'),
            'setuppermalinknote3' => get_string('setup_permalink_note3', 'auth_edwiserbridge'),
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

        $eb_setup_wp_site_name = get_config('auth_edwiserbridge', 'eb_setup_wp_site_name');

        $sitename = $eb_setup_wp_site_name;

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
            'wpsitedetailsnote' => get_string('wp_site_details_note', 'auth_edwiserbridge'),
            'namelabel' => get_string('name', 'auth_edwiserbridge'),
            'wpsitenametip' => get_string('wp_site_name_tip', 'auth_edwiserbridge'),
            'name' => $name,
            'urllabel' => get_string('url', 'auth_edwiserbridge'),
            'wpsiteurltip' => get_string('wp_site_url_tip', 'auth_edwiserbridge'),
            'url' => $url,
            'prevurl' => $prevurl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'wptestconnbtn' => get_string('wp_test_conn_btn', 'auth_edwiserbridge'),
            'setupcontinuebtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/test_connection', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Handles the user and course synchronization settings in the setup wizard.
     *
     * @param int $ajax Whether the function is called via AJAX (1) or not (0).
     * @return string $html HTML content to be displayed in the setup wizard.
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

        $eb_synch_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        $eb_setup_wp_site_name = get_config('auth_edwiserbridge', 'eb_setup_wp_site_name');

        $synchsettings = !empty($eb_synch_settings) ? json_decode($eb_synch_settings, true) : [];
        $sitename = $eb_setup_wp_site_name;
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
            'setupsyncnote1' => get_string('setup_sync_note1', 'auth_edwiserbridge'),
            'selectall' => get_string('select_all', 'auth_edwiserbridge'),
            'recommended' => get_string('recommended', 'auth_edwiserbridge'),
            'allchecked' => $sum == 7,
            'syncsettings' => [
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
            'setupcontinuebtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/user_and_course_sync', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Handles the completion of the setup wizard for the Edwiser Bridge plugin.
     *
     * @param int $ajax Indicates whether the request is an AJAX call (1) or not (0).
     * @return string $html The HTML content to be displayed.
     */
    public function eb_setup_complete_details($ajax = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $step = 'complete_details';
        $isnextsubstep = 0;

        $nextstep = $this->get_next_step($step);

        $eb_setup_wp_site_name = get_config('auth_edwiserbridge', 'eb_setup_wp_site_name');

        $sitename = $eb_setup_wp_site_name;

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
            'whatnext' => get_string('what_next', 'auth_edwiserbridge'),
            'setupcompletionnote1' => get_string('setup_completion_note1', 'auth_edwiserbridge'),
            'setupcompletionnote2' => get_string('setup_completion_note2', 'auth_edwiserbridge'),
            'mdlurl' => get_string('mdl_url', 'auth_edwiserbridge'),
            'url' => $url,
            'wptoken' => get_string('wp_token', 'auth_edwiserbridge'),
            'token' => $token,
            'ebmformlangdesc' => get_string('eb_mform_lang_desc', 'auth_edwiserbridge'),
            'lang' => $CFG->lang,
            'or' => get_string('or', 'auth_edwiserbridge'),
            'setupcompletionnote3' => get_string('setup_completion_note3', 'auth_edwiserbridge'),
            'mdledwiserbridgetxtdownload' => get_string('mdl_edwiser_bridge_txt_download', 'auth_edwiserbridge'),
            'setupcompletionnote4' => get_string('setup_completion_note4', 'auth_edwiserbridge'),
            'prevurl' => $prevurl,
            'back' => get_string('back', 'auth_edwiserbridge'),
            'wpurl' => $wpurl,
            'step' => $step,
            'nextstep' => $nextstep,
            'isnextsubstep' => $isnextsubstep,
            'continuewpwizardbtn' => get_string('continue_wp_wizard_btn', 'auth_edwiserbridge'),
            'setupcontinuebtn' => get_string('setup_continue_btn', 'auth_edwiserbridge'),
            'ebsetupredirectionpopup' => $this->eb_setup_redirection_popup(),
            'ebsetupcompletionpopup' => $this->eb_setup_completion_popup(),
        ];

        $output = $renderer->render_from_template('auth_edwiserbridge/setup_complete_details', $templatecontext);

        if ($ajax) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Renders the HTML content for the setup close popup.
     *
     * @return string $html HTML content for the setup close popup.
     */
    public function eb_setup_close_setup() {
        global $CFG, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $templatecontext = [
            'wwwroot' => $CFG->wwwroot,
            'closequest' => get_string('close_quest', 'auth_edwiserbridge'),
            'yes' => get_string('yes', 'auth_edwiserbridge'),
            'no' => get_string('no', 'auth_edwiserbridge'),
            'note' => get_string('note', 'auth_edwiserbridge'),
            'closenote' => get_string('close_note', 'auth_edwiserbridge'),
        ];

        return $renderer->render_from_template('auth_edwiserbridge/setup_close', $templatecontext);
    }

    /**
     * Renders the HTML content for the setup redirection popup.
     *
     * @return string $html HTML content for the setup redirection popup.
     */
    public function eb_setup_redirection_popup() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $templatecontext = (object)[];

        return $renderer->render_from_template('auth_edwiserbridge/setup_redirection_popup', $templatecontext);
    }

    /**
     * Renders the HTML content for the setup completion popup.
     *
     * @return string $html HTML content for the setup completion popup.
     */
    public function eb_setup_completion_popup() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $templatecontext = [
            'setupcompletionnote5' => get_string('setup_completion_note5', 'auth_edwiserbridge'),
        ];

        return $renderer->render_from_template('auth_edwiserbridge/setup_completion_popup', $templatecontext);
    }

}
