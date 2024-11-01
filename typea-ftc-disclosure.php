<?php

/*
Plugin Name:    FTC Disclosure
Plugin URI:     https://typeaparent.com/tribe/ftc-disclosure-plugin
Description:    This plugin assists the publisher in adding an statement to the beginning of blog posts. Proper wording of this statement can make your posts compliant with FTC Disclosure requirements.
Version:        2.0
Author:         Eric Nagel
Author URI:     https://typeaparent.com/author/typea2017/
License:        GPL2
*/
/*  Copyright 2018 Influencer Media Group, Inc. eric@typeaparent.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
// Create a helper function for easy SDK access.

if ( !function_exists( 'fd_fs' ) ) {
    function fd_fs()
    {
        global  $fd_fs ;
        
        if ( !isset( $fd_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $fd_fs = fs_dynamic_init( array(
                'id'             => '1974',
                'slug'           => 'typea-ftc-disclosure',
                'type'           => 'plugin',
                'public_key'     => 'pk_145aa7f36c30c87a3492d2fc3e880',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'   => array_pop( explode( "/", dirname( __FILE__ ) ) ) . '/' . basename( __FILE__ ),
                'parent' => array(
                'slug' => 'options-general.php',
            ),
            ),
                'is_live'        => true,
            ) );
        }
        
        return $fd_fs;
    }
    
    function my_fs_custom_connect_message(
        $message,
        $user_first_name,
        $product_title,
        $user_login,
        $site_link,
        $freemius_link
    )
    {
        return sprintf( __( '%1$s, never miss an important update – opt-in to our security and feature updates notifications, and non-sensitive diagnostic tracking.', 'my-text-domain' ), $user_first_name );
    }
    
    fd_fs()->add_filter(
        'connect_message',
        'my_fs_custom_connect_message',
        10,
        6
    );
    function my_fs_custom_connect_message_on_update(
        $message,
        $user_first_name,
        $product_title,
        $user_login,
        $site_link,
        $freemius_link
    )
    {
        return sprintf(
            __( 'Hi %1$s', 'my-text-domain' ) . ',<br>' . __( 'Please help us improve our plugin, %2$s! When you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'my-text-domain' ),
            $user_first_name,
            '<b>' . $product_title . '</b>',
            '<b>' . $user_login . '</b>',
            $site_link,
            $freemius_link
        );
    }
    
    fd_fs()->add_filter(
        'connect_message_on_update',
        'my_fs_custom_connect_message_on_update',
        10,
        6
    );
    // Init Freemius.
    fd_fs();
    // Signal that SDK was initiated.
    do_action( 'fd_fs_loaded' );
    add_action( 'admin_init', 'ftc_disclose_register_settings' );
    register_activation_hook( __FILE__, 'ftc_disclose_activate' );
    register_deactivation_hook( __FILE__, 'ftc_disclose_deactivate' );
    register_uninstall_hook( __FILE__, 'ftc_disclose_uninstall' );
    $ftc_available_in_pro = " Option customizable in <a href='" . fd_fs()->get_upgrade_url() . "'>Pro Version</a>";
    /*****************************************************************************
    *
    *   Different disclosure types
    *
    *****************************************************************************/
    global  $ftc_disclosure_types ;
    $ftc_disclosure_types = array();
    $ftc_disclosure_types[] = array(
        'name'    => 'Generic Disclosure',
        'field'   => 'ftc_disclosure_text',
        'default' => 'I have been, or can be if you click on a link and make a purchase, compensated via a cash payment, gift, or something else of value for writing this post. Regardless, I only recommend products or services I use personally and believe will be good for my readers.',
    );
    function ftc_disclose_register_settings()
    {
        //register settings
        register_setting( 'ftc-settings-group', 'ftc_disclose_options' );
    }
    
    // activating the default values
    function ftc_disclose_activate()
    {
        global  $ftc_disclosure_types ;
        foreach ( $ftc_disclosure_types as $disclosure_type ) {
            $current = get_option( $disclosure_type['field'] );
            if ( !$current ) {
                add_option( $disclosure_type['field'], $disclosure_type['default'] );
            }
        }
    }
    
    // deactivating
    function ftc_disclose_deactivate()
    {
        // Nothing to do on deactivation
    }
    
    // uninstalling
    function ftc_disclose_uninstall()
    {
        if ( is_plugin_active( 'typea-ftc-disclosure-premium/typea-ftc-disclosure.php' ) ) {
            return;
        }
        # delete all data stored
        global  $ftc_disclosure_types ;
        foreach ( $ftc_disclosure_types as $disclosure_type ) {
            delete_option( $disclosure_type['field'] );
        }
    }
    
    /*****************************************************************************
    *
    *   Set up the menu and link from plugins page
    *
    *****************************************************************************/
    add_action( 'admin_menu', 'ftc_disclose_menu' );
    function ftc_disclose_menu()
    {
        add_submenu_page(
            'options-general.php',
            'FTC Disclosure',
            translate( 'FTC Disclosure Text' ),
            'administrator',
            __FILE__,
            'ftc_disclose_options'
        );
    }
    
    // Add settings link on plugin page
    function ftc_disclose_settings_link( $links )
    {
        $settings_link = '<a href="options-general.php?page=' . array_pop( explode( "/", dirname( __FILE__ ) ) ) . "%2F" . basename( __FILE__ ) . '">Settings</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
    
    $plugin = plugin_basename( __FILE__ );
    add_filter( "plugin_action_links_{$plugin}", 'ftc_disclose_settings_link' );
    /*****************************************************************************
    *
    *   The settings page
    *
    *****************************************************************************/
    function ftc_disclose_tabs( $current )
    {
        $tabs = array();
        $tabs['statements'] = 'Disclosure Statement' . (( count( $ftc_disclosure_types ) > 1 ? 's' : '' ));
        $tabs['display'] = 'Display Options';
        echo  '<div id="icon-themes" class="icon32"><br></div>' ;
        echo  '<h2 class="nav-tab-wrapper">' ;
        foreach ( $tabs as $tab => $name ) {
            $class = ( $tab == $current ? ' nav-tab-active' : '' );
            echo  "<a class='nav-tab{$class}' href='?page=" . array_pop( explode( "/", dirname( __FILE__ ) ) ) . "%2F" . basename( __FILE__ ) . "&tab={$tab}'>{$name}</a>" ;
        }
        echo  '</h2>' ;
    }
    
    function ftc_disclose_options()
    {
        global  $wpdb, $ftc_disclosure_types ;
        ?>
    <div class="wrap">
    <h2><?php 
        _e( 'FTC Disclosure' . (( count( $ftc_disclosure_types ) > 1 ? 's' : '' )) );
        ?></h2>
    <?php 
        
        if ( isset( $_POST ) && isset( $_POST['submit'] ) && $_POST['submit'] == 'Save Changes' ) {
            unset( $_POST['submit'] );
            while ( list( $var, $val ) = each( $_POST ) ) {
                update_option( $var, trim( stripslashes( $_POST[$var] ) ) );
            }
            ?>
        <div id="message" class="updated fade"><p><strong><?php 
            _e( 'The options have been updated.' );
            ?></strong></p></div>
        <?php 
        }
        
        $current = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'statements' );
        ftc_disclose_tabs( $current );
        ?>

    <form method="post" action="">
        <table class="form-table" width="100%">
        <tr valign="top">
            <td style="vertical-align: top">
                <table class="form-table" width="100%">
                    <?php 
        $function = 'ftc_disclose_options_' . $current;
        $function();
        
        if ( $current !== 'help' ) {
            ?>
                        <tr>
                            <td></td>
                            <td><?php 
            submit_button( translate( 'Save Changes' ) );
            ?></td>
                        </tr>
                        <?php 
        }
        
        ?>
                </table>
            </td>
            <td style="vertical-align: top; width: 300px">
                <?php 
        
        if ( fd_fs()->is_not_paying() ) {
            echo  '<h2>' . __( 'Upgrade to PRO!', 'typea-ftc-disclosure' ) . '</h2>' ;
            echo  '<ul style="list-style: disc; padding-left: 20px;">' ;
            echo  '<li>6 Different Disclosure Statements</li>' ;
            echo  '<li>Choose Default Statment to Show</li>' ;
            // echo '<li>Specify Disclosure per Post</li>';
            echo  '<li>Display on Pages</li>' ;
            echo  '<li>Specify Disclosure per Page</li>' ;
            // echo '<li>Insert Disclosure via Shortcode</li>';
            echo  '<li>Auto nofollow all links with specific Disclosure</li>' ;
            echo  '<li>Hide Disclosure from WooCommerce product pages</li>' ;
            echo  '</ul>' ;
            echo  '<p align="center"><a  class="button" style="color: #FFF; background-color: #1E8CBE; border-color: #176c92; box-shadow: inset 0 1px 0 #5dbbe5;" href="' . fd_fs()->get_upgrade_url() . '">' . __( 'Upgrade Now!', 'typea-ftc-disclosure' ) . '</a></p>' ;
        }
        
        ?>
                    <h2>Type-A Conference</h2>
                    <p><a href="https://typeaparent.com/events/annual-conference/2019-annual-conference-in-dc/?utm_source=disclosure&utm_medium=plugin" target="_blank"><img class="aligncenter size-medium wp-image-48509" src="https://typeaparent.com/wp-content/uploads/2018/09/type-a-2019-conference-300x150.png" alt="" width="300" height="150" /></a></p>
            </td>
        </tr></table>
    </form>
    </div>
    <?php 
    }
    
    function ftc_disclose_options_statements()
    {
        global  $ftc_disclosure_types ;
        reset( $ftc_disclosure_types );
        $n = 1;
        foreach ( $ftc_disclosure_types as $disclosure_type ) {
            ?>
        <tr>
            <td style="vertical-align: top;"><p><strong><?php 
            _e( $disclosure_type['name'] );
            ?></strong></p>
                <p>Shortcode:<br />
                <div style="background-color: white; overflow-x: auto;"><tt>[ftc_disclosure type="<?php 
            echo  substr( $disclosure_type['field'], 4, strlen( $disclosure_type['field'] ) - 9 ) ;
            ?>"]</tt></div></p>

                <?php 
            ?></td>
            <td><?php 
            $settings = array(
                'teeny'         => true,
                'textarea_rows' => 6,
                'tabindex'      => $n++,
            );
            wp_editor( get_option( $disclosure_type['field'] ), $disclosure_type['field'], $settings );
            ?><br />
                Default <?php 
            _e( $disclosure_type['name'] );
            ?> disclosure:<br />
                <div style="background-color: white; overflow-x: auto;"><tt><?php 
            echo  htmlentities( $disclosure_type['default'] ) ;
            ?></tt></div></td>
        </tr>
        <?php 
        }
    }
    
    function ftc_disclose_options_display()
    {
        global  $ftc_disclosure_types, $ftc_available_in_pro ;
        ?>
    <tr valign="top">
        <th scope="row"><?php 
        _e( 'Default Statement to Show:' );
        ?></th>
        <td><?php 
        echo  '<select name="ftc_default_display">' ;
        reset( $ftc_disclosure_types );
        $ftc_default_display = get_option( 'ftc_default_display' );
        foreach ( $ftc_disclosure_types as $disclosure_type ) {
            echo  '<option value="' . $disclosure_type['field'] . '"' . (( $ftc_default_display == $disclosure_type['field'] ? ' selected="selected"' : '' )) . '>' ;
            _e( $disclosure_type['name'] );
            echo  "</option>\n" ;
        }
        echo  '</select>' ;
        ?></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php 
        _e( 'Display on Homepage:' );
        ?><input type="hidden" name="ftc_disclosure_display_on_homepage" value=0 /></th>
        <td><?php 
        // This is only available when 'show_on_front' is set to 'posts'
        
        if ( get_option( 'show_on_front' ) === 'posts' ) {
            ?>
            <input type="checkbox" name="ftc_disclosure_display_on_homepage" value=1 <?php 
            echo  ( get_option( 'ftc_disclosure_display_on_homepage' ) == 1 ? ' checked="checked"' : '' ) ;
            ?> />
            <?php 
        } else {
            echo  "This option is only available when <em><a href='/wp-admin/options-reading.php'>Your homepage displays</a></em> is set to <em>Your latest posts</em>." ;
        }
        
        ?></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php 
        _e( 'Display on<br /> WooCommerce Products:' );
        ?><input type="hidden" name="ftc_disclosure_display_on_woocommerce" value=0 /></th>
        <td><?php 
        echo  '<input type="checkbox" name="ftc_disclosure_display_on_woocommerce" value=1 checked="checked" disabled />' ;
        echo  $ftc_available_in_pro ;
        ?></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php 
        _e( 'Display on Posts:' );
        ?><input type="hidden" name="ftc_disclosure_display_on_posts" value=0 /></th>
        <td><?php 
        echo  '<input type="checkbox" name="ftc_disclosure_display_on_posts" value=1 checked="checked" disabled />' ;
        echo  $ftc_available_in_pro ;
        ?></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php 
        _e( 'Display on Pages:' );
        ?><input type="hidden" name="ftc_disclosure_display_on_pages" value=0 /></th>
        <td><?php 
        echo  '<input type="checkbox" name="ftc_disclosure_display_on_pages" value=1 disabled />' ;
        echo  $ftc_available_in_pro ;
        ?></td>
    </tr>
    <?php 
    }
    
    /*****************************************************************************
    *
    *   Add options to the post editor
    *
    *****************************************************************************/
    add_action( 'load-post.php', 'ftc_disclosure_post_meta_boxes_setup' );
    add_action( 'load-post-new.php', 'ftc_disclosure_post_meta_boxes_setup' );
    function ftc_disclosure_post_meta_boxes_setup()
    {
        add_action( 'add_meta_boxes', 'ftc_disclosure_add_post_meta_boxes' );
        add_action(
            'save_post',
            'ftc_disclosure_save_post_class_meta',
            10,
            2
        );
    }
    
    function ftc_disclosure_add_post_meta_boxes()
    {
        $screen = array( 'post' );
        add_meta_box(
            'ftc-disclosure-post-class',
            // Unique ID
            translate( 'FTC Disclosure' ),
            // Title
            'ftc_disclosure_post_class_meta_box',
            // Callback function
            $screen,
            // Admin page (or post type)
            'side',
            // Context
            'default'
        );
    }
    
    function ftc_disclosure_post_class_meta_box( $post )
    {
        global  $ftc_disclosure_types ;
        wp_nonce_field( basename( __FILE__ ), 'ftc_disclosure_post_class_nonce' );
        $ftc_disclosure_display = get_post_meta( $post->ID, 'ftc_disclosure_display', true );
        ?>
    <p><label for="ftc-disclosure-post-class"><?php 
        _e( "Choose the FTC Disclosure to be displayed." );
        ?></label><br />
        <select name="ftc_disclosure_display">
            <option value=""><?php 
        _e( 'Default' );
        ?></option>
            <option value="none"<?php 
        echo  ( $ftc_disclosure_display == 'none' ? ' selected="selected"' : '' ) ;
        ?>><?php 
        _e( 'None' );
        ?></option>
            <?php 
        ?>
        </select>
    </p>
    <?php 
    }
    
    function ftc_disclosure_save_post_class_meta( $post_id, $post )
    {
        /* Verify the nonce before proceeding. */
        if ( !isset( $_POST['ftc_disclosure_post_class_nonce'] ) || !wp_verify_nonce( $_POST['ftc_disclosure_post_class_nonce'], basename( __FILE__ ) ) ) {
            return $post_id;
        }
        /* Get the post type object. */
        $post_type = get_post_type_object( $post->post_type );
        /* Check if the current user has permission to edit the post. */
        if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
            return $post_id;
        }
        /* Get the posted data and sanitize it for use as an HTML class. */
        $new_meta_value = ( isset( $_POST['ftc_disclosure_display'] ) ? sanitize_html_class( $_POST['ftc_disclosure_display'] ) : '' );
        /* Get the meta key. */
        $meta_key = 'ftc_disclosure_display';
        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );
        /* If a new meta value was added and there was no previous value, add it. */
        
        if ( $new_meta_value && '' == $meta_value ) {
            add_post_meta(
                $post_id,
                $meta_key,
                $new_meta_value,
                true
            );
        } elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
            update_post_meta( $post_id, $meta_key, $new_meta_value );
        } elseif ( '' == $new_meta_value && $meta_value ) {
            delete_post_meta( $post_id, $meta_key, $meta_value );
        }
    
    }
    
    /*****************************************************************************
    *
    *   Display the disclosure
    *
    *****************************************************************************/
    function function_ftc_disclosure( $content )
    {
        // This block will only be executed in the free version
        
        if ( is_single() ) {
            $override = get_post_meta( get_the_ID(), 'ftc_disclosure_display', true );
            if ( $override && $override == 'none' ) {
                return $content;
            }
            return wpautop( do_shortcode( get_option( 'ftc_disclosure_text' ) ) ) . $content;
        }
        
        return $content;
    }
    
    add_action( 'the_content', 'function_ftc_disclosure', 99 );
    /*****************************************************************************
    *
    *   Display the disclosure on the homepage
    *
    *****************************************************************************/
    function ftc_disclosure_on_homepage( $query )
    {
        
        if ( is_front_page() && $query->is_main_query() && get_option( 'ftc_disclosure_display_on_homepage' ) ) {
            echo  '<div id="ftc_disclosure_on_homepage">' ;
            echo  wpautop( do_shortcode( get_option( 'ftc_disclosure_text' ) ) ) ;
            echo  '</div>' ;
            return;
        }
    
    }
    
    add_action( 'loop_start', 'ftc_disclosure_on_homepage' );
    /*****************************************************************************
    *   Shortcode display
    *       [ftc_disclosure type="affiliate"]
    *****************************************************************************/
    function ftc_disclosure_shortcode( $args )
    {
        if ( empty($args['type']) ) {
            $args['type'] = get_option( 'ftc_default_display' );
        }
        if ( !$args['type'] || $args['type'] == 'none' ) {
            return '';
        }
        $disclosure = get_option( 'ftc_' . $args['type'] . '_text' );
        if ( !$disclosure ) {
            return '';
        }
        return wpautop( do_shortcode( $disclosure ) );
    }
    
    add_shortcode( 'ftc_disclosure', 'ftc_disclosure_shortcode' );
}

// wrapper for if ( ! function_exists( 'fd_fs' ) )