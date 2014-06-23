<?php
/*
 * Plugin Name:       My WP Comment Rating
 * Plugin URI:        http://damoiseau.me/
 * Description:       Adds rating functionality to your Wordpress website
 * Version:           0.1.0
 * Author:            Mike Damoiseau
 * Author URI:        http://damoiseau.me
 * Text Domain:       mywpcommentrating
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 *
 * Copyright 2014  Mike  (email : mike@damoiseau.me)
 *
 */

if ( ! defined( 'WPINC' ) ) {
    die('asdfasdasd');
}

if ( ! defined( 'RC_TC_BASE_FILE' ) ) {
    define( 'RC_TC_BASE_FILE', __FILE__ );
}
if ( ! defined( 'RC_TC_BASE_DIR' ) ) {
    define( 'RC_TC_BASE_DIR', dirname( RC_TC_BASE_FILE ) );
}
if ( ! defined( 'RC_TC_PLUGIN_URL' ) ) {
    define( 'RC_TC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'RC_TC_PLUGIN_PATH' ) ) {
    define( 'RC_TC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

class My_WP_Comment_Rating {
    private static $__maxRate = 5;

    public function __construct() {
        add_action( 'comment_form_logged_in_after', array( __CLASS__, 'comment_form_after_fields' ) );
        add_action( 'comment_form_after_fields', array( __CLASS__, 'comment_form_after_fields' ) );

        // save the data
        add_action( 'comment_post', array( __CLASS__, 'comment_post' ) );

        // Add the comment meta (saved earlier) to the comment text
        add_filter( 'comment_text', array( __CLASS__, 'comment_text' ) );

        // Add an edit option to comment editing screen
        add_action( 'add_meta_boxes_comment', array( __CLASS__, 'add_meta_boxes_comment' ) );

        // Update comment meta data from comment editing screen
        add_action( 'edit_comment', array( __CLASS__, 'edit_comment' ) );

        // the shortcodes
        self::add_shortcodes();
    } // __construct

    public static function comment_form_after_fields() {
      echo '<p class="comment-form-rating">'.
      '<label for="rating">'. __( 'Rating', 'mywpcommentrating' ) . '<span class="required">*</span></label>
      <span class="commentratingbox">';

        //Current rating scale is 1 to 5. If you want the scale to be 1 to 10, then set the value of $i to 10.
        for( $i=1; $i <= 5; $i++ ) {
          echo '<span class="commentrating"><input type="radio" name="mywpd_rating" id="mywpd_rating" value="'. $i .'"/>'. $i .'</span>';
        }

      echo'</span></p>';
    } // comment_form_after_fields

    public static function comment_post( $comment_id ) {
        if ( ( isset( $_POST['mywpd_rating'] ) ) && ( $_POST['mywpd_rating'] != '') ) {
          // save the rating of this comment
          $rating = wp_filter_nohtml_kses( $_POST['mywpd_rating'] );
          add_comment_meta( $comment_id, 'mywpd_rating', $rating );

          // recalculate the rating of the post
          $comment = get_comment( $comment_id, ARRAY_A );
          $post_id = $comment['comment_post_ID'];

          $rating_total = ( int )get_post_meta( $post_id, 'mywpd_rating_place', true );
          $rating_count = ( int )get_post_meta( $post_id, 'mywpd_rating_place_count', true );

          $rating_total += $rating;
          $rating_count++;
          if ( ! update_post_meta( $post_id, 'mywpd_rating_place', $rating_total ) ) {
            add_post_meta( $post_id, 'mywpd_rating_place', $rating_total, true );
          }
          if ( ! update_post_meta( $post_id, 'mywpd_rating_place_count', $rating_count ) ) {
            add_post_meta( $post_id, 'mywpd_rating_place_count', $rating_count, true );
          }
        }
    } // comment_post

    public static function comment_text( $text ) {
      $plugin_url_path = RC_TC_PLUGIN_URL;

      if( $commentrating = get_comment_meta( get_comment_ID(), 'mywpd_rating', true ) ) {
        $text .= '<p class="comment-rating">';
        for( $i = 0; $i < self::$__maxRate; $i++ ) {
         $text .= sprintf( '<img src="%simages/%sstar.png" />', RC_TC_PLUGIN_URL, ( $i < $commentrating ) ? '' : 'blank' );
        }
        $text .= '</p>';
      }

      return $text;
    } // comment_text

    public static function add_meta_boxes_comment() {
      add_meta_box(
        'mywpd_rating',
        __( 'Rating', 'mywpcommentrating' ),
        array( __CLASS__, 'extend_comment_meta_box' ),
        'comment',
        'normal',
        'high'
      );
    } // add_meta_boxes_comment

    public static function extend_comment_meta_box( $comment ) {
      $rating = get_comment_meta( $comment->comment_ID, 'mywpd_rating', true );
      wp_nonce_field( 'extend_comment_update', 'extend_comment_update', false );
      ?>
      <p>
        <label for="mywpd_rating"><?php _e( 'Rating: ' ); ?></label>
        <span class="commentratingbox">
        <?php for( $i=1; $i <= 5; $i++ ) {
          echo '<span class="commentrating"><input type="radio" name="mywpd_rating" id="mywpd_rating" value="'. $i .'"';
          if ( $rating == $i ) echo ' checked="checked"';
          echo ' />'. $i .' </span>';
          }
        ?>
        </span>
      </p>
      <?php
    } // extend_comment_meta_box

    public static function edit_comment( $comment_id ) {
      if( ! isset( $_POST['extend_comment_update'] ) || !wp_verify_nonce( $_POST['extend_comment_update'], 'extend_comment_update' ) ) {
        return;
      }

      if ( ( isset( $_POST['mywpd_rating'] ) ) && ( $_POST['mywpd_rating'] != '') ) {
        $comment = get_comment( $comment_id, ARRAY_A );
        $rating_previous = get_comment_meta( $comment_id, 'mywpd_rating', true );


        $rating = wp_filter_nohtml_kses($_POST['mywpd_rating']);
        update_comment_meta( $comment_id, 'mywpd_rating', $rating );

        // recalculate the rating of the post
        $post_id = $comment['comment_post_ID'];

        $rating_total = ( int )get_post_meta( $post_id, 'mywpd_rating_place', true );

        $rating_total -= $rating_previous;
        $rating_total += $rating;

        if ( ! update_post_meta( $post_id, 'mywpd_rating_place', $rating_total ) ) {
          add_post_meta( $post_id, 'mywpd_rating_place', $rating_total, true );
        }

      } else {
        delete_comment_meta( $comment_id, 'mywpd_rating');
      }
    } // edit_comment

    public static function add_shortcodes() {
        add_shortcode( 'my-wp-comment-rating', array( __CLASS__, 'shortcode_rating' ) );
    } // add_shortcodes

    private static function __display_stars( $post_id ) {
        $rating_total = ( int )get_post_meta( $post_id, 'mywpd_rating_place', true );
        $rating_count = ( int )get_post_meta( $post_id, 'mywpd_rating_place_count', true );

        $text = '';
        if( $rating_total > 0 ) {
            $commentrating = ( $rating_total / $rating_count );

            for( $i = 0; $i < self::$__maxRate; $i++ ) {
                $text .= sprintf( '<img src="%simages/%sstar.png" />', RC_TC_PLUGIN_URL, ( $i < $commentrating ) ? '' : 'blank' );
            }
        }

        return $text;
    }

    public static function shortcode_rating( $atts ) {
      global $post;

      extract( shortcode_atts( array(
            'label' => __( 'Post rating: ', 'mywpcommentrating' ),
        ), $atts ) );

        $text = self::__display_stars( $post->ID );
        if( !empty( $text ) ) {
            $text = sprintf( '<span>%s</span>', $label ) . $text;
        }
        return '<div class="mywpdirectory">' . $text . '</div>';
    } // shortcode_rating


} // My_WP_Comment_Rating

// Instanciate the plugin
new My_WP_Comment_Rating();

 ?>