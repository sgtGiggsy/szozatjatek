<?php
class Szozat_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'szozat_stat_widget',
            __('Szozat statisztika', 'szozat'),
            ['description' => __('Felhasználói statisztikák megjelenítése.', 'szozat')]
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        // Cím (opcionális)
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        // Meghívjuk a Szozat osztály logikáját
        if (class_exists('Szozat') && method_exists('Szozat', 'render_widget_stats')) {
            echo Szozat::render_widget_stats();
        } else {
            echo '<p>Nem érhető el statisztika.</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        // Opcionálisan kezelheted a cím beállítását itt
        $title = !empty($instance['title']) ? esc_attr($instance['title']) : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Cím:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo $title; ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}
