<?php
/**
 * Plugin Name: WP Mailster Free
 * Plugin URI: https://www.wpmailster.com
 * Description: The Mailing List Plugin For WordPress
 * Author: brandtoss
 * Author URI: https://www.wpmailster.com
 * Version: 1.5.5.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * WP Mailster is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Mailster is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Mailster. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
  die('These droids are not the droids you are looking for.');
}
require_once(plugin_dir_path(__FILE__) . "admin/mst_config.php");
require_once(plugin_dir_path(__FILE__) . "widget/subscribe-widget.php");

global $wpdb;
$wpdb->show_errors();

class wpmst_mailster
{
  private $table_name, $user_table;

  public $mailingLists_obj;
  public $server_obj;
  public $groups_obj;
  public $users_obj;

  public function __construct($file_path)
  {
    $this->wpmst_define_data($file_path);
    require_once $this->WPMST_PLUGIN_DIR . "/mailster/includes.php"; // Get all essential includes
    require_once $this->WPMST_PLUGIN_DIR . "/models/MailsterModel.php";
    require_once $this->WPMST_PLUGIN_DIR . "/mailster/Consts.php";
    require_once $this->WPMST_PLUGIN_DIR . "/models/MailsterModelList.php";
    require_once $this->WPMST_PLUGIN_DIR . "/models/MailsterModelServer.php";
    require_once $this->WPMST_PLUGIN_DIR . "/models/plgSystemMailster.php";
    require_once $this->WPMST_PLUGIN_DIR . "/mailster/Factory.php";
    //require_once $this->WPMST_PLUGIN_DIR."/classes/mst_configuration.php";

    /* Define plugin directory paths and urls.  */
    load_plugin_textdomain('wpmst-mailster', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    //register menu
    add_action('admin_menu', array($this, 'wpmst_mailster_menu'));

    /*Activation and Deactivation Hooks*/
    /*activate tables*/
    register_activation_hook(__FILE__, 'mailster_install');
    register_activation_hook(__FILE__, 'mailster_install_data');

    /*add scripts*/
    add_action('admin_enqueue_scripts', array($this, 'wpmst_mailster_admin_enqueues'));
    add_action('wp_enqueue_scripts', array($this, 'wpmst_wp_mailster_enqueues'));

    /*ajax handling*/
    add_action('wp_ajax_wpmst_pagination_request', array($this, 'wpmst_pagination_request'));
    add_action('wp_ajax_nopriv_wpmst_pagination_request', array($this, 'wpmst_pagination_request'));

    add_action('wp_ajax_wpmst_delete_users', array($this, 'wpmst_delete_users'));
    add_action('wp_ajax_nopriv_wpmst_delete_users', array($this, 'wpmst_delete_users'));

    add_action('wp_ajax_wpmst_delete_groups', array($this, 'wpmst_delete_groups'));
    add_action('wp_ajax_nopriv_wpmst_delete_groups', array($this, 'wpmst_delete_groups'));

    add_action('wp_ajax_wpmst_delete_lists', array($this, 'wpmst_delete_lists'));
    add_action('wp_ajax_nopriv_wpmst_delete_lists', array($this, 'wpmst_delete_lists'));

    add_action('wp_ajax_wpmst_activate_lists', array($this, 'wpmst_activate_lists'));
    add_action('wp_ajax_nopriv_wpmst_activate_lists', array($this, 'wpmst_activate_lists'));

    add_action('wp_ajax_wpmst_deactivate_lists', array($this, 'wpmst_deactivate_lists'));
    add_action('wp_ajax_nopriv_wpmst_deactivate_lists', array($this, 'wpmst_deactivate_lists'));

    add_action('wp_ajax_wpmst_delete_user_list', array($this, 'wpmst_delete_user_list'));
    add_action('wp_ajax_nopriv_wpmst_delete_user_list', array($this, 'wpmst_delete_user_list'));

    add_action('wp_ajax_wpmst_delete_user_group', array($this, 'wpmst_delete_user_group'));
    add_action('wp_ajax_nopriv_wpmst_delete_user_group', array($this, 'wpmst_delete_user_group'));

    add_action('wp_ajax_wpmst_delete_group_list', array($this, 'wpmst_delete_group_list'));
    add_action('wp_ajax_nopriv_wpmst_delete_group_list', array($this, 'wpmst_delete_group_list'));

    add_action('wp_ajax_wpmst_delete_notify', array($this, 'wpmst_delete_notify'));
    add_action('wp_ajax_nopriv_wpmst_delete_notify', array($this, 'wpmst_delete_notify'));

    add_action('wp_ajax_wpmst_subscribe_plugin', array($this, 'wpmst_subscribe_plugin'));
    add_action('wp_ajax_nopriv_wpmst_subscribe_plugin', array($this, 'wpmst_subscribe_plugin'));

    add_action('wp_ajax_wpmst_unsubscribe_plugin', array($this, 'wpmst_unsubscribe_plugin'));
    add_action('wp_ajax_nopriv_wpmst_unsubscribe_plugin', array($this, 'wpmst_unsubscribe_plugin'));

    /*handle active ajax*/
    add_action('wp_ajax_wpmst_active_request', array($this, 'wpmst_active_request'));
    add_action('wp_ajax_nopriv_wpmst_active_request', array($this, 'wpmst_active_request'));

    /* languages */
    add_action('init', array($this, 'wpmst_load_textdomain'));

    //tables
    global $wpdb;
    $this->mailster_users = $wpdb->prefix . 'mailster_users';
    $this->mailster_groups = $wpdb->prefix . 'mailster_groups';
    $this->mailster_group_users = $wpdb->prefix . 'mailster_group_users';
    $this->mailster_lists = $wpdb->prefix . 'mailster_lists';

    //register configuration settings
    add_action('admin_init', 'mst_setup');
    function mst_setup()
    {
      //register our settings
      register_setting('wp_mailster_settings', 'license_key');
      register_setting('wp_mailster_settings', 'current_version');
      register_setting('wp_mailster_settings', 'version_license');
      register_setting('wp_mailster_settings', 'uninstall_delete_data');
      register_setting('wp_mailster_settings', 'cron_job_key');
      register_setting('wp_mailster_settings', 'undo_line_wrapping');
      register_setting('wp_mailster_settings', 'logging_level');
      register_setting('wp_mailster_settings', 'mail_date_format');
      register_setting('wp_mailster_settings', 'mail_date_format_without_time');
      register_setting('wp_mailster_settings', 'add_reply_prefix');
      register_setting('wp_mailster_settings', 'reply_prefix');
      register_setting('wp_mailster_settings', 'undo_line_wrapping');
      register_setting('wp_mailster_settings', 'trigger_source');
      register_setting('wp_mailster_settings', 'mail_from_field');
      register_setting('wp_mailster_settings', 'name_from_field');
      register_setting('wp_mailster_settings', 'mail_from_email_for_from_name_field');
      register_setting('wp_mailster_settings', 'tag_mailster_mails');
      register_setting('wp_mailster_settings', 'blocked_email_addresses');
      register_setting('wp_mailster_settings', 'words_to_filter');
      register_setting('wp_mailster_settings', 'keep_blocked_mails_for_days');
      register_setting('wp_mailster_settings', 'keep_bounced_mails_for_days');
      register_setting('wp_mailster_settings', 'recaptcha2_public_key');
      register_setting('wp_mailster_settings', 'recaptcha2_private_key');
      register_setting('wp_mailster_settings', 'recaptcha_theme');
      register_setting('wp_mailster_settings', 'use_alt_txt_vars');
      register_setting('wp_mailster_settings', 'include_body_in_blocked_bounced_notifies');
      register_setting('wp_mailster_settings', 'max_mails_per_hour');
      register_setting('wp_mailster_settings', 'max_mails_per_minute');
      register_setting('wp_mailster_settings', 'wait_between_two_mails');
      register_setting('wp_mailster_settings', 'imap_opentimeout');
      register_setting('wp_mailster_settings', 'imap_opentimeout');
      register_setting('wp_mailster_settings', 'include_body_in_blocked_bounced_notifies');
      register_setting('wp_mailster_settings', 'digest_format_html_or_plain');
      register_setting('wp_mailster_settings', 'logging_level');
      register_setting('wp_mailster_settings', 'log_entry_destination');
      register_setting('wp_mailster_settings', 'force_logging');
      register_setting('wp_mailster_settings', 'log_file_warning_size_mb');
      register_setting('wp_mailster_settings', 'log_db_warning_entries_nr');
      register_setting('wp_mailster_settings', 'last_mail_sent_at');
      register_setting('wp_mailster_settings', 'last_hour_mail_sent_in');
      register_setting('wp_mailster_settings', 'nr_of_mails_sent_in_last_hour');
      register_setting('wp_mailster_settings', 'last_day_mail_sent_in');
      register_setting('wp_mailster_settings', 'nr_of_mails_sent_in_last_day');
      register_setting('wp_mailster_settings', 'minchecktime');
      register_setting('wp_mailster_settings', 'minsendtime');
      register_setting('wp_mailster_settings', 'minmaintenance');
      register_setting('wp_mailster_settings', 'maxexectime');
      register_setting('wp_mailster_settings', 'minduration');
    }
  }

  /*	Define plugins paths */
  public function wpmst_define_data($file_path)
  {

    $this->WPMST_PLUGIN_BASENAME = plugin_basename($file_path);
    $this->WPMST_PLUGIN_DIR = trailingslashit(dirname(trailingslashit(WP_PLUGIN_DIR) . $this->WPMST_PLUGIN_BASENAME));
    $this->WPMST_PLUGIN_FILE = trailingslashit(WP_PLUGIN_DIR) . $this->WPMST_PLUGIN_BASENAME;
    $this->WPMST_PLUGIN_DIR_URL = trailingslashit(plugins_url(dirname($this->WPMST_PLUGIN_BASENAME)));
    $this->WPMST_PLUGIN_URL = trailingslashit(plugins_url($this->WPMST_PLUGIN_BASENAME));

  }


  /*loading scripts*/
  function wpmst_mailster_admin_enqueues()
  {

    wp_register_style('mst_print_style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/mst_print.css');
    wp_enqueue_style('mst_print_style');

    wp_register_style('jquery-ui-style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/jquery-ui.css');
    wp_enqueue_style('jquery-ui-style');

    wp_register_style('mst_mailster_style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/mailster.css');
    wp_enqueue_style('mst_mailster_style');

    wp_register_style('multiselect2side_style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/jquery.multiselect2side.css');
    wp_enqueue_style('multiselect2side_style');

    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-tabs');

    wp_enqueue_script('multiselect2side', $this->WPMST_PLUGIN_DIR_URL . 'asset/js/jquery.multiselect2side.js', array('jquery'));
    wp_enqueue_script('mailster_js', $this->WPMST_PLUGIN_DIR_URL . 'asset/js/mst_script.js', array('jquery', 'multiselect2side', 'jquery-ui-core', 'jquery-ui-tabs'));

    wp_enqueue_script('mailster_admin_list_utils', $this->WPMST_PLUGIN_DIR_URL . 'asset/js/admin.list.utils.js', array('jquery', 'jquery-ui-core', 'jquery-ui-dialog'));

  }

  function wpmst_wp_mailster_enqueues()
  {
    wp_enqueue_script('jquery-ui-core');

    wp_enqueue_script('wpmst-subscribe-form-ajax', $this->WPMST_PLUGIN_DIR_URL . 'asset/js/wpmstsubscribe.js', array('jquery'));
    wp_localize_script('wpmst-subscribe-form-ajax', 'wpmst_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'))); // setting ajaxurl

    wp_register_script('wpmst-recaptcha-v2', 'https://www.google.com/recaptcha/api.js?onload=onLoadInitCaptchas&render=explicit');
  }

  function wpmst_load_textdomain()
  {
    load_plugin_textdomain('wpmst-mailster', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
  }

  /* Creating Menus */
  public function wpmst_mailster_menu()
  {
    $admin_level = 'edit_posts';
    $user_level = 'read';
    /* Adding menus */
    add_menu_page(__('WP Mailster', 'wpmst-mailster'), __('WP Mailster', 'wpmst-mailster'), $admin_level, 'wpmst_mailster_intro', array($this, 'wpmst_mailster_intro'), "dashicons-email-alt");

    //mailing list page
    include_once $this->WPMST_PLUGIN_DIR . "/classes/mst_mailing_lists.php";
    $hook = add_submenu_page('wpmst_mailster_intro', __('Mailing Lists', 'wpmst-mailster'), __('Mailing Lists', 'wpmst-mailster'), $admin_level, 'mst_mailing_lists', array($this, 'mst_mailing_list_page'));
    add_action("load-$hook", array($this, 'mst_mailing_list_screen_option'));


    $hook = add_submenu_page('wpmst_mailster_intro', __('Add Mailing List', 'wpmst-mailster'), __('Add Mailing List', 'wpmst-mailster'), $admin_level, 'mst_mailing_list_add', array($this, 'mst_mailing_list_add'));
    $hook = add_submenu_page('wpmst_mailster_intro', __('Manage Recipients', 'wpmst-mailster'), __('Manage Recipients', 'wpmst-mailster'), $admin_level, 'mst_recipient_management', array($this, 'mst_recipient_management'));
    $hook = add_submenu_page('wpmst_mailster_intro', __('Add Recipient', 'wpmst-mailster'), __('Add Recipient', 'wpmst-mailster'), $admin_level, 'mst_list_members_add', array($this, 'mst_list_members_add'));
    $hook = add_submenu_page('wpmst_mailster_intro', __('Add Group', 'wpmst-mailster'), __('Add Group', 'wpmst-mailster'), $admin_level, 'mst_list_groups_add', array($this, 'mst_list_groups_add'));

    //queued emails page
    include_once $this->WPMST_PLUGIN_DIR . "/classes/mst_queued.php";
    $hook = add_submenu_page('wpmst_mailster_intro', __('Queued Emails', 'wpmst-mailster'), __('Queued Emails', 'wpmst-mailster'), $admin_level, 'mst_queued', array($this, 'mst_queued_page'));
    add_action("load-$hook", array($this, 'mst_queued_screen_option'));

    //server page
    include_once $this->WPMST_PLUGIN_DIR . "/classes/mst_servers.php";
    $hook = add_submenu_page('wpmst_mailster_intro', __('Servers', 'wpmst-mailster'), __('Servers', 'wpmst-mailster'), $admin_level, 'mst_servers', array($this, 'mst_servers_page'));
    add_action("load-$hook", array($this, 'mst_servers_screen_option'));
    $hook = add_submenu_page('wpmst_mailster_intro', __('Add Server', 'wpmst-mailster'), __('Add Server', 'wpmst-mailster'), $admin_level, 'mst_servers_add', array($this, 'mst_servers_add'));

    //user page
    include_once $this->WPMST_PLUGIN_DIR . "/classes/mst_users.php";
    $hook = add_submenu_page('wpmst_mailster_intro', __('Users', 'wpmst-mailster'), __('Users', 'wpmst-mailster'), $admin_level, 'mst_users', array($this, 'mst_users_page'));
    add_action("load-$hook", array($this, 'mst_users_screen_option'));
    $hook = add_submenu_page('wpmst_mailster_intro', __('Add User', 'wpmst-mailster'), __('Add User', 'wpmst-mailster'), $admin_level, 'mst_users_add', array($this, 'mst_users_add'));

    //groups list page
    include_once $this->WPMST_PLUGIN_DIR . "/classes/mst_groups.php";
    $hook = add_submenu_page('wpmst_mailster_intro', __('Groups', 'wpmst-mailster'), __('Groups', 'wpmst-mailster'), $admin_level, 'mst_groups', array($this, 'mst_groups_page'));
    add_action("load-$hook", array($this, 'mst_groups_screen_option'));
    $hook = add_submenu_page('wpmst_mailster_intro', __('Add Group', 'wpmst-mailster'), __('Add Group', 'wpmst-mailster'), $admin_level, 'mst_groups_add', array($this, 'mst_groups_add'));
    // mail archive
    include_once $this->WPMST_PLUGIN_DIR . "/classes/mst_archived.php";
    $hook = add_submenu_page('wpmst_mailster_intro', __('Archived Emails', 'wpmst-mailster'), __('Archived Emails', 'wpmst-mailster'), $admin_level, 'mst_archived', array(
      $this,
      'mst_archived_page'
    ));
    add_action("load-$hook", array($this, 'mst_archived_screen_option'));
    //settings page
    $hook = add_submenu_page('wpmst_mailster_intro', __('Settings', 'wpmst-mailster'), __('Settings', 'wpmst-mailster'), $admin_level, 'wpmst_settings', array($this, 'mst_settings_page'));

    if (MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)) {
      // TODO Missing managing digests from backend?!
    }

  }

  /*********************************************\
   *********************************************
   *
   * Mailing Lists
   *********************************************
   * \*********************************************/

  /**
   * Mailing Lists Page
   */
  public function mst_mailing_list_page()
  {
    if (isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
      $this->mst_mailing_list_add();
    } else if (isset($_GET['subpage']) && $_GET['subpage'] == "recipients") {
      $this->mst_recipient_management();
    } else if (isset($_GET['subpage']) && $_GET['subpage'] == "managemembers") {
      $this->mst_list_members_add();
    } else if (isset($_GET['subpage']) && $_GET['subpage'] == "managegroups") {
      $this->mst_list_groups_add();
    } else {

      ?>
      <div class="wrap">
        <h2>
          <?php _e("Mailing Lists", 'wpmst-mailster'); ?>
          <a href="<?php echo admin_url(); ?>admin.php?page=mst_mailing_list_add"
             class="add-new-h2"><?php _e("Add New", "wpmst-mailster"); ?></a>
        </h2>

        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
              <div class="meta-box-sortables ui-sortable">
                <form method="get">
                  <input type="hidden" name="page"
                         value="<?php echo sanitize_text_field($_REQUEST['page']); ?>"/>
                  <?php
                  $this->mailingLists_obj->prepare_items();
                  $this->mailingLists_obj->search_box(__('search', 'wpmst-mailster'), 'search_box');
                  $this->mailingLists_obj->display(); ?>
                </form>
              </div>
            </div>
          </div>
          <br class="clear">
        </div>
      </div>
      <?php
    }
  }

  /**
   * Mailing List Screen options
   */
  public function mst_mailing_list_screen_option()
  {

    $option = 'per_page';
    $args = array(
      'label' => 'Mailing Lists',
      'default' => 20,
      'option' => 'edit_post_per_page'
    );

    add_screen_option($option, $args);

    $this->mailingLists_obj = new Mst_mailing_lists();
  }

  /*********************************************\
   *********************************************
   *
   * Settings
   *********************************************
   * \*********************************************/

  /**
   * Settings Page
   */
  public function mst_settings_page()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/settings/mst_settings.php";
  }

  /*********************************************\
   *********************************************
   *
   * Diagnosis
   *********************************************
   * \*********************************************/

  /**
   * Settings Page
   */
  public function wpmst_mailster_diagnosis_page()
  {
    if (isset($_GET['action']) && $_GET['action'] == "fixdb") {
      include $this->WPMST_PLUGIN_DIR . "/view/diagnosis/fixcollation.php";
    } else {
      include $this->WPMST_PLUGIN_DIR . "/view/diagnosis/mst_diagnosis.php";
    }
  }

  /*********************************************\
   *********************************************
   *
   * Queued Emails
   *********************************************
   * \*********************************************/


  /**
   * Queued Page
   */
  public function mst_queued_page()
  {
    if (isset($_GET['subpage']) && $_GET['subpage'] == "details") {
      $this->mst_email_page();
    } else {
      ?>
      <div class="wrap">
        <h2>
          <?php _e("Queued Emails", 'wpmst-mailster'); ?>
        </h2>
        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
              <div class="meta-box-sortables ui-sortable">
                <form method="get" class="admin-table-with-custom-checks">
                  <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']); ?>"/>
                  <?php
                  $this->queued_obj->prepare_items();
                  $this->queued_obj->search_box(__('search', 'wpmst-mailster'), 'search_box');
                  $this->queued_obj->display();
                  ?>
                </form>
              </div>
            </div>
          </div>
          <br class="clear">
        </div>
      </div>
      <div id="dialog-confirm-delete" title="<?php echo __('Confirmation Required') ?>" class="hidden">
        <?php echo __('Are you sure you want to delete the selected queue entries?') ?>
      </div>
      <div id="dialog-confirm-clear" title="<?php echo __('Confirmation Required') ?>" class="hidden">
        <?php echo __('Are you sure you want to delete ALL queue entries?') ?>
      </div>
      <?php
    }
  }

  /**
   * Queued Screen options
   */
  public function mst_queued_screen_option()
  {

    $option = 'per_page';
    $args = array(
      'label' => 'Queued Emails',
      'default' => 20,
      'option' => 'edit_post_per_page'
    );

    add_screen_option($option, $args);

    $this->queued_obj = new Mst_queued();
  }

  /*********************************************\
   *********************************************
   *
   * Archived Emails
   *********************************************
   * \*********************************************/


  /**
   * Archive Page
   */
  public function mst_archived_page()
  {
    if (isset($_GET['subpage']) && $_GET['subpage'] == "details") {
      $this->mst_email_page();
    } else if (isset($_GET['subpage']) && $_GET['subpage'] == "resend") {
      $this->mst_resend();
    } else {
      ?>
      <div class="wrap">
        <h2>
          <?php _e("Archived Emails", 'wpmst-mailster'); ?>
        </h2>

        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
              <div class="meta-box-sortables ui-sortable">
                <form method="get" id="archivedMailsForm">
                  <input type="hidden" name="page"
                         value="<?php echo sanitize_text_field($_REQUEST['page']); ?>"/>
                  <?php
                  $this->archive_obj->prepare_items();
                  $this->archive_obj->search_box(__('search', 'wpmst-mailster'), 'search_box');
                  $this->archive_obj->display(); ?>
                </form>
              </div>
            </div>
          </div>
          <br class="clear">
        </div>
      </div>
      <script>
        jQuery(document).ready(function () {
          jQuery('form#archivedMailsForm').submit(function (e) {
            if (jQuery('#bulk-action-selector-top').val() === 'bulk_resend') {
              e.preventDefault();
              var nonce = '<?php echo wp_create_nonce('mst_resend_archived'); ?>';
              var checked = jQuery('input[name="bulk-action[]"]:checked');
              var url = 'admin.php?page=mst_archived&subpage=resend&_nonce=' + nonce;
              jQuery(checked).each(function (i, c) {
                url += '&eid[]=' + jQuery(c).val();
              });
              document.location.href = url;
            }

          });
        });
        jQuery('.ewc-filter-cat').live('change', function () {
          var catFilter = jQuery(this).val();
          document.location.href = 'admin.php?page=mst_archived&state=' + catFilter;
        });
      </script>
      <?php
    }
  }

  /**
   * Archived Screen options
   */
  public function mst_archived_screen_option()
  {

    $option = 'per_page';
    $args = array(
      'label' => 'Archived Emails',
      'default' => 20,
      'option' => 'edit_post_per_page'
    );

    add_screen_option($option, $args);

    $this->archive_obj = new Mst_archived();
  }

  /**
   * Resend archived emails
   */
  public function mst_resend()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/mail/mst_resend.php";
  }

  /*********************************************\
   *********************************************
   *
   * Servers
   *********************************************
   * \*********************************************/


  /**
   * Servers Page
   */
  public function mst_servers_page()
  {
    if (isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
      $this->mst_servers_add();
    } else {
      ?>
      <div class="wrap">
        <h2>
          <?php _e("Servers", 'wpmst-mailster'); ?>
          <a href="<?php echo admin_url(); ?>admin.php?page=mst_servers_add"
             class="add-new-h2"><?php _e("Add New", "wpmst-mailster"); ?></a>
        </h2>

        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
              <div class="meta-box-sortables ui-sortable">
                <form method="get">
                  <input type="hidden" name="page"
                         value="<?php echo sanitize_text_field($_REQUEST['page']); ?>"/>
                  <?php
                  $this->server_obj->prepare_items();
                  $this->server_obj->search_box(__('search', 'wpmst-mailster'), 'search_box');
                  $this->server_obj->display(); ?>
                </form>
              </div>
            </div>
          </div>
          <br class="clear">
        </div>
      </div>
      <?php
    }
  }

  /**
   * Servers Screen options
   */
  public function mst_servers_screen_option()
  {

    $option = 'per_page';
    $args = array(
      'label' => 'Servers',
      'default' => 20,
      'option' => 'edit_post_per_page'
    );

    add_screen_option($option, $args);

    $this->server_obj = new Mst_servers();
  }

  /*********************************************\
   *********************************************
   *
   * Users
   *********************************************
   * \*********************************************/

  /**
   * Users Page
   */
  public function mst_users_page()
  {
    if (isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
      $this->mst_users_add();
    } else {
      ?>
      <div class="wrap">
        <h2>
          <?php _e("Users", 'wpmst-mailster'); ?>
          <a href="<?php echo admin_url(); ?>admin.php?page=mst_users_add"
             class="add-new-h2"><?php _e("Add New", "wpmst-mailster"); ?></a>
        </h2>

        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
              <div class="meta-box-sortables ui-sortable">
                <form method="get">
                  <input type="hidden" name="page"
                         value="<?php echo sanitize_text_field($_REQUEST['page']); ?>"/>
                  <?php
                  $this->users_obj->prepare_items();
                  $this->users_obj->search_box(__('search', 'wpmst-mailster'), 'search_box');
                  $this->users_obj->display(); ?>
                </form>
              </div>
            </div>
          </div>
          <br class="clear">
        </div>
      </div>
      <?php
    }
  }

  /**
   * Users Screen options
   */
  public function mst_users_screen_option()
  {

    $option = 'per_page';
    $args = array(
      'label' => 'Users',
      'default' => 20,
      'option' => 'edit_post_per_page'
    );

    add_screen_option($option, $args);

    $this->users_obj = new Mst_users();
  }

  /*********************************************\
   *********************************************
   *
   * Groups
   *********************************************
   * \*********************************************/


  /**
   * Groups Page
   */
  public function mst_groups_page()
  {
    if (isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
      $this->mst_groups_add();
    } else {

      ?>
      <div class="wrap">
        <h2>
          <?php _e("Groups", 'wpmst-mailster'); ?>
          <a href="<?php echo admin_url(); ?>admin.php?page=mst_groups_add"
             class="add-new-h2"><?php _e("Add New", "wpmst-mailster"); ?></a>
        </h2>

        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
              <div class="meta-box-sortables ui-sortable">
                <form method="post">
                  <?php
                  $this->groups_obj->prepare_items();
                  $this->groups_obj->search_box(__('search', 'wpmst-mailster'), 'search_box');
                  $this->groups_obj->display(); ?>
                </form>
              </div>
            </div>
          </div>
          <br class="clear">
        </div>
      </div>
      <?php
    }
  }

  /**
   * Groups Screen options
   */
  public function mst_groups_screen_option()
  {

    $option = 'per_page';
    $args = array(
      'label' => 'Groups',
      'default' => 20,
      'option' => 'edit_post_per_page'
    );

    add_screen_option($option, $args);

    $this->groups_obj = new Mst_groups();
  }


  /*********************************************\
   *********************************************
   *
   * Fields
   *********************************************
   * \*********************************************/

  function mst_display_hidden_field($name, $value)
  {
    ?>
    <input type="hidden" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <?php
  }

  function mst_display_input_field($title, $name, $value, $placeholder = null, $required = false, $isSmall = false, $info = null, $readonly = false)
  {
    ?>
    <tr class="<?php if ($required) {
      echo 'form-required';
    } ?>">
      <th scope="row">
        <label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
      </th>
      <td>
        <input type="text"
               name="<?php echo $name; ?>"
               value="<?php echo $value; ?>"
               placeholder="<?php echo $placeholder; ?>"
          <?php echo($required == true ? 'required' : ''); ?>
          <?php echo($info != null ? 'info="' . $info . '"' : ''); ?>
               class="<?php echo($isSmall == true ? 'small-text' : 'regular-text'); ?>"
               id="<?php echo $name; ?>"
          <?php echo($readonly ? 'disabled="disabled"' : ''); ?>
        >
        <?php if ($required) { ?>
          <span class="mst_required">*</span>
        <?php } ?>
        <?php if ($info) { ?>
          <span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
        <?php } ?>
      </td>
    </tr>
    <?php
  }

  function mst_display_textarea_field($title, $name, $value, $placeholder = null, $required = false, $info = null, $readonly = false)
  {
    ?>
    <tr class="<?php if ($required) {
      echo 'form-required';
    } ?>">
      <th scope="row">
        <label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
      </th>
      <td>
				<textarea
          name="<?php echo $name; ?>"
          placeholder="<?php echo $placeholder; ?>"
          <?php echo($required == true ? 'required' : ''); ?>
          <?php echo($info != null ? 'info="' . $info . '"' : ''); ?>
          class=""
          id="<?php echo $name; ?>"><?php echo $value; ?></textarea>
        <?php if ($required) { ?>
          <span class="mst_required">*</span>
        <?php } ?>
        <?php if ($info) { ?>
          <span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
        <?php } ?>
      </td>
    </tr>
    <?php
  }

  function mst_display_password_field($title, $name, $value, $placeholder = null, $required = false, $isSmall = false, $info = null)
  {
    ?>
    <tr class="<?php if ($required) {
      echo 'form-required';
    } ?>">
      <th scope="row">
        <label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
      </th>
      <td>
        <input type="password"
               name="<?php echo $name; ?>"
               value="<?php echo $value; ?>"
               placeholder="<?php echo $placeholder; ?>"
          <?php echo($required == true ? 'required' : ''); ?>
          <?php echo($info != null ? 'info="' . $info . '"' : ''); ?>
               class="<?php echo($isSmall == true ? 'small-text' : 'regular-text'); ?>"
               id="<?php echo $name; ?>"
        >
        <?php if ($required) { ?>
          <span class="mst_required">*</span>
        <?php } ?>
        <?php if ($info) { ?>
          <span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
        <?php } ?>
      </td>
    </tr>
    <?php
  }

  function mst_display_select_field($title, $name, $options = array(), $value, $placeholder = null, $required = false, $info = null, $readonlyOptions = array(), $disabled = false)
  {
    ?>
    <tr class="<?php if ($required) {
      echo 'form-required';
    } ?>">
      <th scope="row">
        <label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
      </th>
      <td>
        <select name="<?php echo $name; ?>" id="<?php echo $name; ?>" <?php if ($required) {
          echo "required";
        } ?> <?php if ($disabled) {
          echo 'disabled="disabled"';
        } ?> >
          <?php
          foreach ($options as $key => $option_value) {
            $readonly = "";
            if (!empty($readonlyOptions)) {
              if (in_array($key, $readonlyOptions)) {
                $readonly = "disabled";
              }
            } ?>
            <option
              value="<?php echo $key; ?>" <?php echo($value == $key ? 'selected' : ''); ?> <?php echo $readonly; ?> ><?php echo $option_value; ?></option>
          <?php } ?>
        </select>
        <?php if ($required) { ?>
          <span class="mst_required">*</span>
        <?php } ?>
        <?php if ($info) { ?>
          <span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
        <?php } ?>
      </td>
    </tr>
    <?php
  }

  function mst_display_truefalse_field($title, $name, $checked = false, $required = false, $info = null)
  {
    ?>
    <tr class="<?php if ($required) {
      echo 'form-required';
    } ?>">
      <th scope="row">
        <?php echo $title; ?>
      </th>
      <td>
        <label for="<?php echo $name; ?>Yes">
          <input type='radio'
                 name='<?php echo $name; ?>'
                 id="<?php echo $name; ?>Yes"
            <?php echo($checked == true ? 'checked' : ''); ?>
                 value="1"
          >
          <?php _e("Yes", 'wpmst-mailster'); ?>
        </label>
        <label for="<?php echo $name; ?>No">
          <input type='radio'
                 name='<?php echo $name; ?>'
                 id="<?php echo $name; ?>No"
            <?php echo($checked == false ? 'checked' : ''); ?>
                 value="0"
          >
          <?php _e("No", 'wpmst-mailster'); ?>
        </label>
        <?php if ($info) { ?>
          <span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
        <?php } ?>
      </td>
    </tr>
    <?php
  }

  function mst_display_sometext($title, $someText, $info = null)
  {
    ?>
    <tr class="">
      <th scope="row">
        <?php echo $title; ?>
      </th>
      <td>
        <label><?php echo $someText; ?></label>
        <?php if ($info) { ?>
          <span class="hTip" title="<?php echo esc_attr($info); ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
        <?php } ?>
      </td>
    </tr>
    <?php
  }

  function mst_display_spacer()
  {
    ?>
    <tr class="">
      <th scope="row">
        &nbsp;
      </th>
      <td>
        <label>&nbsp;</label>
      </td>
    </tr>
    <?php
  }

  function mst_display_radio_field($title, $name, $value, $id, $text, $checked = false, $required = false)
  {
    ?>
    <tr class="<?php if ($required) {
      echo 'form-required';
    } ?>">
      <th scope="row">
        <?php echo $title; ?>
      </th>
      <td>
        <label for="<?php echo $id; ?>">
          <input type='radio' name='<?php echo $name; ?>'
                 id="<?php echo $id; ?>" <?php if ($checked) echo 'selected'; ?> value="<?php echo $value; ?>">
          <?php echo $text; ?>
        </label>
      </td>
    </tr>
    <?php
  }

  function mst_display_multiple_radio_fields($title, $name, $fields, $checked = 0, $required = false)
  {
    ?>
    <tr class="<?php if ($required) {
      echo 'form-required';
    } ?>">
      <th scope="row">
        <?php echo $title; ?>
      </th>
      <td>
        <?php foreach ($fields as $field) { ?>
          <label for="<?php echo $field->id; ?>">
            <input type='radio' name='<?php echo $name; ?>'
                   id="<?php echo $field->id; ?>" <?php echo($checked == $field->value ? 'checked' : ''); ?> <?php echo($field->title != null ? 'title="' . $field->title . '"' : ''); ?>
                   value="<?php echo $field->value; ?>">
            <?php echo $field->text; ?>
          </label>
          <br>
        <?php } ?>
      </td>
    </tr>
    <?php
  }

  function mst_display_simple_radio_field($name, $value, $id, $text, $checked = false, $required = false)
  {
    ?>
    <label for="<?php echo $id; ?>">
      <input type='radio' name='<?php echo $name; ?>' id="<?php echo $id; ?>" <?php if ($checked) echo 'checked'; ?>
             value="<?php echo $value; ?>">
      <?php echo $text; ?>
    </label>
    <?php
  }


  public function wpmst_mailster_intro()
  {
    if (isset($_GET['subpage']) && $_GET['subpage'] == "diagnosis") {
      if (isset($_GET['action']) && $_GET['action'] == "fixdb") {
        include $this->WPMST_PLUGIN_DIR . "/view/diagnosis/fixcollation.php";
      } else {
        include $this->WPMST_PLUGIN_DIR . "/view/diagnosis/mst_diagnosis.php";
      }
    } else if (isset($_GET['subpage']) && $_GET['subpage'] == "import") {
      include $this->WPMST_PLUGIN_DIR . "/view/csv/import.php";
    } else if (isset($_GET['subpage']) && $_GET['subpage'] == "export") {
      include $this->WPMST_PLUGIN_DIR . "/view/csv/export.php";
    } else {
      include $this->WPMST_PLUGIN_DIR . "/view/stats/mst_intro.php";
    }
  }

  public function mst_email_page()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/mail/mst_emaildetails.php";
  }

  /* mailing List groups */
  public function wpmst_list_groups()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/mst_list_groups.php";
  }

  /* Add List Groups */
  public function mst_list_groups_add()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/list/mst_list_groups_add.php";
  }

  /* Add Groups */
  public function mst_groups_add()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/groups/mst_groups_add.php";
  }

  /* Add Servers */
  public function mst_servers_add()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/servers/mst_servers_add.php";
  }

  /* Add Users */
  public function mst_users_add()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/users/mst_users_add.php";
  }

  /* Add mailing List */
  public function mst_mailing_list_add()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/list/mst_mailing_list_add.php";
  }

  /* Manage List Recipients */
  public function mst_recipient_management()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/list/mst_recipient_management.php";
  }

  /* Add List Recipients */
  public function mst_list_members_add()
  {
    include $this->WPMST_PLUGIN_DIR . "/view/list/mst_list_members_add.php";
  }


  public function wpmst_view_message($type, $message)
  {
    return "<div class='" . $type . "'><p><strong>" . $message . "</strong></p></div>";
  }

  public function wpmst_print_messages($messages)
  {
    if ($messages && is_array($messages) && count($messages) > 0) {
      foreach ($messages AS $message) {
        echo $message;
      }
    } else {
      if ($messages && is_scalar($messages)) {
        echo $messages;
      }
    }
  }

  /*perpage limit*/
  function wpmst_perpage_limit($page)
  {
    $perpage = 5;
    $start = ($page - 1) * $perpage;
    return array($perpage, $start);
  }

  /*pagination*/
  function wpmst_get_pagination($total_count, $page, $perpage)
  {
    $prevLinks = 'disabled';
    $nextLinks = 'disabled';
    $prev_page = '';
    $next_page = '';
    $last_page = '';

    $pages = ceil($total_count / $perpage);

    if ($pages > 1) {
      $last_page = $pages;
      $prev_page = $page - 1;
      $next_page = $page + 1;
      if ($page == 1 || $page > 1) {
        $nextLinks = 'wptl_pagi_nav';
        $prev_page = '';
      }
      if ($page > 1) {
        $prevLinks = 'wptl_pagi_nav';
        $prev_page = $page - 1;
        $next_page = $page + 1;
      }
      if ($page == $pages) {
        $nextLinks = 'disabled';
        $prev_page = $page - 1;
        $next_page = '';
        $last_page = '';
      }

    }
    return array($pages, $prevLinks, $nextLinks, $prev_page, $next_page, $last_page);
  }

  /*Handling ajax request*/
  public function wpmst_pagination_request()
  {
    $currentUrl = sanitize_text_field($_POST['currentUrl']);
    $paged = intval($_POST['paged']);
    if (!$paged) {
      $paged = 0;
    }
    $url_array = parse_url($currentUrl);

    /* modify current url query string. */
    $query_array = array();
    if (!empty($url_array['query'])) {
      parse_str($url_array['query'], $query_array);
      if (array_key_exists('paged', $query_array)) unset($query_array['paged']);
      $query_array['paged'] = $paged;
    }

    $paramString = http_build_query($query_array);
    $new_url = $url_array['scheme'] . '://' . $url_array['host'] . $url_array['path'] . '?' . $paramString;
    echo $new_url;
    die;
  }

  public function wpmst_active_request()
  {
    global $wpdb;
    $id = intval($_POST['id']);
    if (null === $id) {
      die;
    }
    $active = intval($_POST['active']);
    if (null === $active) {
      die;
    }
    echo $result = $wpdb->update($this->mailster_lists, array('active' => $active), array('id' => $id));
    die;
  }

  public function wpmst_delete_users()
  {
    global $wpdb;
    $delposts = $_POST['deleteid'];
    foreach ($delposts as $delpost) {
      $delpost = intval($delpost);
      if (null !== $delpost) {
        $table = $wpdb->base_prefix . "mailster_users";
        $wpdb->delete($table, array('ID' => $delpost));
      }
    }
    die;
  }

  public function wpmst_delete_groups()
  {
    global $wpdb;
    $delgroups = $_POST['deleteid'];
    foreach ($delgroups as $delgroup) {
      $delgroup = intval($delgroup);
      if (null !== $delgroup) {
        $table = $wpdb->base_prefix . "mailster_groups";
        $wpdb->delete($table, array('ID' => $delgroup));
      }
    }
    die;
  }

  public function wpmst_delete_lists()
  {
    global $wpdb;
    $delgroups = $_POST['deleteid'];
    foreach ($delgroups as $delgroup) {
      $delgroup = intval($delgroup);
      if (null !== $delgroup) {
        $table = $wpdb->base_prefix . "mailster_lists";
        $wpdb->delete($table, array('ID' => $delgroup));
      }
    }
    die;
  }

  public function wpmst_activate_lists()
  {
    global $wpdb;
    $delgroups = $_POST['activateid'];
    foreach ($delgroups as $delgroup) {
      $delgroup = intval($delgroup);
      if (null !== $delgroup) {
        $table = $wpdb->base_prefix . "mailster_lists";
        $wpdb->update($table, array('active' => 1), array('ID' => $delgroup));
      }
    }
    die;
  }

  public function wpmst_deactivate_lists()
  {
    global $wpdb;
    $delgroups = $_POST['deactivateid'];
    foreach ($delgroups as $delgroup) {
      $delgroup = intval($delgroup);
      if (null !== $delgroup) {
        $table = $wpdb->base_prefix . "mailster_lists";
        $wpdb->update($table, array('active' => 0), array('ID' => $delgroup));
      }
    }
    die;
  }

  public function wpmst_delete_user_list()
  {
    global $wpdb;
    $delgroups = $_POST['deleteid'];
    foreach ($delgroups as $delgroup) {
      $delgroup = intval($delgroup);
      if (null !== $delgroup) {
        $table = $wpdb->base_prefix . "mailster_list_members";
        $wpdb->delete($table, array('user_id' => $delgroup));
      }
    }
    die;
  }

  public function wpmst_delete_user_group()
  {
    global $wpdb;
    $delgroups = $_POST['deleteid'];
    $groupid = intval($_POST['groupid']);
    if (null !== $groupid) {
      foreach ($delgroups as $delgroup) {
        $delgroup = intval($delgroup);
        if (null !== $delgroup) {
          $wpdb->query("delete from {$wpdb->prefix}mailster_group_users WHERE user_id=$delgroup AND group_id=$groupid");
        }
      }
    }
    die;
  }

  public function wpmst_delete_group_list()
  {
    global $wpdb;
    $delgroups = $_POST['deleteid'];
    foreach ($delgroups as $delgroup) {
      $delgroup = intval($delgroup);
      if (null !== $delgroup) {
        $table = $wpdb->base_prefix . "mailster_list_groups";
        $wpdb->delete($table, array('group_id' => $delgroup));
      }
    }
    die;
  }

  public function wpmst_delete_notify()
  {
    $notifyId = intval($_POST['notifyId']);
    $rowNr = intval($_POST['rowNr']);
    $notifyUtils = MstFactory::getNotifyUtils();
    $mstUtils = MstFactory::getUtils();
    $res = ($notifyUtils->deleteNotify($notifyId) ? 'true' : 'false');
    $resultArray = array();
    $resultArray['res'] = $res;
    $resultArray['notifyId'] = $notifyId;
    $resultArray['rowNr'] = $rowNr;
    $jsonStr = $mstUtils->jsonEncode($resultArray);
    echo $jsonStr;
    die;

  }

  public function wpmst_subscribe_plugin()
  {
    check_ajax_referer('wpmst_subscribe_plugin_nonce');
    global $wpdb;
    $log = MstFactory::getLogger();
    $listUtils = MstFactory::getMailingListUtils();
    $subscrUtils = MstFactory::getSubscribeUtils();
    $mstUtils = MstFactory::getUtils();
    $resultObj = new stdClass();
    $res = 'Ajax called';
    $log->debug('wpmst_subscribe_plugin POST: ' . print_r($_POST, true));

    $formId = sanitize_text_field($_REQUEST[MstConsts::SUBSCR_POST_IDENTIFIER]);
    $name = sanitize_text_field($_REQUEST[MstConsts::SUBSCR_NAME_FIELD]);
    $email = sanitize_email($_REQUEST[MstConsts::SUBSCR_EMAIL_FIELD]);
    $listId = intval($_REQUEST[MstConsts::SUBSCR_ID_FIELD]);
    $digest = 0;
    if (isset($_REQUEST[MstConsts::SUBSCR_DIGEST_FIELD])) {
      $digest = sanitize_text_field($_REQUEST[MstConsts::SUBSCR_DIGEST_FIELD]);
    } else {
      $digest = MstConsts::DIGEST_NO_DIGEST;
    }

    $subscribeFormsInSession = array_key_exists('wpmst_subscribe_forms', $_SESSION) ? $_SESSION['wpmst_subscribe_forms'] : array();
    $log->debug('wpmst_subscribe_plugin subscribeFormsInSession: ' . print_r($subscribeFormsInSession, true));

    $errors = 0;
    $resultMsg = '';
    $errorMsgs = array();
    $res = false;
    $foundInSession = false;
    $captchaRes = '';
    $foundSessionKeyIndex = -1;
    if ($subscribeFormsInSession && is_array($subscribeFormsInSession)) {
      foreach ($subscribeFormsInSession AS $sessionKeyIndex => $formInSession) {
        if (property_exists($formInSession, 'id') && $formInSession->id === $formId) {
          $log->debug('Found in session: ' . $formId);
          $foundInSession = true;
          $foundSessionKeyIndex = $sessionKeyIndex;
          $captchaRes = property_exists($formInSession, 'captcha') ? $formInSession->captcha : false;
          $add2Group = property_exists($formInSession, 'subscribeAdd2Group') ? $formInSession->subscribeAdd2Group : 0;
          $submitTxt = property_exists($formInSession, 'submitTxt') ? $formInSession->submitTxt : 'SUBMITTED OKAY!!!111';
          $submitConfirmTxt = property_exists($formInSession, 'submitConfirmTxt') ? $formInSession->submitConfirmTxt : 'SUBMITTED, NEED TO CONFIRM OKAY!!!111';
          $errorTxt = property_exists($formInSession, 'errorTxt') ? $formInSession->errorTxt : 'PROBLEM SUBSCRIBING!!!111';
        }
      }
    }

    $noName = array('id' => 'no_name', 'msg' => __('Please provide a name', "wpmst-mailster"));
    $noEmail = array('id' => 'no_email', 'msg' => __('Please provide your email address', "wpmst-mailster"));
    $invalidEmail = array('id' => 'invalid_email', 'msg' => __('Invalid email address', "wpmst-mailster"));
    $noListChosen = array('id' => 'no_list', 'msg' => __('You have no mailing list chosen', "wpmst-mailster"));
    $tooMuchRecipients = array('id' => 'too_much_recip', 'msg' => __('Too many recipients (Product limit)', "wpmst-mailster"));
    $registrationInactive = array('id' => 'reg_inactive', 'msg' => __('Registration currently not possible', "wpmst-mailster"));
    $registrationOnlyForRegisteredUsers = array('id' => 'reg_only_registered', 'msg' => __('Subscribing not allowed for unregistered users. Please login first.', "wpmst-mailster"));
    $captchaCodeWrong = array('id' => 'captcha_wrong', 'msg' => __('The captcha code you entered was wrong, please try again', "wpmst-mailster"));

    if ($foundInSession) {

      $log->debug('wpmst_subscribe_plugin Post data: name=' . $name . ', email=' . $email . ', digest=' . $digest . ', listId=' . $listId);

      if ($email === "") {
        $errorMsgs[] = $noEmail;
        $errors++;
      } else if (!preg_match("/^.+?@.+$/", $email)) {
        $errorMsgs[] = $invalidEmail;
        $errors++;
      }
      if (($listId === "") || ($listId <= 0)) {
        $errorMsgs[] = $noListChosen;
        $errors++;
      }

      $captchaValid = true;
      if ($captchaRes) {
        $mstCaptcha = $mstUtils->getCaptcha($captchaRes);
        $captchaValid = $mstCaptcha->isValid();
      }
      if ($captchaValid == false) {
        $errorMsgs[] = $captchaCodeWrong;
        $errors++;
      }

      if ($errors <= 0) {
        $log->debug('wpmst_subscribe_plugin No errors in Post detected');

        if (($name === "")) { // name unknown and doesn't need to be supplied
          $name = $email; // copy email in name to have something in the DB as the name
        }

        $list = $listUtils->getMailingList($listId);
        if ($list->allow_subscribe == '1') {
          if ($list->public_registration == '1' || ($subscrUtils->isUserLoggedIn())) {

            if ($list->subscribe_mode != MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN) {
              // default subscription
              $log->debug('Double Opt-in subscribe mode not activated');
              $log->debug('All OK, we can insert in DB');
              $resultMsg = $submitTxt;
              $success = $subscrUtils->subscribeUser($name, $email, $listId, $digest); // subscribing user...
              $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($name, $email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE);
              if ($success == false) {
                $mstRecipients = MstFactory::getRecipients();
                $cr = $mstRecipients->getTotalRecipientsCount($listId);
                if ($cr >= MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC)) {
                  $errors = $errors + 1;
                  $log->debug('Too many recipients!');
                  $errorMsgs[] = $tooMuchRecipients;
                }
              } else {
                if ($add2Group) {
                  $log->debug('User is added to group ' . $add2Group . ' after subscribe');
                  $subscrUser = $subscrUtils->getUserByEmail($email, true);
                  $subscrUser->group_id = $add2Group;
                  $log->debug(print_r($subscrUser, true));
                  $groupUserModel = MstFactory::getGroupUsersModel();
                  $add2GroupSuccess = $groupUserModel->store($subscrUser);
                  $log->debug('User added to group: ' . ($add2GroupSuccess ? 'Yes' : 'No'));
                }
                // ####### TRIGGER NEW EVENT #######
                $mstEvents = MstFactory::getEvents();
                $mstEvents->userSubscribedOnWebsite($name, $email, $listId);
                // #################################
                $res = true;
              }
            } else {
              $log->debug('Double Opt-in subscribe mode');
              $resultMsg = $submitConfirmTxt;
              $subscrUtils->subscribeUserWithDoubleOptIn($email, $name, $listId, $add2Group, $digest);
              $res = true;
            }

          } else {
            $errors = $errors + 1;
            $log->debug('Cannot subscribe - registration is not allowed for not logged in users');
            $errorMsgs[] = $registrationOnlyForRegisteredUsers;
          }
        } else {
          $errors = $errors + 1;
          $log->debug('Cannot subscribe - registration is not allowed!');
          $errorMsgs[] = $registrationInactive;
        }
      } else {
        $log->error('wpmst_subscribe_plugin: Errors in Post detected: ' . print_r($errorMsgs, true));
      }

      if ($res && $foundSessionKeyIndex >= 0) {
        //$log->debug('Removing index no '.$foundSessionKeyIndex.' from session...');
        unset($subscribeFormsInSession[$foundSessionKeyIndex]);
        $subscribeFormsInSession = array_values($subscribeFormsInSession); // re-index
        $_SESSION['wpmst_subscribe_forms'] = $subscribeFormsInSession;
      }

    } else {
      $log->debug('Form ID ' . $formId . ' not found in session');
    }

    if ($errors > 0) {
      $resultMsg = $errorTxt;
    }

    $resultObj->res = $res;
    $resultObj->resultMsg = $resultMsg;
    $resultObj->errorMsgs = $errorMsgs;
    $jsonStr = $mstUtils->jsonEncode($resultObj);
    echo $jsonStr;
    die;
  }


  public function wpmst_unsubscribe_plugin()
  {
    check_ajax_referer('wpmst_unsubscribe_plugin_nonce');
    global $wpdb;
    $log = MstFactory::getLogger();
    $listUtils = MstFactory::getMailingListUtils();
    $subscrUtils = MstFactory::getSubscribeUtils();
    $recips = MstFactory::getRecipients();
    $mstUtils = MstFactory::getUtils();
    $resultObj = new stdClass();
    $res = 'Ajax Unsubscribe Called';

    $log->debug('wpmst_unsubscribe_plugin POST: ' . print_r($_POST, true));
    $subscribeFormsInSession = array_key_exists('wpmst_subscribe_forms', $_SESSION) ? $_SESSION['wpmst_subscribe_forms'] : array();
    $log->debug('wpmst_unsubscribe_plugin subscribeFormsInSession: ' . print_r($subscribeFormsInSession, true));
    $formId = sanitize_text_field($_REQUEST[MstConsts::SUBSCR_POST_IDENTIFIER]);
    $email = sanitize_email($_REQUEST[MstConsts::SUBSCR_EMAIL_FIELD]);
    $listId = intval($_REQUEST[MstConsts::SUBSCR_ID_FIELD]);

    $errors = 0;
    $resultMsg = '';
    $errorMsgs = array();
    $res = false;
    $foundInSession = false;
    $captchaRes = '';
    $foundSessionKeyIndex = -1;
    if ($subscribeFormsInSession && is_array($subscribeFormsInSession)) {
      foreach ($subscribeFormsInSession AS $sessionKeyIndex => $formInSession) {
        if (property_exists($formInSession, 'id') && $formInSession->id === $formId) {
          $foundInSession = true;
          $foundSessionKeyIndex = $sessionKeyIndex;
          $captchaRes = property_exists($formInSession, 'captcha') ? $formInSession->captcha : false;
          $submitTxt = property_exists($formInSession, 'submitTxt') ? $formInSession->submitTxt : 'SUBMITTED OKAY!!!111';
          $submitConfirmTxt = property_exists($formInSession, 'submitConfirmTxt') ? $formInSession->submitConfirmTxt : 'SUBMITTED, NEED TO CONFIRM OKAY!!!111';
          $errorTxt = property_exists($formInSession, 'errorTxt') ? $formInSession->errorTxt : 'PROBLEM UNSUBSCRIBING!!!111';
        }
      }
    }


    $noEmail = array('id' => 'no_email', 'msg' => __('Please provide your email address', "wpmst-mailster"));
    $invalidEmail = array('id' => 'invalid_email', 'msg' => __('Please provide a valid email address', "wpmst-mailster"));
    $noListChosen = array('id' => 'no_list', 'msg' => __('You have no mailing list chosen', "wpmst-mailster"));
    $unsubscribeIncative = array('id' => 'unsub_inactive', 'msg' => __('Unsubscribing currently not possible', "wpmst-mailster"));
    $notSubscribed = array('id' => 'unsub_inactive', 'msg' => __('Email address is not subscribed', "wpmst-mailster"));
    $captchaCodeWrong = array('id' => 'captcha_wrong', 'msg' => __('The captcha code you entered was wrong, please try again', "wpmst-mailster"));


    if ($foundInSession) {

      $log->debug('wpmst_unsubscribe_plugin Post data: email=' . $email . ', listId=' . $listId);

      if ($email === "") {
        $errorMsgs[] = $noEmail;
        $errors++;
      } else if (!preg_match("/^.+?@.+$/", $email)) {
        $errorMsgs[] = $invalidEmail;
        $errors++;
      }
      if (($listId === "") || ($listId <= 0)) {
        $errorMsgs[] = $noListChosen;
        $errors++;
      }

      $captchaValid = true;
      if ($captchaRes) {
        $mstCaptcha = $mstUtils->getCaptcha($captchaRes);
        $captchaValid = $mstCaptcha->isValid();
      }
      if ($captchaValid == false) {
        $errorMsgs[] = $captchaCodeWrong;
        $errors++;
      }

      $list = $listUtils->getMailingList($listId);
      if ($list->allow_unsubscribe == '1') {
        $isRecipient = $recips->isRecipient($listId, $email);
        $log->debug('Check whether person with email ' . $email . ' is recipient of list ' . $listId . ', result: ' . ($isRecipient ? 'Is recipient' : 'NOT a recipient'));

        if ($isRecipient == false) {
          $errorMsgs[] = $notSubscribed;
          $errors++;
        }

        if ($errors <= 0) {
          if ($list->unsubscribe_mode != MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN) {
            $log->debug('Double Opt-in unsubscribe mode not activated');
            $log->debug('All OK, we can delete from DB');
            $resultMsg = $submitTxt;
            $success = $subscrUtils->unsubscribeUser($email, $listId); // unsubscribing user...
            $tmpUser = $subscrUtils->getUserByEmail($email);
            $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
            // ####### TRIGGER NEW EVENT #######
            $mstEvents = MstFactory::getEvents();
            $mstEvents->userUnsubscribedOnWebsite($email, $listId);
            // #################################
            $res = true;
          } else {
            $log->debug('Double Opt-in unsubscribe mode');
            $resultMsg = $submitConfirmTxt;
            $subscrUtils->unsubscribeUserWithDoubleOptIn($email, $listId);
            $res = true;
          }
        } else {
          $log->error('wpmst_unsubscribe_plugin: Errors in Post detected: ' . print_r($errorMsgs, true));
        }
      } else {
        $errors = $errors + 1;
        $log->debug('Cannot unsubscribe - unsubscribing is not allowed!');
        $errorMsgs[] = $unsubscribeIncative;
      }

      if ($res && $foundSessionKeyIndex >= 0) {
        unset($subscribeFormsInSession[$foundSessionKeyIndex]);
        $subscribeFormsInSession = array_values($subscribeFormsInSession); // re-index
        $_SESSION['wpmst_subscribe_forms'] = $subscribeFormsInSession;
      }

    } else {
      $log->debug('Form ID ' . $formId . ' not found in session');
    }

    if ($errors > 0) {
      $resultMsg = $errorTxt;
    }

    $resultObj->res = $res;
    $resultObj->resultMsg = $resultMsg;
    $resultObj->errorMsgs = $errorMsgs;
    $jsonStr = $mstUtils->jsonEncode($resultObj);
    echo $jsonStr;
    die;
  }

}

