<?php
/**
 * The plugin creation file.
 * 
 * Plugin Name: Post Word Count and Read Time
 * Description: Add an estimated reading time and count the words to your posts.
 * Version: 1.0.0
 * Author: Claudio Alcantara
 * Author URI: https://www.linkedin.com/in/claudioalcantararivas/
 * Text Domain: pwcpdomain
 * License: GPL2
 * Domain Path: /languages
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * Copyright 2022  Claudio Alcantara  (email : claudio.dev29@gmail.com)
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// // If this file is called directly, exit.
if (!defined('ABSPATH')){exit;}

// The class that contains all functions for calculating the words, characters and reading time.
class PostWordCountAndTime
{
    function __construct() { 
        add_action( 'admin_menu', array($this, 'adminPage') );
        add_action( 'admin_init', array($this, 'settings') );
        add_action( 'the_content', array($this, 'ifWrap') );
        add_action( 'init', array($this, 'languages') );
    }

    // Set translations
    function languages()
    {
        load_plugin_textdomain( 'pwcpdomain', false, dirname(plugin_basename(__FILE__)) . '/languages' );
    }

    // Removing html output if all checkbox's are empty
    function ifWrap($content) 
    {
        if(is_main_query() AND is_single() AND
        (
            get_option('pwcp_wordcountcheck', '1') OR
            get_option('pwcp_charactercountcheck', '1') OR
            get_option('pwcp_readtimecheck', '1')
        ))
        {
            return $this->createHTML($content);
        }
        return $content;
    }

    function createHTML($content)
    {
        $html = '<h3>' . esc_html(get_option('pwcp_headline', __('Post Statistics', 'pwcpdomain'))) .'</h3><p>';

        // Get word count once because both word count and read time will need it.
        if(get_option('pwcp_wordcountcheck', '1') OR get_option('pwcp_readtimecheck', '1')) {
            $wordCount = str_word_count(strip_tags($content));
        }

        // Counting words
        if (get_option('pwcp_wordcountcheck', '1')) {
            $html .= __('This post has', 'pwcpdomain') . ' ' . $wordCount . ' ' . __('words.', 'pwcpdomain') . '<br>'; 
        }

        // Counting characters
        if (get_option('pwcp_charactercountcheck', '1')) {
            $html .= __('This post has', 'pwcpdomain') . ' ' . strlen(strip_tags($content)) . ' ' . __('characters', 'pwcpdomain') . '.<br>'; 
        }

        // Reading time
        if (get_option('pwcp_readtimecheck', '1')) {
            $html .= round($wordCount/300) . ' ' . __('minute(s) to read', 'pwcpdomain') . '.<br>'; 
        }

        $html .= '</p>';

        // Position of the Post Statistics
        if (get_option('pwcp_location', '0') == '0') {
            return $html . $content;
        }
        return $content . $html;
    }

    function settings()
    {
        // Register the link access in the settings menu
        add_settings_section( 'pwcp_first_section', null, null, 'post-word-count-settings-page' );

        // Location 
        add_settings_field( 'pwcp_location', __('Display Location', 'pwcpdomain'), array($this, 'locationHTML'),'post-word-count-settings-page', 'pwcp_first_section' );
        register_setting( 'postwordcountplugin', 'pwcp_location', array('sanitize_callback' => array($this, 'sanitizeLocation'), 'default' => '0') );

        // Headline
        add_settings_field( 'pwcp_headline', __('Headline Text', 'pwcpdomain'), array($this, 'headlineHTML'),'post-word-count-settings-page', 'pwcp_first_section' );
        register_setting( 'postwordcountplugin', 'pwcp_headline', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'Post Statistics') );

        // Word Count Checkbox
        add_settings_field( 'pwcp_wordcountcheck', __('Word Count', 'pwcpdomain'), array($this, 'wordcountcheckHTML'),'post-word-count-settings-page', 'pwcp_first_section' );
        register_setting( 'postwordcountplugin', 'pwcp_wordcountcheck', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1') );

        // Character Count Checkbox
        add_settings_field( 'pwcp_charactercountcheck', __('Character Count', 'pwcpdomain'), array($this, 'charactercountcheckHTML'),'post-word-count-settings-page', 'pwcp_first_section' );
        register_setting( 'postwordcountplugin', 'pwcp_charactercountcheck', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1') );

        // Read Time Checkbox
        add_settings_field( 'pwcp_readtimecheck', __('Read Time', 'pwcpdomain'), array($this, 'readtimecheckHTML'),'post-word-count-settings-page', 'pwcp_first_section' );
        register_setting( 'postwordcountplugin', 'pwcp_readtimecheck', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1') );
    }

    function sanitizeLocation($input) 
    {
        if($input != '0' AND $input != '1') {
            add_settings_error( 'pwcp_location', 'pwcp_location_error', __('Display location must be either beggining or end.', 'pwcpdomain') );
            return get_option('pwcp_location');
        }
        return $input;
    }

    // Callback: Word count checkbox output
    function wordcountcheckHTML() {?>
        <input type="checkbox" name="pwcp_wordcountcheck" value="1" <?php checked(get_option('pwcp_wordcountcheck'), '1') ?>>
        <p class="description"><?php esc_html_e( 'Display word count of the post. Uncheck to hide.', 'pwcpdomain' ); ?></p>       
    <?php }

    // Callback: Character count checkbox output
    function charactercountcheckHTML() {?>
        <input type="checkbox" name="pwcp_charactercountcheck" value="1" <?php checked(get_option('pwcp_charactercountcheck'), '1') ?>>
        <p class="description"><?php esc_html_e( 'Display post character count. Uncheck to hide.', 'pwcpdomain' ); ?></p>
    <?php }

    // Callback: Read time checkbox output
    function readtimecheckHTML() {?>
        <input type="checkbox" name="pwcp_readtimecheck" value="1" <?php checked(get_option('pwcp_readtimecheck'), '1') ?>>
        <p class="description"><?php esc_html_e( 'Display reading time of the post. By default the value is 300. Uncheck to hide.', 'pwcpdomain' ); ?></p>
    <?php }

    // Callback: The Headline content output
    function headlineHTML() {?>
        <input type="text" name="pwcp_headline" value="<?= esc_attr( get_option( 'pwcp_headline' ) ) ?>">
        <p class="description"><?php esc_html_e( 'This value appears before the box. Leave it blank so that it is not displayed.', 'pwcpdomain' ); ?></p>
    <?php }

    // Callback: Display Location output
    function locationHTML() { ?>
        <select name="pwcp_location">
            <option value="0" <?php selected(get_option('pwcp_location'), '0') ?>><?php esc_html_e( 'Begginig of post', 'pwcpdomain' )?></option>
            <option value="1" <?php selected(get_option('pwcp_location'), '1') ?>><?php esc_html_e( 'End of post', 'pwcpdomain' )?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Selects the position where you want that the information appear in the post content.', 'pwcpdomain' ); ?></p>
    <?php }

    // Add the admin page
    function adminPage() 
    {
        add_options_page( 'Post Word Count and Read Time Settings', __('Word Count and Read Time', 'pwcpdomain' ), 'manage_options', 'post-word-count-settings-page', array($this, 'HTMLContent'));
    }

    // Callback: Display Location
    function HTMLContent() { ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Word Count and Read Time', 'pwcpdomain' );?></h1>
            <form action="options.php" method="POST">
            <?php
                settings_fields( 'postwordcountplugin' );
                do_settings_sections( 'post-word-count-settings-page' );
                submit_button();
            ?>
            </form>
        </div>
    <?php }
}

$postWordCountAndTime = new PostWordCountAndTime();