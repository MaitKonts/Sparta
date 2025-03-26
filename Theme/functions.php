<?php
// Theme setup
function custom_theme_setup() {
    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails
    add_theme_support('post-thumbnails');

    // Register navigation menu
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'custom-theme'),
    ));

    // Enable HTML5 support
    add_theme_support('html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
    ));
}
add_action('after_setup_theme', 'custom_theme_setup');

function my_custom_theme_enqueue_scripts() {
    // Enqueue the main stylesheet
    wp_enqueue_style( 'style', get_stylesheet_uri() );

}

add_action( 'wp_enqueue_scripts', 'my_custom_theme_enqueue_scripts' );

function teema() {
    ob_start(); // Start output buffering
    ?>
        <div class="container">
        <div class="toggle">
            <input type="checkbox">
            <span class="button"></span>
            <span class="label">☼</span>
        </div>
        </div>
    <?php
return ob_get_clean(); // Return the buffered output
}
add_shortcode('teema' , 'teema');

// Function to allow the creation of HTML Blocks.
function create_html_block_cpt() {
  $labels = array(
      'name' => __('HTML Blocks'),
      'singular_name' => __('HTML Block'),
      'menu_name' => __('HTML Blocks'),
      'name_admin_bar' => __('HTML Block'),
      'add_new' => __('Add New'),
      'add_new_item' => __('Add New HTML Block'),
      'edit_item' => __('Edit HTML Block'),
      'new_item' => __('New HTML Block'),
      'view_item' => __('View HTML Block'),
      'all_items' => __('All HTML Blocks'),
      'search_items' => __('Search HTML Blocks'),
      'not_found' => __('No HTML Blocks found.'),
      'not_found_in_trash' => __('No HTML Blocks found in Trash.'),
  );

  $args = array(
      'labels' => $labels,
      'public' => true,
      'has_archive' => false,
      'menu_icon' => 'dashicons-editor-code', // Optional: Customize the menu icon
      'supports' => array('title', 'editor'), // Allows title and editor (where you can add HTML)
      'show_in_rest' => true, // Allows Gutenberg compatibility
  );

  register_post_type('html_block', $args);
}
add_action('init', 'create_html_block_cpt');

// Function to display HTML block using a shortcode.
// Function to display HTML block using a shortcode.
function html_block_shortcode($atts) {
  // Extract shortcode attributes
  $atts = shortcode_atts(
      array(
          'id' => '', // Default attribute: 'id'
      ),
      $atts
  );

  // Check if ID is provided
  if (empty($atts['id'])) {
      return 'HTML Block ID not provided.';
  }

  // Fetch the HTML block post content by ID
  $html_block = get_post($atts['id']);

  // Verify if the post exists and is of type 'html_block'
  if ($html_block && $html_block->post_type === 'html_block') {
      // Get the custom CSS for the HTML block
      $custom_css = get_post_meta($html_block->ID, '_html_block_custom_css', true);

      // Create a unique class for this HTML block
      $unique_class = 'html-block-' . $html_block->ID;

      // Output the CSS in a <style> tag, scoped to this HTML block
      $output = '<style>';
      $output .= '.' . $unique_class . ' {';
      $output .= $custom_css; // Output the custom CSS
      $output .= '}';
      $output .= '</style>';

      // Add a container around the HTML block and assign the unique class
      $output .= '<div class="' . esc_attr($unique_class) . '">';
      $output .= apply_filters('the_content', $html_block->post_content);
      $output .= '</div>';

      // Return the full output (CSS + HTML)
      return $output;
  } else {
      return 'HTML Block not found.';
  }
}
add_shortcode('html_block', 'html_block_shortcode');


//SHORTKOODID//
// Register a shortcode to display the primary menu
function primary_menu_shortcode($atts) {
  // Attributes for the shortcode, allowing custom menu IDs or classes
  $atts = shortcode_atts(
      array(
          'menu' => 'primary-menu', // Default theme location
          'container' => 'nav',     // HTML container for the menu
          'container_class' => 'main-navigation', // Container class
          'menu_class' => 'primary-menu',  // Menu class
          'menu_id' => 'primary-menu',     // Menu ID
      ),
      $atts,
      'primary_menu'
  );

  // Output the menu using wp_nav_menu
  return wp_nav_menu(
      array(
          'theme_location'  => $atts['menu'], // Theme location (register menu in functions.php)
          'container'       => $atts['container'], // Container tag (nav, div, etc.)
          'container_class' => $atts['container_class'], // CSS class for the container
          'menu_class'      => $atts['menu_class'],  // CSS class for the menu
          'menu_id'         => $atts['menu_id'],     // HTML ID for the menu
          'echo'            => false, // Output the menu as a return value instead of echoing it
      )
  );
}
add_shortcode('primary_menu', 'primary_menu_shortcode');

