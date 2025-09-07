<?php
/**
 * Plugin Name: Новости распродаж
 * Description: Плагин для создания и управления новостями о распродажах с микроразметкой SaleEvent. Включает шорткод [sales_promo] для вывода акций.
 * Version: 2.3.0
 * Author: Ali Profi
 * Author URI: https://aliprofi.ru
 * Text Domain: Ali Sale
 */

// Запретить прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

class SalesNewsPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_data'));
        add_action('wp_head', array($this, 'add_schema_markup'));
        add_filter('single_template', array($this, 'load_single_template'));
        add_filter('archive_template', array($this, 'load_archive_template'));
        add_filter('category_template', array($this, 'load_category_template'));
        add_filter('tag_template', array($this, 'load_tag_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Исправление архивов рубрик - более агрессивный подход
        add_action('pre_get_posts', array($this, 'include_custom_post_in_archives'), 1);
        add_filter('request', array($this, 'fix_archive_requests'));
        
        // Хуки для обработки template_redirect
        add_action('template_redirect', array($this, 'handle_category_archives'), 1);
        
        add_filter('the_content', array($this, 'replace_external_links'), 99);
        add_filter('the_content', array($this, 'add_thumbnail_to_content'), 10);
        
        // Регистрация шорткода
        add_shortcode('sales_promo', array($this, 'sales_promo_shortcode'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->register_post_type();
        $this->register_taxonomies();
    }

    // --- ОБНОВЛЕННАЯ ФУНКЦИЯ: Замена внешних ссылок на <span> с классом spanlink ---
    public function replace_external_links($content) {
        if (is_admin() || !is_main_query() || !is_singular('qa_post')) {
            return $content;
        }

        $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1(.*?)>(.*?)<\/a>/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $href = $matches[2];
            $link_text = $matches[4];
            $site_url = get_site_url();

            // Проверяем, является ли ссылка внутренней
            if (strpos($href, $site_url) !== false || substr($href, 0, 1) === '#' || substr($href, 0, 1) === '/') {
                return $matches[0]; // Если ссылка внутренняя, возвращаем ее как есть
            }
            
            // Если ссылка внешняя, заменяем ее на span с классом spanlink
            $safe_url = esc_attr($href);
            return "<span class=\"spanlink\" onclick=\"GoTo('{$safe_url}')\">{$link_text}</span>";

        }, $content);
    }
    
    public function add_thumbnail_to_content($content) {
        if (is_singular('qa_post') && in_the_loop() && is_main_query()) {
            if (has_post_thumbnail()) {
                $thumbnail_html = get_the_post_thumbnail(get_the_ID(), 'large', array('class' => 'post-thumbnail-in-content'));
                return $thumbnail_html . $content;
            }
        }
        return $content;
    }

    public function register_post_type() {
        $labels = array(
            'name' => 'Новости распродаж', 
            'singular_name' => 'Новость распродажи', 
            'menu_name' => 'Распродажи',
            'add_new' => 'Добавить новость', 
            'add_new_item' => 'Добавить новость распродажи', 
            'new_item' => 'Новая новость',
            'edit_item' => 'Редактировать новость', 
            'view_item' => 'Просмотреть новость', 
            'all_items' => 'Все новости',
            'search_items' => 'Искать новости', 
            'not_found' => 'Новости не найдены', 
            'not_found_in_trash' => 'В корзине новости не найдены'
        );
        $args = array(
            'labels' => $labels, 
            'public' => true, 
            'publicly_queryable' => true, 
            'show_ui' => true, 
            'show_in_menu' => true,
            'query_var' => true, 
            'rewrite' => array(
                'slug' => 'sale-news',
                'with_front' => false,
                'feeds' => true,
                'pages' => true
            ), 
            'capability_type' => 'post', 
            'has_archive' => true,
            'hierarchical' => false, 
            'menu_position' => 5, 
            'menu_icon' => 'dashicons-tag',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'), 
            'show_in_rest' => true, 
            'taxonomies' => array('category', 'post_tag')
        );
        register_post_type('qa_post', $args);
    }
    
    public function register_taxonomies() {
        register_taxonomy_for_object_type('category', 'qa_post');
        register_taxonomy_for_object_type('post_tag', 'qa_post');
    }
    
    // Новый подход для обработки архивов категорий
    public function handle_category_archives() {
        if (is_category() || is_tag()) {
            global $wp_query;
            
            // Получаем текущую категорию/тег
            if (is_category()) {
                $term_id = get_queried_object_id();
                $posts_exist = $this->check_qa_posts_in_category($term_id);
            } else {
                $term_id = get_queried_object_id();
                $posts_exist = $this->check_qa_posts_in_tag($term_id);
            }
            
            if ($posts_exist && $wp_query->found_posts == 0) {
                // Переделываем запрос
                $wp_query = new WP_Query($this->get_category_query_args());
                // Устанавливаем правильные флаги
                if (is_category()) {
                    $wp_query->is_category = true;
                    $wp_query->is_archive = true;
                } else {
                    $wp_query->is_tag = true;
                    $wp_query->is_archive = true;
                }
                $wp_query->is_home = false;
            }
        }
    }
    
    private function get_category_query_args() {
        global $paged;
        
        $args = array(
            'post_type' => array('post', 'qa_post'),
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged
        );
        
        if (is_category()) {
            $args['cat'] = get_queried_object_id();
        } elseif (is_tag()) {
            $args['tag_id'] = get_queried_object_id();
        }
        
        return $args;
    }
    
    private function check_qa_posts_in_category($cat_id) {
        $posts = get_posts(array(
            'post_type' => 'qa_post',
            'category' => $cat_id,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        return !empty($posts);
    }
    
    private function check_qa_posts_in_tag($tag_id) {
        $posts = get_posts(array(
            'post_type' => 'qa_post',
            'tag_id' => $tag_id,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        return !empty($posts);
    }
    
    // Включаем qa_post в архивы рубрик и тегов
    public function include_custom_post_in_archives($query) {
        if (!is_admin() && $query->is_main_query()) {
            if ((is_category() || is_tag()) && !isset($query->query_vars['suppress_filters'])) {
                $post_types = $query->get('post_type');
                if (empty($post_types) || $post_types == 'post') {
                    $query->set('post_type', array('post', 'qa_post'));
                }
            }
        }
        return $query;
    }
    
    // Исправляем пагинацию для архивов рубрик
    public function fix_archive_requests($query_vars) {
        if (isset($query_vars['category_name']) && !isset($query_vars['post_type'])) {
            $query_vars['post_type'] = array('post', 'qa_post');
        }
        if (isset($query_vars['tag']) && !isset($query_vars['post_type'])) {
            $query_vars['post_type'] = array('post', 'qa_post');
        }
        return $query_vars;
    }
    
    public function add_meta_boxes() {
        add_meta_box('sales_meta_box', 'Информация о распродаже', array($this, 'meta_box_callback'), 'qa_post', 'side', 'high');
    }
    
    public function meta_box_callback($post) {
        wp_nonce_field('sales_meta_box_nonce', 'sales_meta_box_nonce');
        $start_date = get_post_meta($post->ID, '_sale_start_date', true);
        $end_date = get_post_meta($post->ID, '_sale_end_date', true);
        $sale_url = get_post_meta($post->ID, '_sale_url', true);
        
        echo '<p><label for="sale_start_date"><strong>Дата начала:</strong></label><br><input type="date" id="sale_start_date" name="sale_start_date" value="' . esc_attr($start_date) . '" style="width:100%;" /></p>';
        echo '<p><label for="sale_end_date"><strong>Дата окончания:</strong></label><br><input type="date" id="sale_end_date" name="sale_end_date" value="' . esc_attr($end_date) . '" style="width:100%;" /></p>';
        echo '<p><label for="sale_url"><strong>URL Распродажи:</strong></label><br><input type="url" id="sale_url" name="sale_url" value="' . esc_url($sale_url) . '" placeholder="https://aliexpress.com/sale" style="width:100%;" /></p>';
        echo '<p><em>Если дату начала не указать, будет использована дата публикации.</em></p>';
    }
    
    public function save_meta_data($post_id) {
        if (!isset($_POST['sales_meta_box_nonce']) || !wp_verify_nonce($_POST['sales_meta_box_nonce'], 'sales_meta_box_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        update_post_meta($post_id, '_sale_start_date', isset($_POST['sale_start_date']) ? sanitize_text_field($_POST['sale_start_date']) : '');
        update_post_meta($post_id, '_sale_end_date', isset($_POST['sale_end_date']) ? sanitize_text_field($_POST['sale_end_date']) : '');
        update_post_meta($post_id, '_sale_url', isset($_POST['sale_url']) ? esc_url_raw($_POST['sale_url']) : '');
    }
    
    public function add_schema_markup() {
        if (is_singular('qa_post')) {
            global $post;
            $start_date = get_post_meta($post->ID, '_sale_start_date', true);
            $end_date = get_post_meta($post->ID, '_sale_end_date', true);
            $sale_url = get_post_meta($post->ID, '_sale_url', true);
            
            if (!$start_date) {
                $start_date = get_the_date('Y-m-d', $post->ID);
            }
            
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'SaleEvent',
                'name' => get_the_title($post->ID),
                'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
                'startDate' => $start_date,
                'url' => get_permalink($post->ID)
            );
            
            if ($end_date) {
                $schema['endDate'] = $end_date;
            }
            
            if ($sale_url) {
                $schema['offers'] = array(
                    '@type' => 'Offer',
                    'url' => $sale_url
                );
            }
            
            if (has_post_thumbnail($post->ID)) {
                $schema['image'] = get_the_post_thumbnail_url($post->ID, 'full');
            }
            
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }
    }
    
  // Функция для обработки шорткода [sales_promo]
public function sales_promo_shortcode($atts) {
    $atts = shortcode_atts(array(
        'count' => 5,
        'category' => '',
        'title' => '🔥 Актуальные распродажи',
        'show_dates' => 'yes'
    ), $atts, 'sales_promo');
    
    $args = array(
        'post_type' => 'qa_post',
        'posts_per_page' => intval($atts['count']),
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    if (!empty($atts['category'])) {
        if (is_numeric($atts['category'])) {
            $args['cat'] = intval($atts['category']);
        } else {
            $args['category_name'] = sanitize_text_field($atts['category']);
        }
    }
    
    $posts_query = new WP_Query($args);
    
    if (!$posts_query->have_posts()) {
        return '<div class="sales-promo-block"><p>Активные распродажи не найдены</p></div>';
    }
    
    ob_start();
    ?>
    <div class="sales-promo-block">
        <h3 class="sales-promo-title"><?php echo esc_html($atts['title']); ?></h3>
        
        <div class="sales-promo-list">
            <?php while ($posts_query->have_posts()) : 
                $posts_query->the_post();
                $start_date = get_post_meta(get_the_ID(), '_sale_start_date', true);
                $end_date = get_post_meta(get_the_ID(), '_sale_end_date', true);
                $sale_url = get_post_meta(get_the_ID(), '_sale_url', true);
            ?>
            <div class="sales-promo-item">
                <div class="sales-promo-content">
                    <h4 class="sales-promo-item-title">
                        <a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title(); ?></a>
                    </h4>
                    
                    <?php if ($atts['show_dates'] === 'yes' && ($start_date || $end_date)) : ?>
                    <div class="sales-promo-dates">
                        <i class="dashicons dashicons-clock"></i>
                        <?php if ($start_date) echo 'с ' . date_i18n('j M', strtotime($start_date)); ?>
                        <?php if ($end_date) echo ' по ' . date_i18n('j M Y', strtotime($end_date)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="sales-promo-excerpt">
                        <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                    </div>
                </div>
                
                <div class="sales-promo-action">
                    <?php if ($sale_url) : ?>
                    <span class="sales-promo-btn" onclick="GoTo('<?php echo esc_attr($sale_url); ?>')">
                        Перейти
                        <i class="dashicons dashicons-external"></i>
                    </span>
                    <?php else : ?>
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="sales-promo-btn">
                        Подробнее
                        <i class="dashicons dashicons-arrow-right-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; 
            wp_reset_postdata(); ?>
        </div>
        
        <div class="sales-promo-footer">
            <a href="<?php echo esc_url(get_post_type_archive_link('qa_post')); ?>" class="sales-promo-all">
                Все распродажи
                <i class="dashicons dashicons-arrow-right-alt"></i>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
    
    public function load_single_template($template) {
        if (is_singular('qa_post')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'single-qa_post.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }
    
    public function load_archive_template($template) {
        if (is_post_type_archive('qa_post')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'archive-qa_post.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }
    
    public function load_category_template($template) {
        if (is_category()) {
            // Проверяем, есть ли qa_post записи в этой категории
            $cat_id = get_queried_object_id();
            if ($this->check_qa_posts_in_category($cat_id)) {
                $plugin_template = plugin_dir_path(__FILE__) . 'archive-qa_post.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }
    
    public function load_tag_template($template) {
        if (is_tag()) {
            // Проверяем, есть ли qa_post записи с этим тегом
            $tag_id = get_queried_object_id();
            if ($this->check_qa_posts_in_tag($tag_id)) {
                $plugin_template = plugin_dir_path(__FILE__) . 'archive-qa_post.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }
    
  public function enqueue_assets() {
    // Загружаем стили на страницах с qa_post, архивах и везде где может быть шорткод
    if (is_singular('qa_post') || is_post_type_archive('qa_post') || 
        (is_category() && $this->check_qa_posts_in_category(get_queried_object_id())) ||
        (is_tag() && $this->check_qa_posts_in_tag(get_queried_object_id())) ||
        $this->has_sales_promo_shortcode()) {
        
        wp_enqueue_style('sales-news-styles', plugin_dir_url(__FILE__) . 'qa-styles.css', array(), '2.3.2'); // Обновили версию
        wp_enqueue_script('sales-news-script', plugin_dir_url(__FILE__) . 'main.js', array(), '2.3.2', true); // Обновили версию
    }
}
    
    // Проверяем, есть ли шорткод [sales_promo] на текущей странице
    private function has_sales_promo_shortcode() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'sales_promo')) {
            return true;
        }
        return false;
    }
    
    public function activate() { 
        $this->register_post_type(); 
        $this->register_taxonomies();
        flush_rewrite_rules(); 
        
        // Дополнительно очищаем кэш объектов
        wp_cache_flush();
    }
    
    public function deactivate() { 
        flush_rewrite_rules(); 
        wp_cache_flush();
    }
}

new SalesNewsPlugin();