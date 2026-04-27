<section class="featured">
<?php
global $post;
$page_content = $post ? $post->post_content : '';

$slides = [];
$args = [
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'tax_query'      => [[
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => 'featured',
    ]],
];
$slider_query = new WP_Query($args);

if ($slider_query->have_posts()) {
    while ($slider_query->have_posts()) {
        $slider_query->the_post();
        if (has_post_thumbnail()) {
            $thumb_id  = get_post_thumbnail_id();
            $thumb_src = wp_get_attachment_image_src($thumb_id, 'large', true);
            $slides[]  = [
                'title' => get_the_title(),
                'image' => $thumb_src[0],
            ];
        }
    }
}
wp_reset_postdata();
?>

<div class="embla">

    <div class="container">
        <div class="row">
            <div class="col content">
                <?php echo apply_filters('the_content', $page_content); ?>
            </div>
        </div>
    </div>

    <div class="embla__viewport">
        <div class="embla__container">
            <?php foreach ($slides as $slide) : ?>
            <div class="embla__slide">
                <div class="embla__slide__inner">
                    <img src="<?php echo esc_url($slide['image']); ?>" alt="<?php echo esc_attr($slide['title']); ?>">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="embla__controls">
        <div class="embla__buttons">
            <button class="embla__button embla__button--prev" type="button">
                <svg class="embla__button__svg" viewBox="0 0 532 532"><path d="M355.66 11.354c13.793-13.805 36.208-13.805 50.001 0 13.785 13.804 13.785 36.238 0 50.034L201.22 266l204.442 204.61c13.785 13.805 13.785 36.239 0 50.044-13.793 13.795-36.208 13.795-50.001 0L118.34 291.413c-13.785-13.805-13.785-36.239 0-50.044l237.32-229.915z"/></svg>
            </button>
            <button class="embla__button embla__button--next" type="button">
                <svg class="embla__button__svg" viewBox="0 0 532 532"><path d="M176.34 520.646c-13.793 13.805-36.208 13.805-50.001 0-13.785-13.804-13.785-36.238 0-50.034L330.78 266 126.34 61.391c-13.785-13.805-13.785-36.239 0-50.044 13.793-13.795 36.208-13.795 50.001 0L413.66 240.588c13.785 13.805 13.785 36.239 0 50.044L176.34 520.646z"/></svg>
            </button>
        </div>
        <div class="embla__dots"></div>
    </div>

</div>
</section>
