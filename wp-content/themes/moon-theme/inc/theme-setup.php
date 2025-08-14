<?php
function mytheme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'main-menu' => __('Main Menu', 'my-custom-theme')
    ]);
}
add_action('after_setup_theme', 'mytheme_setup');
?>