$wpmst_mailster = new wpmst_mailster(__FILE__);
function mst_get_version()
{
  if (is_admin()) {
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];
  } else {
    $plugin_version = "not run through admin";
  }
  return $plugin_version;
}

function mst_execute_procedures()
{
  $system = new plgSystemMailster();
  $system->onAfterInitialise();
}

add_action('shutdown', 'mst_execute_procedures');

include_once plugin_dir_path(__FILE__) . "conncheck.php";

add_action('wp_ajax_resetplgtimer', 'resetplgtimer_callback');
function resetplgtimer_callback()
{
  $log = MstFactory::getLogger();
  $pluginUtils = MstFactory::getPluginUtils();
  $mstUtils = MstFactory::getUtils();
  $resultArray = array();
  $res = __('Reset timer called', "wpmst-mailster");
  $ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
  $ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
  $task = $ajaxParams->{'task'};
  if ($task == 'resetPlgTimer') {
    if ($pluginUtils->resetMailPluginTimes()) {
      $res = __('Reset') . ' ' . __('Ok');
    } else {
      $res = __('Reset') . ' ' . __('Not Ok');
    }
  } else {
    $res = __('Unknown task') . ': ' . $task;
  }
  $resultArray['checkresult'] = $res;
  $jsonStr = $mstUtils->jsonEncode($resultArray);
  echo "[" . $jsonStr . "]";
  exit();
}

