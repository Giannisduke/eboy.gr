<section class="featured">

<button class="embla__button embla__button--prev" type="button">
    <svg class="embla__button__svg" viewBox="0 0 54 14"><g>
	<path d="M1.89,6h50.91c0.55,0,1,0.45,1,1v0c0,0.55-0.45,1-1,1H1.89V6z"/>
	<path d="M8.25,13.82c-0.2,0-0.41-0.06-0.59-0.19L1.29,9.01C0.6,8.52,0.21,7.79,0.21,7.03c0-0.76,0.39-1.49,1.07-1.99l6.38-4.67
		c0.45-0.33,1.07-0.23,1.4,0.22c0.33,0.44,0.23,1.07-0.22,1.4L2.46,6.66C2.3,6.77,2.2,6.91,2.21,7.03c0,0.12,0.09,0.25,0.26,0.37
		l6.37,4.62c0.45,0.32,0.55,0.95,0.22,1.4C8.86,13.68,8.56,13.82,8.25,13.82z"/>
</g></svg>
</button>
<button class="embla__button embla__button--next" type="button">
    <svg class="embla__button__svg" viewBox="0 0 54 14"><g>
	<path d="M52.11,6H1.21c-0.55,0-1,0.45-1,1v0c0,0.55,0.45,1,1,1h50.91V6z"/>
	<path d="M45.75,13.82c0.2,0,0.41-0.06,0.59-0.19l6.37-4.62c0.69-0.5,1.08-1.22,1.08-1.98c0-0.76-0.39-1.49-1.07-1.99l-6.38-4.67
		c-0.45-0.33-1.07-0.23-1.4,0.22c-0.33,0.44-0.23,1.07,0.22,1.4l6.38,4.67c0.16,0.12,0.26,0.25,0.26,0.37
		c0,0.12-0.09,0.25-0.26,0.37l-6.37,4.62c-0.45,0.32-0.55,0.95-0.22,1.4C45.14,13.68,45.44,13.82,45.75,13.82z"/>
</g></svg>
</button>

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
                'url'   => get_permalink(),
            ];
        }
    }
}
wp_reset_postdata();
?>
    <div class="offcanvas offcanvas-start" tabindex="-1" id="siteOffcanvas" aria-labelledby="siteOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="siteOffcanvasLabel">Μενού</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Κλείσιμο"></button>
        </div>
        <div class="offcanvas-body">
            @if (has_nav_menu('primary_navigation'))
                <nav aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
                    {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex-column', 'echo' => false]) !!}
                </nav>
            @endif
        </div>
    </div>
<div class="embla">

    <div class="featured_wrapper">
        <div class="row">
            <div class="slogan">
                <?php echo apply_filters('the_content', $page_content); ?>
            </div>
            @php $product_count = wp_count_posts('product')->publish; @endphp
            <div class="slogan-product-count">{{ $product_count }} προϊόντα</div>
        </div>
    </div>

    <div class="embla__viewport">
        <div class="embla__container">
            <?php foreach ($slides as $slide) : ?>
            <div class="embla__slide">
                <a class="embla__slide__inner" href="<?php echo esc_url($slide['url']); ?>">
                    <img src="<?php echo esc_url($slide['image']); ?>" alt="<?php echo esc_attr($slide['title']); ?>">
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="embla__controls">
        <div class="embla__dots"></div>
    </div>

</div>
</section>
