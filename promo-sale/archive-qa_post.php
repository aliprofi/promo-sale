<?php
/**
 * Template for Sales News archive, categories and tags
 */

get_header(); ?>

<div class="sales-archive-container">
    
    <header class="sales-archive-header">
        <h1 class="sales-archive-title">
            <i class="dashicons dashicons-tag"></i>
            <?php
            if (is_post_type_archive('qa_post')) {
                echo 'Новости распродаж';
            } elseif (is_category()) {
                echo 'Рубрика: ' . single_cat_title('', false);
            } elseif (is_tag()) {
                echo 'Тег: ' . single_tag_title('', false);
            } else {
                echo 'Новости распродаж';
            }
            ?>
        </h1>
        <p class="sales-archive-description">
            <?php
            if (is_post_type_archive('qa_post')) {
                echo 'Все актуальные акции и скидки в одном месте';
            } elseif (is_category()) {
                $category_description = category_description();
                echo !empty($category_description) ? strip_tags($category_description) : 'Новости распродаж в рубрике "' . single_cat_title('', false) . '"';
            } elseif (is_tag()) {
                $tag_description = tag_description();
                echo !empty($tag_description) ? strip_tags($tag_description) : 'Новости распродаж с тегом "' . single_tag_title('', false) . '"';
            } else {
                echo 'Все актуальные акции и скидки в одном месте';
            }
            ?>
        </p>
    </header>
    
    <div class="sales-search-form">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="search" 
                   name="s" 
                   placeholder="Поиск по акциям..."
                   value="<?php echo get_search_query(); ?>" />
            <input type="hidden" name="post_type" value="qa_post" />
            <button type="submit">
                <i class="dashicons dashicons-search"></i>
                Найти
            </button>
        </form>
    </div>
    
    <?php if (have_posts()) : ?>
    
    <div class="sales-list">
        <?php while (have_posts()) : the_post(); 
            $start_date = get_post_meta(get_the_ID(), '_sale_start_date', true);
            $end_date = get_post_meta(get_the_ID(), '_sale_end_date', true);
        ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('sales-item'); ?>>
            
            <?php if (has_post_thumbnail()) : ?>
            <div class="sales-item-thumbnail">
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('medium'); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="sales-item-content">
                <header class="sales-item-header">
                    <h2 class="sales-item-title">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h2>
                </header>

                <?php if ($start_date || $end_date) : ?>
                <div class="sales-item-dates">
                    <i class="dashicons dashicons-clock"></i>
                    <?php if ($start_date) echo 'с ' . date_i18n('j F', strtotime($start_date)); ?>
                    <?php if ($end_date) echo ' по ' . date_i18n('j F Y', strtotime($end_date)); ?>
                </div>
                <?php endif; ?>

                <div class="sales-item-excerpt">
                    <?php the_excerpt(); ?>
                </div>

                <footer class="sales-item-footer">
                    <a href="<?php the_permalink(); ?>" class="sales-read-more">
                        Подробнее
                        <i class="dashicons dashicons-arrow-right-alt"></i>
                    </a>
                </footer>
            </div>
            
        </article>
        
        <?php endwhile; ?>
    </div>
    
    <div class="sales-pagination">
        <?php
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => '<i class="dashicons dashicons-arrow-left-alt"></i> Назад',
            'next_text' => 'Вперед <i class="dashicons dashicons-arrow-right-alt"></i>',
        ));
        ?>
    </div>
    
    <?php else : ?>
    
    <div class="sales-no-posts">
        <h2>Акции не найдены</h2>
        <p>К сожалению, по вашему запросу ничего не найдено. Попробуйте изменить параметры поиска.</p>
    </div>
    
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>