add_action('init', 'wpmst_startSession', 1);
function wpmst_startSession()
{
  if (version_compare(phpversion(), "5.4.0", ">=")) {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
  } else {
    if (session_id() === '') {
      session_start();
    }
  }
}

add_action('wp_logout', 'wpmst_endSession');
add_action('wp_login', 'wpmst_endSession');
function wpmst_endSession()
{
  if (version_compare(phpversion(), "5.4.0", ">=")) {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
  } else {
    if (session_id() === '') {
      session_start();
    }
  }
  session_destroy();
}

add_action('admin_menu', 'nstrm_remove_admin_submenus', 999);
function nstrm_remove_admin_submenus()
{

}

//ajax load of server details
add_action("wp_ajax_wpmst_get_server_data", "wpmst_get_server_data");
function wpmst_get_server_data()
{
  $serverId = intval($_POST['server_id']);
  if (null !== $serverId) {
    $Server = new MailsterModelServer($serverId);
    $ret = $Server->getFormData();
    echo json_encode($ret);
  }
  exit();
}

add_action("wp_ajax_wpmst_deleteLogFile", "wpmst_deleteLogFile");
function wpmst_deleteLogFile()
{
  $log = MstFactory::getLogger();
  $logFile = $log->getLogFile();

  if (unlink($logFile)) {
    $result = __('Deleted Log file', 'wpmst-mailster');
  } else {
    $result = __('Failed to delete Log file', 'wpmst-mailster');
  }
  try {
    $log->initFile();
  } catch (RuntimeException $e) {
    /// no action here
  }
  echo $result;
  exit();
}


