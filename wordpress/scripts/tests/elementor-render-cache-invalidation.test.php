<?php

declare(strict_types=1);

namespace {
    $GLOBALS['rosa_cache_options'] = [
        'rosa_elementor_render_cache_version' => 'old-version',
    ];
    $GLOBALS['rosa_cache_meta'] = [
        28 => [
            '_wp_page_template' => 'page-templates/rosa-elementor-authoring.php',
            '_elementor_element_cache' => '<section data-preview-map-role>STALE</section>',
            '_elementor_css' => 'stale-css',
            '_elementor_data' => '[{"id":"contact-en","widgetType":"rosa-contact-map"}]',
        ],
        29 => [
            '_wp_page_template' => 'page-templates/rosa-elementor-authoring.php',
            '_elementor_element_cache' => '<section data-preview-map-role>STALE AR</section>',
            '_elementor_css' => 'stale-css-ar',
            '_elementor_data' => '[{"id":"contact-ar","widgetType":"rosa-contact-map"}]',
        ],
        99 => [
            '_wp_page_template' => 'default',
            '_elementor_element_cache' => 'unrelated-cache',
            '_elementor_css' => 'unrelated-css',
            '_elementor_data' => '[{"id":"unrelated"}]',
        ],
    ];
    $GLOBALS['rosa_cache_deletions'] = [];
    $GLOBALS['rosa_cache_cleaned'] = [];
    $GLOBALS['rosa_cache_queries'] = [];

    function get_option(string $name, mixed $default = false): mixed
    {
        return array_key_exists($name, $GLOBALS['rosa_cache_options'])
            ? $GLOBALS['rosa_cache_options'][$name]
            : $default;
    }

    function update_option(string $name, mixed $value, mixed $autoload = null): bool
    {
        $GLOBALS['rosa_cache_options'][$name] = $value;
        return true;
    }

    function get_posts(array $args = []): array
    {
        $GLOBALS['rosa_cache_queries'][] = $args;
        return [28, 29];
    }

    function get_post_meta(int $postId, string $key, bool $single = false): mixed
    {
        return $GLOBALS['rosa_cache_meta'][$postId][$key] ?? '';
    }

    function delete_post_meta(int $postId, string $key): bool
    {
        $GLOBALS['rosa_cache_deletions'][] = [$postId, $key];
        unset($GLOBALS['rosa_cache_meta'][$postId][$key]);
        return true;
    }

    function clean_post_cache(int $postId): void
    {
        $GLOBALS['rosa_cache_cleaned'][] = $postId;
    }

    function fail_cache_test(string $message): never
    {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }

    $file = __DIR__ . '/../../wp-content/plugins/rosa-medical-core/src/Elementor/ElementorRenderCache.php';
    if (! is_file($file)) {
        fail_cache_test('Elementor renderer cache invalidator missing');
    }
    require_once $file;

    use RosaMedical\Core\Elementor\ElementorRenderCache;

    if (! defined(ElementorRenderCache::class . '::VERSION')) {
        fail_cache_test('Elementor renderer cache version constant missing');
    }
    if (! defined(ElementorRenderCache::class . '::OPTION')) {
        fail_cache_test('Elementor renderer cache option constant missing');
    }

    $beforeData = [
        28 => $GLOBALS['rosa_cache_meta'][28]['_elementor_data'],
        29 => $GLOBALS['rosa_cache_meta'][29]['_elementor_data'],
        99 => $GLOBALS['rosa_cache_meta'][99]['_elementor_data'],
    ];

    ElementorRenderCache::ensureFresh();

    $query = $GLOBALS['rosa_cache_queries'][0] ?? [];
    if (($query['post_type'] ?? null) !== 'page'
        || ($query['fields'] ?? null) !== 'ids'
        || ($query['meta_key'] ?? null) !== '_wp_page_template'
        || ($query['meta_value'] ?? null) !== 'page-templates/rosa-elementor-authoring.php') {
        fail_cache_test('Cache invalidator must target only Rosa Elementor authoring pages');
    }

    foreach ([28, 29] as $id) {
        if (array_key_exists('_elementor_element_cache', $GLOBALS['rosa_cache_meta'][$id])) {
            fail_cache_test("Rendered Elementor cache was not cleared for post {$id}");
        }
        if (array_key_exists('_elementor_css', $GLOBALS['rosa_cache_meta'][$id])) {
            fail_cache_test("Generated Elementor CSS cache was not cleared for post {$id}");
        }
        if (($GLOBALS['rosa_cache_meta'][$id]['_elementor_data'] ?? null) !== $beforeData[$id]) {
            fail_cache_test("Elementor document data changed while invalidating post {$id}");
        }
    }

    if (($GLOBALS['rosa_cache_meta'][99]['_elementor_element_cache'] ?? '') !== 'unrelated-cache'
        || ($GLOBALS['rosa_cache_meta'][99]['_elementor_css'] ?? '') !== 'unrelated-css'
        || ($GLOBALS['rosa_cache_meta'][99]['_elementor_data'] ?? '') !== $beforeData[99]) {
        fail_cache_test('Cache invalidator touched a non-Rosa Elementor authoring page');
    }

    sort($GLOBALS['rosa_cache_cleaned']);
    if ($GLOBALS['rosa_cache_cleaned'] !== [28, 29]) {
        fail_cache_test('WordPress post cache was not cleaned for every Rosa Elementor authoring page');
    }

    if (($GLOBALS['rosa_cache_options'][ElementorRenderCache::OPTION] ?? null) !== ElementorRenderCache::VERSION) {
        fail_cache_test('Renderer cache version option was not advanced after invalidation');
    }

    $deletionCount = count($GLOBALS['rosa_cache_deletions']);
    $queryCount = count($GLOBALS['rosa_cache_queries']);
    $cleanCount = count($GLOBALS['rosa_cache_cleaned']);

    ElementorRenderCache::ensureFresh();

    if (count($GLOBALS['rosa_cache_deletions']) !== $deletionCount
        || count($GLOBALS['rosa_cache_queries']) !== $queryCount
        || count($GLOBALS['rosa_cache_cleaned']) !== $cleanCount) {
        fail_cache_test('Renderer cache invalidation repeated after the version was already current');
    }

    fwrite(STDOUT, "PASS: versioned Elementor renderer cache invalidation clears stale HTML/CSS once without changing authoring data\n");
}
