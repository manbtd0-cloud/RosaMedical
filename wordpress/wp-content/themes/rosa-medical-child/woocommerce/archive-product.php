<?php
if (! defined('ABSPATH')) { exit; }
get_header();
get_template_part('template-parts/client-preview/shop-page', null, ['locale' => 'en']);
get_footer();