add_action('plugins_loaded', function () {
  if (isset($_GET['mst_download'])) {
    $log = MstFactory::getLogger();
    $fileName = null;
    $mstDownload = $_GET['mst_download'];
    if ($mstDownload === "mailster.log") {
      $filePath = $log->getLogFile();
      $fileName = 'mailster.log';
    } elseif ($mstDownload === 'attachment') {
      $attachId = intval($_GET['id']);
      $attachUtils = MstFactory::getAttachmentsUtils();
      $attach = $attachUtils->getAttachment($attachId);
      $upload_dir = wp_upload_dir();
      $filePath = $upload_dir['basedir'] . $attach->filepath . DIRECTORY_SEPARATOR . $attach->filename;
      $fileName = $attach->filename;
    }
    if (!is_null($filePath)) {
      // $log->debug('Download '.$fileName.' in '.$filePath);
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="' . rawurldecode($fileName) . '"');
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Length: ' . filesize($filePath));
      ob_clean();
      flush();
      readfile($filePath);
      exit;
    }
  }
});

// profile short code is always available
add_shortcode('mst_profile', array('mst_frontend_mailing_list', 'mst_profile'));
include_once(plugin_dir_path(__FILE__) . "shortcodes/mst_view_mailing_list.php");

//shortcodes in Society and Enterprise
/* @@REMOVE_START_MST_free@@ */
/* @@REMOVE_START_MST_club@@ */
add_shortcode('mst_mailing_lists', array('mst_frontend_mailing_list', 'mst_mailing_lists_frontend'));
add_shortcode('mst_emails', array('mst_frontend_mailing_list', 'mst_emails_frontend'));
/* @@REMOVE_END_MST_club@@ */
/* @@REMOVE_END_MST_free@@ */


