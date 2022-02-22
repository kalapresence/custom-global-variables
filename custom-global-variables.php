<?php
/**
 * Plugin Name: Custom Global Variables
 * Plugin URI: https://www.newtarget.com/solutions/wordpress-websites
 * Description: Easily create custom variables that can be accessed globally in Wordpress and PHP. Retrieval of information is extremely fast, with no database calls.
 * Version: 1.1.2
 * Author: new target, inc
 * Author URI: https://www.newtarget.com
 * License: GPL2
 */

class Custom_Global_Variables {

    private $file_path = '';

    // Constructor
    function __construct() {

        $this->file_path = WP_CONTENT_DIR . '/custom-global-variables/' . md5( AUTH_KEY ) . '.json';

        /* Retrieve locally the current definitions. */

        if ( file_exists( $this->file_path ) ) {

            $vars = file_get_contents( $this->file_path );

            if ( ! empty( $vars ) ) {

                $GLOBALS['cgv'] = json_decode( $vars, true );
            }
            else {

                $GLOBALS['cgv'] = array();
            }
        }
        // Create the directory and file when it doesn't exist.
        else {

            if ( wp_mkdir_p( WP_CONTENT_DIR . '/custom-global-variables' ) ) {

                file_put_contents( $this->file_path, '' );
            }

            $GLOBALS['cgv'] = array();
        }

        // Add the menu item.
        add_action( 'admin_menu', array( &$this, 'add_menu' ) );

        // Setup the shortcode.
        add_shortcode( 'cgv', array( &$this, 'shortcode' ) );
    }

    // Adds the menu item under Settings
    function add_menu() {

        add_submenu_page(
            'options-general.php',
            'Custom Global Variables',
            'Custom Global Variables',
            'manage_options',
            'custom-global-variables',
            array( &$this, 'admin_page' )
        );
    }

    // Admin page
    function admin_page() {

        // Terminate if the user isn't allowed to access the page.
        if ( ! current_user_can( 'manage_options' ) ) {

            wp_die( 'You do not have sufficient permissions.' );
        }

        wp_enqueue_style( 'custom-global-variables-style', plugins_url( 'style.css', __FILE__ ) );
        wp_enqueue_script( 'custom-global-variables-script', plugins_url( 'script.js', __FILE__ ), array( 'jquery' ) );

        $vars = $GLOBALS['cgv'];

        // Save definitions upon submission.
        if ( isset( $_POST['vars'] ) ) {
            if( isset( $_POST['cgv_nonce'] ) ){
                if ( !wp_verify_nonce( $_REQUEST['cgv_nonce'], 'cgv_nonce' ) ){
                    wp_die( 'You do not have sufficient permissions 2' );
                    return FALSE;                    
                }
            }else {
                wp_die( 'You do not have sufficient permissions 3' );
                return FALSE;
            }

            $vars_new = array();

            foreach ( $_POST['vars'] as $var ) {

                $var['name'] = sanitize_textarea_field($var['name']);
                $var['val'] = wp_kses_post($var['val'] . '～' . $var['class']);

                if ( ! empty( $var['name'] ) && !empty( $var['val'] ) ) {

                    $name = trim( strtolower( str_replace( ' ', '_', $var['name'] ) ) );
                    $vars_new[ $name ] = stripslashes( $var['val'] );
                }
            }

            if ( file_put_contents( $this->file_path, json_encode( $vars_new ) ) !== false ) {

                $vars = $vars_new;

                echo '<div id="message" class="updated"><p>Your variables have successfully been saved.</p></div>';
            }
            else {

                echo '<div id="message" class="error"><p>Your variables could not be saved. Check to see if the following folder exists and has sufficient write permissions:</p><p><strong>' . WP_CONTENT_DIR . '/custom-global-variables' . '</strong></p></div>';
            }
        }
        ?>

        <div class="wrap">

            <h2>Custom Global Variables</h2>

            <div class="card" style="max-width: 100%">

                <h3>Usage</h3>

                <p>Display your variables using the shortcode syntax:</p>

                <p><code>[cgv <em>variable-name</em>]</code></p>

                <p>Or using the superglobal in PHP:</p>

                <p><code>&lt;?php echo $GLOBALS['cgv']['<em>variable-name</em>'] ?&gt;</code></p>

            </div>

            <div class="card" style="max-width: 100%">

                <h3>Define your variables</h3>

                <form method="POST" action="">
                    <?php
                        wp_nonce_field( 'cgv_nonce', 'cgv_nonce', false, true );                        
                    ?>
                    <table id="custom-global-variables-table-definitions">

                        <tbody>

                            <?php
                            $i = 0;

                            if ( !empty( $vars ) ):
                            ?>

                                <?php foreach ( $vars as $key => $val ):  ?>
                                    <?php
                                    $key = esc_html($key);
                                    $val = wp_kses_post($val);
                                    ?>

                                    <tr>

                                        <td style="width: 10% !important;"><input autocomplete="off" name="vars[<?php echo $i ?>][name]" placeholder="name" type="text" value="<?php echo $key ?>"></td>

                                        <td><span class="equals">=</span></td>

                                        <td class="value-input">

                                            <?php if ( $val == strip_tags( $val ) ): ?>

                                                <input autocomplete="off" name="vars[<?php echo $i ?>][val]" placeholder="value" type="text" value="<?php echo htmlentities( strtok($val, '～') ) ?>">

                                            <?php else: ?>

                                                <textarea name="vars[<?php echo $i ?>][val]" placeholder="value"><?php echo htmlentities( strtok($val, '～') ) ?></textarea>

                                            <?php endif ?>

                                        </td>
                                        
                                        <td>
                                            <p>Class:</p>
                                        </td>
                                        
                                        <td>
                                            <input type="text" name="vars[<?php echo $i ?>][class]" value="<?php echo htmlentities( substr($val, strpos($val, '～') + 3) ) ?>">
                                        </td>

                                        <td class="options" style="vertical-align: middle !important;">

                                            <img style="width: 40px !important;" alt="delete" class="delete" src="<?php echo plugin_dir_url( __FILE__ ) ?>/delete.png">

                                        </td>

                                    </tr>

                                <?php $i++; endforeach; ?>

                            <?php endif ?>

                            <tr>

                                <td style="width: 10% !important;"><input autocomplete="off" name="vars[<?php echo $i ?>][name]" placeholder="name" type="text"></td>

                                <td><span class="equals">=</span></td>

                                <td><input autocomplete="off" name="vars[<?php echo $i ?>][val]" placeholder="value" type="text"></td>

                                <td>
                                    <p>Class:</p>
                                </td>

                                <td>
                                    <input type="text" name="vars[<?php echo $i ?>][class]">
                                </td>

                                <td></td>

                            </tr>

                        </tbody>

                    </table>

                    <p><input type="submit" value="Save" class="button-primary"></p>

                </form>

            </div>

        </div>

        <?php
    }

    // Shortcode for displaying values
    function shortcode( $params ) {
        $param0 = sanitize_text_field($params[0]);
        if ( ! empty( $GLOBALS['cgv'][ $param0 ])  ) {
	        return wp_kses_post('<div class="' . htmlentities( substr($GLOBALS['cgv'][ $param0 ], strpos($GLOBALS['cgv'][ $param0 ], '～') + 3) ) . '">' . strtok($GLOBALS['cgv'][ $param0 ], '～') . '</div>');
        }

        return false;
    }
}

$custom_global_variables = new Custom_Global_Variables;