// Add custom meta box for CSS in HTML Block
function html_block_add_css_meta_box() {
  add_meta_box(
      'html_block_css',
      __('Custom CSS', 'textdomain'),
      'html_block_css_meta_box_callback',
      'html_block', // Post type
      'normal',
      'high'
  );
}

add_action('add_meta_boxes', 'html_block_add_css_meta_box');

// Display the CSS meta box
function html_block_css_meta_box_callback($post) {
  wp_nonce_field('html_block_save_css', 'html_block_css_nonce');

  $value = get_post_meta($post->ID, '_html_block_custom_css', true);

  echo '<label for="html_block_custom_css">' . __('Add custom CSS for this block:', 'textdomain') . '</label>';
  echo '<textarea id="html_block_custom_css" name="html_block_custom_css" rows="10" style="width:100%;">' . esc_attr($value) . '</textarea>';
}

// Save the CSS when the post is saved
function html_block_save_css_meta_box($post_id) {
  if (!isset($_POST['html_block_css_nonce']) || !wp_verify_nonce($_POST['html_block_css_nonce'], 'html_block_save_css')) {
      return;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
  }

  if (isset($_POST['html_block_custom_css'])) {
      update_post_meta($post_id, '_html_block_custom_css', sanitize_textarea_field($_POST['html_block_custom_css']));
  }
}

add_action('save_post', 'html_block_save_css_meta_box');

// Hook into the admin menu to add custom admin pages
add_action('admin_menu', 'regumweb_create_menu');

function regumweb_create_menu() {
    // Add a top-level menu page
    add_menu_page(
        'Regumweb Settings',          // Page title
        'Regumweb',                   // Menu title
        'manage_options',             // Capability required to access this menu
        'regumweb_main_menu',         // Menu slug
        'regumweb_settings_page',     // Function to display the page content
        'dashicons-welcome-view-site',    // Icon for the menu
        66                            // Position in the menu (just after Comments)
    );

}

// Function to display the content of the "Settings" page
// Function to display the content of the "Settings" page
function regumweb_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Regumweb Settings', 'textdomain'); ?></h1>
        <form method="post" action="options.php">
            <?php
                // Output settings sections and their fields
                settings_fields('regumweb_settings_group'); // Use the same group name registered earlier
                do_settings_sections('regumweb_main_menu'); // Display the settings for the main menu

                // Output the save settings button
                submit_button();
            ?>
        </form>
    </div>
    <?php
}


// Hook to register settings for Regumweb
add_action('admin_init', 'regumweb_register_settings');

function regumweb_register_settings() {
    // Register a new setting for the 'regumweb_settings' page
    register_setting(
        'regumweb_settings_group',    // Settings group
        'regumweb_option_name',       // Option name in the database
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        )
    );

}

// Section description callback function
function regumweb_settings_section_callback() {
    echo '<p>' . __('General settings for the Regumweb theme.', 'textdomain') . '</p>';
}