/* @@REMOVE_START_MST_society@@ */
/* @@REMOVE_START_MST_enterprise@@ */
// add_shortcode( 'mst_mailing_lists', array( 'mst_frontend_mailing_list', 'mst_shortcode_not_available_mst_mailing_lists' ) );
// add_shortcode( 'mst_emails', array( 'mst_frontend_mailing_list', 'mst_shortcode_not_available_mst_emails' ) );
/* @@REMOVE_END_MST_enterprise@@ */
/* @@REMOVE_END_MST_society@@ */


/*  */


//message for free version downgrade
function wpmst_admin_notice()
{
  if (get_option('version_license') == 'free' && get_option('current_version') != 'free') {
    ?>
    <div class="notice notice-warning is-dismissible">
      <p><?php _e('Your Mailster version will become free the next time you update it. Go to <a href="admin.php?page=wpmst_settings">WPMailster settings</a> to add your serial key', 'wpmst-mailster'); ?></p>
    </div>
    <?php
  }
}

/*  */


/*  */


//double opt in url
add_action('wp_loaded', 'wpmst_double_opt_check');
function wpmst_double_opt_check()
{
  $log = MstFactory::getLogger();
  $recips = MstFactory::getRecipients();
  $listUtils = MstFactory::getMailingListUtils();
  $hashUtils = MstFactory::getHashUtils();
  $subscribeUtils = MstFactory::getSubscribeUtils();
  if (isset($_GET['confirm_subscribe']) && $_GET['confirm_subscribe'] == "indeed") {
    $log->debug('indeed - subscribe: ' . print_r($_REQUEST, true));
    $successful = 0;
    if (isset($_REQUEST['sm'])) {
      $subscribeMode = intval($_REQUEST['sm']);
    } else {
      $subscribeMode = 0;
    }
    if (isset($_REQUEST['sa'])) {
      $salt = intval($_REQUEST['sa']);
    } else {
      $salt = rand();
    }
    if (isset($_REQUEST['h'])) {
      $hash = sanitize_text_field($_REQUEST['h']);
    } else {
      $hash = "";
    }
    if ($subscribeMode == 0) {
      $log->debug('Default subscribe, not yet existing...');
    } elseif ($subscribeMode == MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN) {
      if (isset($_REQUEST["si"])) {
        $subscriptionId = intval($_REQUEST['si']);
      } else {
        $subscriptionId = 0;
      }
      $log->debug('Double Opt-In subscribe');
      $subscribeInfo = $subscribeUtils->getSubscribeInfo($subscriptionId);
      if (!is_null($subscribeInfo)) {
        $log->debug('Found subscribe info: ' . print_r($subscribeInfo, true));
        $saltedKeyHash = $hashUtils->getSubscribeKey($salt, $subscribeInfo->hashkey);
        $hashOk = ($saltedKeyHash == $hash);
      } else {
        $hashOk = false;
      }
      if ($hashOk) {
        $listId = $subscribeInfo->list_id;
        $subscrUtils = MstFactory::getSubscribeUtils();
        $success = $subscrUtils->subscribeUser($subscribeInfo->name, $subscribeInfo->email, $listId, $subscribeInfo->digest_freq);
        $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($subscribeInfo->name, $subscribeInfo->email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE);
        if ($success) {
          $log->debug('Subscribing successful');
          if ($subscribeInfo->add2group) {
            $log->debug('User is added to group ' . $subscribeInfo->add2group . ' after subscribe');

            $subscrUser = $subscrUtils->getUserByEmail($subscribeInfo->email, true);
            $subscrUser->group_id = $subscribeInfo->add2group;
            $log->debug('Subscr User:' . print_r($subscrUser, true));
            $groupUserModel = MstFactory::getGroupUsersModel();
            $add2GroupSuccess = $groupUserModel->store($subscrUser);
            $log->debug('User added to group: ' . ($add2GroupSuccess ? 'Yes' : 'No'));
          }
          // ####### TRIGGER NEW EVENT #######
          $mstEvents = MstFactory::getEvents();
          $mstEvents->userSubscribedOnWebsite($subscribeInfo->name, $subscribeInfo->email, $listId);
          // #################################
          $subscrUtils->deleteSubscriptionInfo($subscribeInfo->id);
          $successful = 1;
        } else {
          $log->debug('Subscribing failed');
          $successful = 0;
        }
      } else {
        $log->debug('Subscribing failed, hash was not correct');
        $successful = 0;
      }
    }
    header("HTTP/1.1 301 Moved Permanently");
    header("Status: 301 Moved Permanently");
    header("Location: " . plugins_url() . "/wp-mailster/view/subscription/subscribe.php?success=" . $successful);
    header("Connection: close");
    exit(0);
  }
  if (isset($_GET['confirm_unsubscribe']) && $_GET['confirm_unsubscribe'] == "indeed") {
    $log->debug('indeed - unsubscribe: ' . print_r($_REQUEST, true));
    if (isset($_REQUEST['sm'])) {
      $unsubscribeMode = intval($_REQUEST['sm']);
    } else {
      $unsubscribeMode = 0;
    }
    if (isset($_REQUEST['sa'])) {
      $salt = intval($_REQUEST['sa']);
    } else {
      $salt = rand();
    }
    if (isset($_REQUEST['h'])) {
      $hash = sanitize_text_field($_REQUEST['h']);
    } else {
      $hash = "";
    }

    $defaultErrorMsg = "";
    $notSubscribed = false;
    $unsubscribeCompletedOk = false;
    $unsubscribeFailed = false;
    $unsubscribeFormNeeded = false;
    $nonce = false;

    if ($unsubscribeMode == 0) {
      $log->debug('Default unsubscribe');
      if (isset($_REQUEST['m'])) {
        $mailId = intval($_REQUEST['m']);
      } else {
        $mailId = 0;
      }
      if (isset($_REQUEST['ea'])) {
        $email = sanitize_text_field($_REQUEST['ea']);
      } else {
        $email = null;
      }
      $listId = $listUtils->getMailingListIdByMailId($mailId);
      $hashOk = $hashUtils->checkUnsubscribeKeyOfMail($mailId, $salt, $hash);
      if ($listId) {
        $list = $listUtils->getMailingList($listId);
        $listName = $list->name;
      } else {
        $listName = null;
      }
      if ($hashOk) {
        $unsubscribeFormNeeded = true;
      } else {
        $unsubscribeFailed = true;
      }

      $_SESSION['nonce_mst_frontend_unsub_confirm'] = wp_create_nonce('mst_frontend_unsub_confirm');
      $_SESSION['hashOk'] = $hashOk;
      $_SESSION['listId'] = $listId;
      $_SESSION['listName'] = $listName;
      $_SESSION['email'] = $email;
      $_SESSION['mailId'] = $mailId;
      $_SESSION['salt'] = $salt;
      $_SESSION['hash'] = $hash;
      $_SESSION['query'] = get_site_url() . '?confirm_unsubscribe=indeed2';

    } elseif ($unsubscribeMode == MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN) {
      if (isset($_REQUEST['si'])) {
        $subscriptionId = intval($_REQUEST['si']);
      } else {
        $subscriptionId = 0;
      }
      $log->debug('Double Opt-In unsubscribe with subscriptionId: ' . $subscriptionId);
      $subscribeInfo = $subscribeUtils->getSubscribeInfo($subscriptionId);
      if (!is_null($subscribeInfo)) {
        $log->debug('Found subscribe info: ' . print_r($subscribeInfo, true));
        $saltedKeyHash = $hashUtils->getUnsubscribeKey($salt, $subscribeInfo->hashkey);
        $hashOk = ($saltedKeyHash == $hash);
      } else {
        $hashOk = false;
        $notSubscribed = true;
        $log->debug('Did NOT find subscribe info');
      }

      if ($hashOk) {
        $log->debug('Hash OK');
        $listId = $subscribeInfo->list_id;
        $subscrUtils = MstFactory::getSubscribeUtils();
        $isRecipient = $recips->isRecipient($listId, $subscribeInfo->email);
        if ($isRecipient) {
          $log->debug($subscribeInfo->email . ' is a recipient of list ' . $listId);
          $success = $subscrUtils->unsubscribeUser($subscribeInfo->email, $listId);
          $tmpUser = $subscrUtils->getUserByEmail($subscribeInfo->email);
          $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $subscribeInfo->email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
          if ($success) {
            $log->debug('Unsubscribing successful');
            // ####### TRIGGER NEW EVENT #######
            $mstEvents = MstFactory::getEvents();
            $mstEvents->userUnsubscribedOnWebsite($subscribeInfo->email, $listId);
            // #################################
            $subscrUtils->deleteSubscriptionInfo($subscribeInfo->id);
            $unsubscribeCompletedOk = true;
          } else {
            $log->debug('Unsubscribing failed');
            $unsubscribeFailed = true;
          }
        } else {
          $log->debug($subscribeInfo->email . ' is NOT a recipient of list ' . $listId);
          $notSubscribed = true;
        }
      } else {
        $log->debug('Hash not ok');
        $unsubscribeFailed = true;
      }
    }


    $query = array();
    if ($notSubscribed) {
      $query[] = 'ns=1';
    }
    if ($unsubscribeCompletedOk) {
      $query[] = 'sc=1';
    }
    if ($unsubscribeFailed) {
      $query[] = 'sf=1';
    }
    if ($unsubscribeFormNeeded) {
      $query[] = 'uf=1';
      if ($email) {
        $query[] = 'ea=' . $email;
      }
      $query[] = 'ln=' . urlencode($listName);
    }
    $queryStr = implode('&', $query);

    header("HTTP/1.1 301 Moved Permanently");
    header("Status: 301 Moved Permanently");
    header("Location: " . plugins_url() . "/wp-mailster/view/subscription/unsubscribe.php?" . $queryStr);
    header("Connection: close");
    exit(0);
  }
  if (isset($_GET['confirm_unsubscribe']) && $_GET['confirm_unsubscribe'] == "indeed2") {
    $log->debug('indeed2 - unsubscribe');
    $listId = intval($_SESSION['listId']);
    $mailId = intval($_SESSION['mailId']);
    $salt = intval($_SESSION['salt']);
    $hashOk = (boolean)($_SESSION['hashOk']);
    $nonceFromSession = $_SESSION['nonce_mst_frontend_unsub_confirm'];
    $hash = $_SESSION['hash'];
    $email = $_REQUEST['email'];
    $log->debug('listId: ' . $listId . ', ' . 'mailId: ' . $mailId . ', ' . 'salt: ' . $salt . ', ' . 'hashOk: ' . $hashOk . ', ' . 'nonceFromSession: ' . $nonceFromSession . ', ' . 'hash: ' . $hash . ', ' . 'email: ' . $email);

    $hashOk = ($hashOk && $hashUtils->checkUnsubscribeKeyOfMail($mailId, $salt, $hash) && wp_verify_nonce($nonceFromSession, 'mst_frontend_unsub_confirm'));
    $log->debug('hashOk after all checks: ' . ($hashOk ? 'yes' : 'no'));
    $mList = $listUtils->getMailingList($listId);
    $isRecipient = $recips->isRecipient($listId, $email);
    $unsubscribeFailed = false;
    $unsubscribeCompletedOk = false;
    $doubleOptInConfirmSent = false;
    $notSubscribed = false;
    $message = null;

    if ($hashOk) {
      if ($isRecipient) {
        if ($mList->unsubscribe_mode != MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN) {
          $log->debug('Double Opt-in unsubscribe mode not activated (frontend)');
          $success = $subscribeUtils->unsubscribeUser($email, $listId);
          $tmpUser = $subscribeUtils->getUserByEmail($email);
          $subscribeUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
          if ($success) {
            $unsubscribeCompletedOk = true;
            $message = __('Successfully unsubscribed', 'wpmst-mailster');
          } else {
            $unsubscribeFailed = true;
            $message = __('Unsubscription failed', 'wpmst-mailster');
          }
        } else {
          $log->debug('Double Opt-in unsubscribe mode (frontend)');
          $subscribeUtils->unsubscribeUserWithDoubleOptIn($email, $listId);
          $doubleOptInConfirmSent = true;
          $message = __('An email was sent to you in order to confirm that you wanted to unsubscribe. Please follow the instructions in the email.', 'wpmst-mailster');
        }
      } else {
        $message = __('Email not subscribed', 'wpmst-mailster');
        $unsubscribeFailed = true;
      }
    } else {
      $message = __('Unsubscription failed', 'wpmst-mailster') . ' (Reason: invalid hash)';
      $unsubscribeFailed = true;
    }

    $query = array();
    if ($unsubscribeCompletedOk) {
      $query[] = 'success=1';
    }
    if ($unsubscribeFailed) {
      $query[] = 'success=0';
    }
    if ($doubleOptInConfirmSent) {
      $query[] = 'success=1';
      $query[] = 'dos=1';
    }
    $query[] = 'mes=' . urlencode($message);

    $queryStr = implode('&', $query);

    header("HTTP/1.1 301 Moved Permanently");
    header("Status: 301 Moved Permanently");
    header("Location: " . plugins_url() . "/wp-mailster/view/subscription/unsubscribe2.php?" . $queryStr);
    header("Connection: close");
    exit(0);

  }
}


