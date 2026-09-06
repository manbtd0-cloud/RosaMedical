<?php
if (! defined('ABSPATH')) { exit; }

$sectionArgs = isset($args) && is_array($args) ? $args : [];
$locale = (string) ($sectionArgs['locale'] ?? rosa_preview_locale());
$locale = $locale === 'ar' ? 'ar' : 'en';
$c = static fn(string $key, string $en, string $ar): string => rosa_preview_content('shop', $key, $locale, $locale === 'ar' ? $ar : $en);

$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash((string) $_GET['s'])) : '';
$shopUrl = $locale === 'ar' ? home_url('/ar/shop/') : (get_post_type_archive_link('product') ?: home_url('/shop/'));

$heroTitle = $c('hero_title', 'Find Product', 'اعثر على المنتج');
if (($locale === 'en' && trim($heroTitle) === 'Shop') || ($locale === 'ar' && trim($heroTitle) === 'المنتجات')) {
    $heroTitle = $locale === 'ar' ? 'اعثر على المنتج' : 'Find Product';
}

$productQueryArgs = [
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => 18,
    'orderby' => [
        'menu_order' => 'ASC',
        'title' => 'ASC',
    ],
];
if ($search !== '') {
    $productQueryArgs['s'] = $search;
}
$productQuery = new WP_Query($productQueryArgs);

$families = [
    ['slug' => 'knives', 'label' => 'Knives'],
    ['slug' => 'scissors', 'label' => 'Scissors'],
    ['slug' => 'punches', 'label' => 'Punches'],
    ['slug' => 'chisels', 'label' => 'Chisels'],
    ['slug' => 'cutters', 'label' => 'Cutters'],
];

$familyUrl = static function (string $slug) use ($locale, $shopUrl): string {
    $term = get_term_by('slug', $slug, 'product_cat');
    if ($term instanceof WP_Term) {
        $url = get_term_link($term);
        if (! is_wp_error($url)) {
            return (string) $url;
        }
    }
    return add_query_arg('family', $slug, $shopUrl);
};

$workflow = $locale === 'ar'
    ? [
        ['01', 'حدد فئة الأداة', 'ابدأ بنوع الأداة أو الفئة التي تحتاجها.'],
        ['02', 'شارك المرجع', 'أرسل رمز الكتالوج أو التكوين المتاح لديك.'],
        ['03', 'اطلب عرض سعر', 'تواصل مع روزا للحصول على دعم التوريد.'],
    ]
    : [
        ['01', 'Identify the family', 'Start with the instrument type or family you need.'],
        ['02', 'Share the reference', 'Send the catalogue code or configuration you already have.'],
        ['03', 'Request a quotation', 'Contact Rosa for clear procurement support.'],
    ];
?>
<section class="rosa-preview-shop-hero rosa-live-shop-hero" data-preview-shop-hero>
  <div class="rosa-preview-rail rosa-live-shop-hero__inner">
    <p class="rosa-preview-eyebrow"><?php echo esc_html($c('hero_eyebrow', 'ROSA', 'ROSA')); ?></p>
    <h1><?php echo esc_html($heroTitle); ?></h1>
    <p><?php echo esc_html($c('hero_body', 'Search Rosa instrument families and catalogue references.', 'ابحث في فئات أدوات روزا ومراجع الكتالوج.')); ?></p>
    <form class="rosa-live-shop-search" role="search" method="get" action="<?php echo esc_url($shopUrl); ?>">
      <label class="screen-reader-text" for="rosa-live-shop-search"><?php echo esc_html($c('search_label', 'Search products', 'البحث في المنتجات')); ?></label>
      <input id="rosa-live-shop-search" name="s" type="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr($c('search_label', 'Search products', 'البحث في المنتجات')); ?>">
      <?php if ($locale === 'en') : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
      <button type="submit" class="rosa-preview-button rosa-preview-button--accent"><?php echo esc_html($c('search_button', 'Search', 'بحث')); ?></button>
    </form>
  </div>
</section>

