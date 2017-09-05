<?php

if (!empty($order)) {
    $items = $order->get_items(); //Get Item details

    $list_of_course_ids = array();

    foreach ($items as $single_item) {
        //$product_id = isset($single_item['product_id'])? $single_item['product_id'] : '';
        $product_id = '';
        if (isset($single_item['product_id'])) {
            $_product = wc_get_product($single_item['product_id']);

            if ($_product && $_product->is_type('variable') && isset($single_item['variation_id'])) {
                //The line item is a variable product, so consider its variation.
                $product_id = $single_item['variation_id'];
            } else {
                $product_id = $single_item['product_id'];
            }
        }

        if (is_numeric($product_id)) {
            $product_options = get_post_meta($product_id, 'product_options', true);

            if (!empty($product_options) && isset($product_options['moodle_post_course_id']) && !empty($product_options['moodle_post_course_id'])) {
                $line_item_course_ids = $product_options['moodle_post_course_id'];

                if (!empty($list_of_course_ids)) {
                    $list_of_course_ids = array_unique(array_merge($list_of_course_ids, $line_item_course_ids), SORT_REGULAR);
                } else {
                    $list_of_course_ids = $line_item_course_ids;
                }
            }
        }
    }//foreach ends

    if (!empty($list_of_course_ids)) {
        ?>
        
            <h4><?php _e('Courses', WOOINT_TD);
        ?></h4>
                
            <ul class="bridge-woo-courses">
                    
                <?php
                foreach ($list_of_course_ids as $single_course_id) {
                    ?>
                        <li><a href="<?php echo esc_url(get_permalink($single_course_id));
                    ?>"><?php echo get_the_title($single_course_id);
                    ?></a></li>
                        <?php
                }
        ?>
                </ul>
        
            <?php
    }
}