//ajax unsubscribe
add_action('wp_ajax_wpmst_unsubscribe', 'wpmst_unsubscribe_callback');
add_action('wp_ajax_nopriv_wpmst_unsubscribe', 'wpmst_unsubscribe_callback');
function wpmst_unsubscribe_callback()
{
  include_once(plugin_dir_path(__FILE__) . "view/unsubscribe.php");
}

//ajax subscribe
add_action('wp_ajax_wpmst_subscribe', 'wpmst_subscribe_callback');
add_action('wp_ajax_nopriv_wpmst_subscribe', 'wpmst_subscribe_callback');
function wpmst_subscribe_callback()
{
  include_once(plugin_dir_path(__FILE__) . "view/subscribe.php");
}


function hpr_html_form_code($propertyId)
{
  echo "Property id is $propertyId<br>";
  echo '<form action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post">';
  echo '<p>';
  echo 'Your Name (required) <br/>';
  echo '<input type="text" name="cf-name" pattern="[a-zA-Z0-9 ]+" value="' . (isset($_POST["cf-name"]) ? esc_attr($_POST["cf-name"]) : '') . '" size="40" />';
  echo '</p>';
  echo '<p>';
  echo 'Your JACK Email (required) <br/>';
  echo '<input type="email" name="cf-email" value="' . (isset($_POST["cf-email"]) ? esc_attr($_POST["cf-email"]) : '') . '" size="40" />';
  echo '</p>';
  echo '<p>';
//  echo 'Subject (required) <br/>';
//  echo '<input type="text" name="cf-subject" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf-subject"] ) ? esc_attr( $_POST["cf-subject"] ) : '' ) . '" size="40" />';
//  echo '</p>';
//  echo '<p>';
  echo 'Your Message (required) <br/>';
  echo '<textarea rows="10" cols="35" name="cf-message">' . (isset($_POST["cf-message"]) ? esc_attr($_POST["cf-message"]) : '') . '</textarea>';
  echo '</p>';
  echo '<p><input type="submit" name="cf-submitted" value="Send"></p>';
  echo '</form>';
}
require_once(plugin_dir_path(__FILE__) . "/models/MailsterModelGroup.php");
require_once(plugin_dir_path(__FILE__) . "/models/MailsterModelUser.php");

