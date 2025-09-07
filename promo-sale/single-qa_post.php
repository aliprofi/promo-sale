<?php
/**
 * Template for single Sale News post
 */

get_header(); ?>

<div class="sales-container">
    <?php while (have_posts()) : the_post(); 
        $start_date = get_post_meta(get_the_ID(), '_sale_start_date', true);
        $end_date = get_post_meta(get_the_ID(), '_sale_end_date', true);
        $sale_url = get_post_meta(get_the_ID(), '_sale_url', true);
    ?>
    
    <article id="post-<?php the_ID(); ?>" <?php post_class('sales-single'); ?>>
        
        <header class="sales-header">
            <h1 class="sales-title"><?php the_title(); ?></h1>
            
            <div class="sales-meta">
                <span class="sales-date">
                    <i class="dashicons dashicons-calendar-alt"></i>
                    Опубликовано: <?php echo get_the_date(); ?>
                </span>
                
                <?php if (has_category()) : ?>
                <span class="sales-categories">
                    <i class="dashicons dashicons-category"></i>
                    <?php the_category(', '); ?>
                </span>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="sales-content">

            <?php if ($start_date || $end_date) : ?>
            <div class="sales-dates-block">
                <h2 class="sales-dates-title">
                    <i class="dashicons dashicons-clock"></i>
                    Сроки проведения акции
                </h2>
                <div class="sales-dates-text">
                    <?php if ($start_date) : ?>
                        <span>Начало: <strong><?php echo date_i18n('j F Y', strtotime($start_date)); ?></strong></span>
                    <?php endif; ?>
                    <?php if ($end_date) : ?>
                        <span>Окончание: <strong><?php echo date_i18n('j F Y', strtotime($end_date)); ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="sales-description-block">
                <h2 class="sales-description-title">
                    <i class="dashicons dashicons-info-outline"></i>
                    Описание акции
                </h2>
                <div class="sales-description-text">
                    <?php the_content(); /* Здесь выводится основной контент и миниатюра из плагина */ ?>
                </div>
            </div>

            <?php if ($sale_url) : ?>
            <div class="sales-cta-block">
                <!-- ИЗМЕНЕНО: используем span с onclick вместо ссылки -->
                <span class="sales-cta-button" onclick="GoTo('<?php echo esc_attr($sale_url); ?>')">
                    Перейти к распродаже
                    <i class="dashicons dashicons-arrow-right-alt"></i>
                </span>
            </div>
            <?php endif; ?>
            
        </div>

        <?php if (has_tag()) : ?>
        <footer class="sales-footer">
            <div class="sales-tags">
                <i class="dashicons dashicons-tag"></i>
                <?php the_tags('<span>', '</span><span>', '</span>'); ?>
            </div>
        </footer>
        <?php endif; ?>
        
    </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>