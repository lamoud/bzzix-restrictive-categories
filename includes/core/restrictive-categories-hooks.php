<?php
/**
 * Main plugin class file.
 *
 * @package bzzix_restrictive_categories/Includes/core
 */

 // Function to check if user is restricted
 function bzrc_is_user_restricted($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    $restriction_type = get_option('bzrc_bzzix_restrictive_categories_type', 'roles');
    $restricted_roles = get_option('bzrc_bzzix_restrictive_categories_roles', array());
    $restricted_users = get_option('bzrc_bzzix_restrictive_categories_users', '');

    $restricted_users = array_filter(array_map('trim', explode(',', $restricted_users)));

    if ($restriction_type === 'users' && in_array($user_id, $restricted_users)) {
        return true;
    }

    if ($restriction_type === 'roles' && !empty(array_intersect($restricted_roles, $user->roles))) {
        return true;
    }

    return false;
}

//Add fields to select categories in the user profile
add_action('show_user_profile', 'bzrc_add_user_category_field');
add_action('edit_user_profile', 'bzrc_add_user_category_field');
function bzrc_add_user_category_field($user) {

    if (!bzrc_is_user_restricted($user->ID)) {
        return;
    }
    $allowed_categories = get_user_meta($user->ID, 'bzrc_allowed_categories', true);
    $allowed_categories = is_array($allowed_categories) ? $allowed_categories : [];

    $categories = get_categories(array('hide_empty' => false));
    ?>
    <h3><?php _e('Category Restrictions', 'bzzix-restrictive-categories'); ?></h3>
    <table class="form-table" style="border: 2px solid #007cba; padding: 15px; border-radius: 8px; background: #f9f9f9;">
        <tr>
            <th><?php _e('Allowed Categories', 'bzzix-restrictive-categories'); ?></th>
            <td>
                <?php foreach ($categories as $category) : ?>
                    <label style="display:block; margin-bottom: 5px;">
                        <input type="checkbox" name="bzrc_allowed_categories[]" value="<?php echo esc_attr($category->term_id); ?>" 
                            <?php echo in_array($category->term_id, $allowed_categories) ? 'checked' : ''; ?>>
                        <?php echo esc_html($category->name); ?>
                    </label>
                <?php endforeach; ?>
                <p class="description"><?php _e('Select the categories this user is allowed to post in.', 'bzzix-restrictive-categories'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

//Save categories allowed to publish
add_action('personal_options_update', 'bzrc_save_user_category_field');
add_action('edit_user_profile_update', 'bzrc_save_user_category_field');
function bzrc_save_user_category_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['bzrc_allowed_categories']) && is_array($_POST['bzrc_allowed_categories'])) {
        $allowed_categories = array_map('intval', $_POST['bzrc_allowed_categories']);
        update_user_meta($user_id, 'bzrc_allowed_categories', $allowed_categories);
    } else {
        delete_user_meta($user_id, 'bzrc_allowed_categories');
    }
}

// Function to get the categories allowed for the user
function bzrc_get_user_allowed_categories($user_id) {
    return get_user_meta($user_id, 'bzrc_allowed_categories', true);
}

// Prevent user from posting in unauthorized categories
add_action('save_post', 'bzrc_prevent_publish_in_restricted_categories', 10, 1);
function bzrc_prevent_publish_in_restricted_categories($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $user_id = get_current_user_id();

    if (bzrc_is_user_restricted($user_id)) {
        $allowed_categories = bzrc_get_user_allowed_categories($user_id);

        if (!empty($allowed_categories)) {
            $post_categories = wp_get_post_categories($post_id);

            $invalid_categories = array_diff($post_categories, $allowed_categories);
            if (!empty($invalid_categories)) {
                remove_action('save_post', 'bzrc_prevent_publish_in_restricted_categories');
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_status' => 'draft',
                ));
                add_action('save_post', 'bzrc_prevent_publish_in_restricted_categories');

                wp_die(__('You are not allowed to publish in these categories. Please choose only from the allowed categories.', 'bzzix-restrictive-categories'));
            }
        }
    }
}

// User category filter function
function bzrc_filter_categories_for_user($terms, $taxonomies, $args, $post_id) {
    $user_id = get_current_user_id();
    if (bzrc_is_user_restricted($user_id)) {
        $allowed_categories = bzrc_get_user_allowed_categories($user_id);

        if (!empty($allowed_categories)) {
            $terms = array_filter($terms, function($term) use ($allowed_categories) {
                if (is_object($term) && isset($term->term_id)) {
                    return in_array($term->term_id, $allowed_categories);
                }
                return false;
            });
        }
    }
    return $terms;
}
add_filter('get_terms', 'bzrc_filter_categories_for_user', 10, 4);