function hpr_deliver_mail()
{

  // if the submit button is clicked, send the email
  if (isset($_POST['cf-submitted'])) {

    // sanitize form values
    $name = sanitize_text_field($_POST["cf-name"]);
    $email = sanitize_email($_POST["cf-email"]);
    $subject = sanitize_text_field($_POST["cf-subject"]);
    $message = esc_textarea($_POST["cf-message"]);

    $propertyName = "HPR 01 - ";
    $propertyOwnerEmail = trim(strtolower("jwarner_ags@yahoo.com"));

    $Group = new MailsterModelGroup();

    //if ($_GET['jack'] == "hello") {
    # Convention is that the 2nd user is a hotel owner who is also a core user, but doesn't have to be
    //$email = 'jwarner_ags@yahoocom2';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      echo "$email is not valid.";
    } else {
      # TODO - get emails from external input and make sure they are valid syntactically
      $emails = array($email, $propertyOwnerEmail);
      $group_id = $Group->getRelationshipGroup($emails);
      if (!empty($group_id)) {
        echo "Group id is $group_id - nothing to be done";
      } else {
        echo "we have work to do!";
        $group_options['name'] = implode('+', $emails);
        $Group->saveData($group_options);
        $sid = $Group->getId();
        echo "Yay, group is $sid";
        # Now let's check on users
        $UserOne = new MailsterModelUser();
        $firstUser = $UserOne->isDuplicateEntry($emails[0]);
        $firstUserId = -1;
        $firstUserCore = 0;
        $secondUserId = -1;
        $secondUserCore = 0;
        $secondUserNiceName = '';
        if (empty($firstUser)) {
          echo "need to create user $emails[0]";
          $user_options['name'] = $emails[0];
          $user_options['email'] = $emails[0];
          $user_options['notes'] = 'created automatically from booking form';
          $firstUser = $UserOne->saveData($user_options);
          $firstUserId = $UserOne->getId();
        } else {
          echo "first user exists:";
          echo "<pre>";
          print_r($firstUser);
          echo "id is $firstUser->id";
          $firstUserId = $firstUser->id;
          $firstUserCore = $firstUser->is_core_user;
          echo "</pre>";
        }
        $UserTwo = new MailsterModelUser();
        $secondUser = $UserTwo->isDuplicateEntry($emails[1]);
        if (empty($secondUser)) {
          echo "need to create user $emails[1]";
          $user_options['name'] = $emails[1];
          $user_options['email'] = $emails[1];
          $user_options['notes'] = 'created automatically from booking form';
          $secondUser = $UserTwo->saveData($user_options);
          $secondUserId = $UserTwo->getId();
          $secondUserNiceName = $secondUserId;
        } else {
          echo "second user exists:";
          echo "<pre>";
          print_r($secondUser);

          $secondUserId = $secondUser->id;
          $secondUserNiceName = $secondUserId;
          $secondUserCore = $secondUser->is_core_user;
          if ($secondUserCore) {
            $secondUserId = $secondUser->ID;
            $secondUserNiceName = $secondUser->user_login;
          }

          echo "id is $secondUserId";
          echo "</pre>";

        }
        $Group->emtpyUsers();
        // both users exist, add them to the group!
        $res = $Group->addUserById($firstUserId, $firstUserCore);
        $res = $Group->addUserById($secondUserId, $secondUserCore);

        echo "list creation";
        $options['name'] = "Hand-Picked Riviera ";
        $options['list_mail'] = 'wpmailster@gmail.com';
        $options['admin_mail'] = 'wpmailster@gmail.com';
        $options['active'] = 1;
        $options['front_archive_access'] = 0;
        $options['server_inb_id'] = 3;
        $options['mail_in_user'] = 'wpmailster@gmail.com';
        $options['mail_in_pw'] = '4XtxJ39ZqCx7DkC97iyq';
        $options['use_cms_mailer'] = 0;
        $options['server_out_id'] = 4;
        $options['mail_out_user'] = 'wpmailster@gmail.com';
        $options['mail_out_pw'] = '4XtxJ39ZqCx7DkC97iyq';
        $options['subject_prefix'] = "";
        $options['custom_header_plain'] = "{name} ({date}):";
        $options['custom_footer_plain'] = "";
        $options['clean_up_subject'] = 1;
        $options['mail_format_conv'] = 0;
        $options['disable_mail_footer'] = 0;
        $options['mail_format_altbody'] = 1;
        $options['custom_header_html'] = "
{name} ({date}):
";
        $options['custom_footer_html'] = "";
        $options['copy_to_sender'] = 0;
        $options['mail_size_limit'] = 0;
        $options['filter_mails'] = 0;
        $options['allow_bulk_precedence'] = 0;
        $options['sending_public'] = 0;
        $options['sending_recipients'] = 0;
        $options['sending_admin'] = 1;
        $options['sending_group'] = 1;
        $options['sending_group_id'] = $Group->getId();
        $options['addressing_mode'] = 0;
        $options['bcc_count'] = 10;
        $options['incl_orig_headers'] = 0;
        $options['mail_from_mode'] = 2;
        $options['name_from_mode'] = 0;
        $options['reply_to_sender'] = 0;
        $options['bounce_mail'] = '';
        $options['bounce_mode'] = 0;
        $options['max_send_attempts'] = 5;
        $options['save_send_reports'] = 30;
        $options['allow_subscribe'] = 0;
        $options['public_registration'] = 0;
        $options['subscribe_mode'] = 0;
        $options['welcome_msg'] = 0;
        $options['welcome_msg_admin'] = 0;
        $options['allow_unsubscribe'] = 1;
        $options['unsubscribe_mode'] = 0;
        $options['goodbye_msg'] = 0;
        $options['goodbye_msg_admin'] = 0;
        $options['allow_digests'] = 0;
        $options['archive_mode'] = 0;
        $options['notify_not_fwd_sender'] = 1;

        $List = new MailsterModelList();
        $saved = $List->saveData($options);
        if ($saved == null) { //unsuccessful save
          #$message = $this->wpmst_view_message("updated", __("Something went wrong, data not saved. Please try again", 'wpmst-mailster'));
          echo "Failed!";
        } else {
          #$message = $this->wpmst_view_message("updated", __("Mailing list saved successfully.", 'wpmst-mailster'));
          $lid = $saved;
          echo "Saved $saved";

          $res = $List->addUserById(intval($secondUserId), intval($secondUserCore));
          $res = $List->addUserById(intval($firstUserId), intval($firstUserCore));
//      if ( $res && !$newUser->isRecip && $mList && $mList->welcome_msg > 0 && $mList->welcome_msg_admin > 0 ) {
//        $subscrUtils = MstFactory::getSubscribeUtils();
//        $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg( $userRow->name, $userRow->email, intval( $lid ), MstConsts::SUB_TYPE_SUBSCRIBE );
//        $log->debug( 'sent welcome message to user_id: ' . $userRow->name . ', ' . $userRow->email );
//      }

        }


        echo "<pre>";
        print_r($options);
        echo "</pre>";

      }
    }
  }
}

function hpr_cf_shortcode($atts)
{
  $atts = shortcode_atts(
    array(
      'propertyid' => '-1',
    ), $atts, 'sitepoint_contact_form' );

  ob_start();
  hpr_deliver_mail();
  hpr_html_form_code($atts['propertyid']);

  return ob_get_clean();
}

add_shortcode('sitepoint_contact_form', 'hpr_cf_shortcode');