<section class="rosa-live-shop-catalogue" aria-labelledby="rosa-live-shop-catalogue-title">
  <div class="rosa-preview-rail">
    <div class="rosa-live-shop-heading">
      <div>
        <p class="rosa-preview-eyebrow"><?php echo esc_html($locale === 'ar' ? 'كتالوج روزا' : 'ROSA CATALOGUE'); ?></p>
        <h2 id="rosa-live-shop-catalogue-title"><?php echo esc_html($locale === 'ar' ? 'استكشف الأدوات حسب الفئة والمرجع' : 'Explore instruments by family and reference'); ?></h2>
      </div>
      <p><?php echo esc_html($locale === 'ar' ? 'استخدم اسم الأداة أو مرجع الكتالوج للوصول إلى التكوين المطلوب.' : 'Use the instrument name or catalogue reference to find the configuration you need.'); ?></p>
    </div>

    <div class="rosa-preview-shop-grid rosa-live-shop-grid" data-preview-shop-grid>
      <?php
      $rendered = 0;
      if ($productQuery->have_posts()) :
          while ($productQuery->have_posts()) :
              $productQuery->the_post();
              $product = wc_get_product(get_the_ID());
              if (! $product instanceof WC_Product) {
                  continue;
              }
              get_template_part('template-parts/client-preview/product-card', null, ['product' => $product, 'locale' => $locale]);
              $rendered++;
          endwhile;
          wp_reset_postdata();
      endif;

      // The frozen public Shop has a dense catalogue surface. In representative
      // local datasets, supplement the real Woo products with family-navigation
      // cards only; product truth itself remains exclusively in WooCommerce.
      $familySequence = [0, 1, 2, 3, 4, 0, 2, 3, 4, 1, 2, 0];
      $familyCursor = 0;
      while ($rendered < 12) :
          $family = $families[$familySequence[$familyCursor % count($familySequence)]];
          get_template_part('template-parts/client-preview/product-card', null, [
              'family' => [
                  'label' => $family['label'],
                  'url' => $familyUrl($family['slug']),
              ],
              'locale' => $locale,
              'media_slot' => 'catalogue-family-' . $family['slug'],
          ]);
          $rendered++;
          $familyCursor++;
      endwhile;

      if ($rendered === 0) : ?>
        <p class="rosa-preview-shop-empty"><?php echo esc_html($c('empty_state', 'No products matched this view.', 'لا توجد منتجات متاحة في هذه المعاينة.')); ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="rosa-live-shop-workflow" data-preview-shop-workflow>
  <div class="rosa-preview-rail rosa-live-shop-workflow__layout">
    <div class="rosa-live-shop-workflow__intro">
      <p class="rosa-preview-eyebrow"><?php echo esc_html($locale === 'ar' ? 'مسار واضح' : 'A CLEAR WORKFLOW'); ?></p>
      <h2><?php echo esc_html($locale === 'ar' ? 'حوّل احتياجك للأداة إلى طلب توريد واضح.' : 'Turn an instrument need into a clear procurement request.'); ?></h2>
      <p><?php echo esc_html($locale === 'ar' ? 'ثلاث خطوات تساعد فريق روزا على فهم ما تحتاجه بسرعة.' : 'Three simple steps help the Rosa team understand exactly what you need.'); ?></p>
    </div>
    <div class="rosa-live-shop-workflow__steps">
      <?php foreach ($workflow as [$number, $title, $body]) : ?>
        <article><span><?php echo esc_html($number); ?></span><div><h3><?php echo esc_html($title); ?></h3><p><?php echo esc_html($body); ?></p></div></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="rosa-live-shop-support" data-preview-shop-support>
  <div class="rosa-preview-rail rosa-live-shop-support__layout">
    <div class="rosa-live-shop-support__intro">
      <p class="rosa-preview-eyebrow"><?php echo esc_html($locale === 'ar' ? 'دعم التوريد' : 'PROCUREMENT SUPPORT'); ?></p>
      <h2><?php echo esc_html($locale === 'ar' ? 'دعم واضح من الكتالوج إلى طلب عرض السعر' : 'Clear support from catalogue discovery to quotation'); ?></h2>
      <?php get_template_part('template-parts/client-preview/media-slot', null, ['slot' => 'home-why-01', 'label' => $locale === 'ar' ? 'دعم توريد أدوات روزا' : 'Rosa instrument procurement']); ?>
    </div>
    <div class="rosa-live-shop-support__grid">
      <article><span>01</span><div><h3><?php echo esc_html($locale === 'ar' ? 'مراجع واضحة' : 'Clear references'); ?></h3><p><?php echo esc_html($locale === 'ar' ? 'استخدم أسماء الفئات وأكواد الكتالوج عند تحديد احتياجك.' : 'Use family names and catalogue codes when identifying your requirement.'); ?></p></div></article>
      <article><span>02</span><div><h3><?php echo esc_html($locale === 'ar' ? 'تكوينات دقيقة' : 'Exact configurations'); ?></h3><p><?php echo esc_html($locale === 'ar' ? 'راجع الخيارات المتاحة للأداة قبل إرسال الطلب.' : 'Review the available instrument options before sending your request.'); ?></p></div></article>
      <article><span>03</span><div><h3><?php echo esc_html($locale === 'ar' ? 'تواصل مباشر' : 'Direct support'); ?></h3><p><?php echo esc_html($locale === 'ar' ? 'شارك متطلباتك مع فريق روزا للحصول على دعم عرض السعر.' : 'Share your requirements with the Rosa team for quotation support.'); ?></p></div></article>
    </div>
  </div>
</section>

<section class="rosa-live-shop-families" data-preview-shop-families>
  <div class="rosa-preview-rail">
    <div class="rosa-live-shop-heading rosa-live-shop-families__heading">
      <div>
        <p class="rosa-preview-eyebrow"><?php echo esc_html($locale === 'ar' ? 'فئات الأدوات' : 'INSTRUMENT FAMILIES'); ?></p>
        <h2><?php echo esc_html($locale === 'ar' ? 'ابدأ من الفئة المناسبة' : 'Start with the right instrument family'); ?></h2>
      </div>
    </div>
    <nav class="rosa-live-shop-families__grid" aria-label="<?php echo esc_attr($locale === 'ar' ? 'فئات المنتجات' : 'Product families'); ?>">
      <?php foreach ($families as $index => $family) : ?>
        <a href="<?php echo esc_url($familyUrl($family['slug'])); ?>"><span><?php echo esc_html('0' . ($index + 1)); ?></span><strong><?php echo esc_html(rosa_preview_family_label($family['label'], $locale)); ?></strong><b aria-hidden="true">→</b></a>
      <?php endforeach; ?>
    </nav>
  </div>
</section>

<?php get_template_part('template-parts/client-preview/cta-banner', null, ['locale' => $locale]); ?>
