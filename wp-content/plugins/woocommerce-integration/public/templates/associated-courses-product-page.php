<?php

if (! empty($product_id)) {
    $product_options = get_post_meta($product_id, 'product_options', true);

    if (! empty($product_options)) {
        if (isset($product_options['moodle_post_course_id']) && is_array($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
            ?>
            <div class="wi-asso-courses-wrapper">
                <h5><?php _e('Associated Courses', WOOINT_TD); ?></h5>
                <ul class="bridge-woo-available-courses">
                    <?php
                    foreach ($product_options['moodle_post_course_id'] as $single_course_id) {
                        if ('publish' === get_post_status($single_course_id)) {
                            ?>
                            <li>
                                <a href="<?php echo esc_url(get_permalink($single_course_id)); ?>" target="_blank"><?php echo get_the_title($single_course_id); ?></a>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </div>
            <?php
        }
    }
}