// Add SEO meta box
function regumweb_add_seo_meta_box() {
    add_meta_box(
        'regumweb_seo_meta_box',
        __('SEO Settings', 'textdomain'),
        'regumweb_seo_meta_box_callback',
        'post',
        'normal',
        'high'
    );
    add_meta_box(
        'regumweb_seo_meta_box',
        __('SEO Settings', 'textdomain'),
        'regumweb_seo_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'regumweb_add_seo_meta_box');

// Callback to display meta box fields
function regumweb_seo_meta_box_callback($post) {
    wp_nonce_field('regumweb_save_seo_meta', 'regumweb_seo_meta_nonce');

    $meta_title = get_post_meta($post->ID, '_regumweb_meta_title', true);
    $meta_description = get_post_meta($post->ID, '_regumweb_meta_description', true);

    echo '<label for="regumweb_meta_title">' . __('Meta Title:', 'textdomain') . '</label>';
    echo '<input type="text" id="regumweb_meta_title" name="regumweb_meta_title" value="' . esc_attr($meta_title) . '" style="width:100%;" /><br/><br/>';

    echo '<label for="regumweb_meta_description">' . __('Meta Description:', 'textdomain') . '</label>';
    echo '<textarea id="regumweb_meta_description" name="regumweb_meta_description" rows="4" style="width:100%;">' . esc_attr($meta_description) . '</textarea>';
}

// Save SEO meta data
function regumweb_save_seo_meta($post_id) {
    if (!isset($_POST['regumweb_seo_meta_nonce']) || !wp_verify_nonce($_POST['regumweb_seo_meta_nonce'], 'regumweb_save_seo_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['regumweb_meta_title'])) {
        update_post_meta($post_id, '_regumweb_meta_title', sanitize_text_field($_POST['regumweb_meta_title']));
    }

    if (isset($_POST['regumweb_meta_description'])) {
        update_post_meta($post_id, '_regumweb_meta_description', sanitize_textarea_field($_POST['regumweb_meta_description']));
    }
}
add_action('save_post', 'regumweb_save_seo_meta');

// Add Custom CSS and JS fields to the settings page
function regumweb_custom_code_settings_section() {
    add_settings_section(
        'regumweb_custom_code_section',
        __('Custom Code Settings', 'textdomain'),
        'regumweb_custom_code_section_callback',
        'regumweb_main_menu'
    );

    add_settings_field(
        'regumweb_custom_css',
        __('Custom CSS', 'textdomain'),
        'regumweb_custom_css_callback',
        'regumweb_main_menu',
        'regumweb_custom_code_section'
    );

    add_settings_field(
        'regumweb_custom_js',
        __('Custom JavaScript', 'textdomain'),
        'regumweb_custom_js_callback',
        'regumweb_main_menu',
        'regumweb_custom_code_section'
    );

    register_setting('regumweb_settings_group', 'regumweb_custom_css');
    register_setting('regumweb_settings_group', 'regumweb_custom_js');
}
add_action('admin_init', 'regumweb_custom_code_settings_section');

// Display section description
function regumweb_custom_code_section_callback() {
    echo '<p>' . __('Add your custom CSS and JavaScript here.', 'textdomain') . '</p>';
}

// Custom CSS field callback
function regumweb_custom_css_callback() {
    $custom_css = get_option('regumweb_custom_css', '');
    echo '<textarea name="regumweb_custom_css" rows="10" style="width:100%;">' . esc_textarea($custom_css) . '</textarea>';
}

// Custom JS field callback
function regumweb_custom_js_callback() {
    $custom_js = get_option('regumweb_custom_js', '');
    echo '<textarea name="regumweb_custom_js" rows="10" style="width:100%;">' . esc_textarea($custom_js) . '</textarea>';
}

// Enqueue custom CSS and JS in the theme
function regumweb_enqueue_custom_code() {
    $custom_css = get_option('regumweb_custom_css');
    $custom_js = get_option('regumweb_custom_js');

    if (!empty($custom_css)) {
        wp_add_inline_style('style', $custom_css);
    }

    if (!empty($custom_js)) {
        wp_add_inline_script('jquery', $custom_js);
    }
}
add_action('wp_enqueue_scripts', 'regumweb_enqueue_custom_code');

// Register option to select a custom 404 page
function regumweb_404_page_settings() {
    add_settings_field(
        'regumweb_404_page',
        __('Custom 404 Page', 'textdomain'),
        'regumweb_404_page_callback',
        'regumweb_main_menu',
        'regumweb_settings_section'
    );
    register_setting('regumweb_settings_group', 'regumweb_404_page');
}
add_action('admin_init', 'regumweb_404_page_settings');

// Callback function to display the page selection dropdown
function regumweb_404_page_callback() {
    $selected_page = get_option('regumweb_404_page');
    wp_dropdown_pages(array(
        'name' => 'regumweb_404_page',
        'selected' => $selected_page,
        'show_option_none' => __('Select a page', 'textdomain'),
    ));
}

// Redirect to custom 404 page if set
function regumweb_custom_404_redirect() {
    if (is_404()) {
        $custom_404_page = get_option('regumweb_404_page');
        if ($custom_404_page) {
            wp_redirect(get_permalink($custom_404_page));
            exit;
        }
    }
}
add_action('template_redirect', 'regumweb_custom_404_redirect');

// Enable lazy loading for images
function regumweb_lazy_load_images($content) {
    $content = preg_replace('/<img(.*?)src=/i', '<img$1loading="lazy" src=', $content);
    return $content;
}
add_filter('the_content', 'regumweb_lazy_load_images');

// Custom gallery shortcode
function regumweb_custom_gallery($output, $atts, $instance) {
    $atts = shortcode_atts(
        array(
            'columns' => 3,
            'size' => 'thumbnail',
            'ids' => ''
        ),
        $atts
    );
    $ids = explode(',', $atts['ids']);
    if (!$ids) {
        return '';
    }

    $output = '<div class="custom-gallery">';
    foreach ($ids as $id) {
        $output .= '<div class="gallery-item">';
        $output .= wp_get_attachment_image($id, $atts['size']);
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}
add_filter('post_gallery', 'regumweb_custom_gallery', 10, 3);

// Register performance settings
function regumweb_performance_settings() {
    add_settings_section(
        'regumweb_performance_section',
        __('Performance Settings', 'textdomain'),
        'regumweb_performance_section_callback',
        'regumweb_main_menu'
    );

    add_settings_field(
        'regumweb_lazy_load_images',
        __('Lazy Load Images', 'textdomain'),
        'regumweb_lazy_load_images_callback',
        'regumweb_main_menu',
        'regumweb_performance_section'
    );

    register_setting('regumweb_settings_group', 'regumweb_lazy_load_images');
}
add_action('admin_init', 'regumweb_performance_settings');

// Lazy load images setting callback
function regumweb_lazy_load_images_callback() {
    $lazy_load = get_option('regumweb_lazy_load_images', false);
    echo '<input type="checkbox" name="regumweb_lazy_load_images" value="1" ' . checked(1, $lazy_load, false) . ' />';
}

// Enable lazy loading for images
function regumweb_maybe_lazy_load_images($content) {
    if (get_option('regumweb_lazy_load_images', false)) {
        return preg_replace('/<img(.*?)src=/i', '<img$1loading="lazy" src=', $content);
    }
    return $content;
}
add_filter('the_content', 'regumweb_maybe_lazy_load_images');

function display_html_block($block_id) {
    // Get the HTML block post by ID
    $html_block = get_post($block_id);

    // Check if the HTML block exists and is of the correct post type
    if ($html_block && $html_block->post_type === 'html_block') {
        // Output the content, applying filters for shortcodes and formatting
        echo apply_filters('the_content', $html_block->post_content);
    } else {
        // Display a message if the HTML block is not found
        echo 'HTML Block not found.';
    }
}

function regumweb_contact_form_shortcode() {
    // Display any success or error messages from the form processing
    if (isset($_GET['success']) && $_GET['success'] == 'true') {
        echo '<p class="success-message">Thank you for contacting us! We will get back to you soon.</p>';
    } elseif (isset($_GET['success']) && $_GET['success'] == 'false') {
        echo '<p class="error-message">There was an error sending your message. Please try again later.</p>';
    }

    ob_start();
    ?>
    <div class="contact-form-box">
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="contact-form">
            <!-- Specify a unique action for the admin-post handler -->
            <input type="hidden" name="action" value="submit_contact_form">
            <!-- WordPress nonce for security -->
            <?php wp_nonce_field('contact_form_nonce_action', 'contact_form_nonce'); ?>

            <label for="name">Name*</label>
            <input type="text" name="name" id="name" required>

            <label for="email">Email*</label>
            <input type="email" name="email" id="email" required>

            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone">

            <label for="company">Company Name</label>
            <input type="text" name="company" id="company">

            <label for="message">Message*</label>
            <textarea name="message" id="message" required></textarea>

            <button type="submit">Send Message</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('contact_form', 'regumweb_contact_form_shortcode');


function handle_contact_form_submission() {
    // Verify nonce and referer
    if (!isset($_POST['contact_form_nonce']) || !wp_verify_nonce($_POST['contact_form_nonce'], 'contact_form_nonce_action')) {
        wp_redirect(add_query_arg('success', 'false', wp_get_referer()));
        exit;
    }

    // Sanitize and validate form data
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $company = sanitize_text_field($_POST['company']);
    $message = sanitize_textarea_field($_POST['message']);

    // Validate required fields
    if (empty($name) || empty($email) || !is_email($email) || empty($message)) {
        wp_redirect(add_query_arg('success', 'false', wp_get_referer()));
        exit;
    }

    // Prepare email
    $to = 'maitkonts@regumweb.com';
    $subject = 'New Contact Form Submission';
    $body = "Name: $name\nEmail: $email\nPhone: $phone\nCompany: $company\n\nMessage:\n$message";
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: Your Site Name <your-email@yourdomain.com>',
        'Reply-To: ' . $email
    ];
    

    // Send email and redirect based on success or failure
    if (wp_mail($to, $subject, $body, $headers)) {
        wp_redirect(add_query_arg('success', 'true', wp_get_referer()));
    } else {
        wp_redirect(add_query_arg('success', 'false', wp_get_referer()));
    }
    exit;
}

// Hook the form handler to admin-post actions
add_action('admin_post_nopriv_submit_contact_form', 'handle_contact_form_submission');
add_action('admin_post_submit_contact_form', 'handle_contact_form_submission');
function mytheme_register_sidebars() {
    register_sidebar( array(
        'name'          => __( 'Primary Sidebar', 'mytheme' ),
        'id'            => 'primary-sidebar',
        'description'   => __( 'The main sidebar area.', 'mytheme' ),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'mytheme_register_sidebars' );


?>