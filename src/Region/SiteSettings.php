<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Region;

use BoxUk\Dictator\Utils;

class SiteSettings extends Region
{
    /**
     * Schema config.
     *
     * @var array $schema
     */
    protected array $schema = [
        '_type'     => 'array',
        '_children' => [
            /**
             * General
             */
            'title'               => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'description'         => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'admin_email'         => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'timezone'            => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'WPLANG'              => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'date_format'         => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'time_format'         => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            /**
             * Reading
             */
            'public'              => [
                '_type'         => 'bool',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'posts_per_page'      => [
                '_type'         => 'numeric',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'posts_per_feed'      => [
                '_type'         => 'numeric',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'feed_uses_excerpt'   => [
                '_type'         => 'bool',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'show_on_front'       => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'page_on_front'       => [
                '_type'         => 'numeric',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'page_for_posts'      => [
                '_type'         => 'numeric',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            /**
             * Discussion
             */
            'allow_comments'      => [
                '_type'         => 'bool',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'allow_pingbacks'     => [
                '_type'         => 'bool',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'notify_comments'     => [
                '_type'         => 'bool',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'notify_moderation'   => [
                '_type'         => 'bool',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            /**
             * Permalinks
             */
            'permalink_structure' => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'category_base'       => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'tag_base'            => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            /**
             * Theme / plugins
             */
            'active_theme'        => [
                '_type'         => 'text',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
            'active_plugins'      => [
                '_type'         => 'array',
                '_required'     => false,
                '_get_callback' => 'get',
            ],
        ],
    ];

    /**
     * Correct core's confusing option names.
     *
     * @var array $optionsMap
     */
    protected array $optionsMap = [
        'title'             => 'blogname',
        'description'       => 'blogdescription',
        'timezone'          => 'timezone_string',
        'public'            => 'blog_public',
        'posts_per_feed'    => 'posts_per_rss',
        'feed_uses_excerpt' => 'rss_use_excerpt',
        'allow_comments'    => 'default_comment_status',
        'allow_pingbacks'   => 'default_ping_status',
        'notify_comments'   => 'comments_notify',
        'notify_moderation' => 'moderation_notify',
    ];

    /**
     * Impose some data onto the region
     * How the data is interpreted depends
     * on the region
     *
     * @param string $_ Unused.
     * @param array $options Options to impose.
     * @return true|\WP_Error
     */
    public function impose(string $_, $options)
    {
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->optionsMap)) {
                $key = $this->optionsMap[ $key ];
            }

            switch ($key) {

                case 'active_theme':
                    if ($value !== get_option('stylesheet')) {
                        switch_theme($value);
                    }
                    break;

                case 'active_plugins':
                    foreach ($value as $plugin) {
                        if (! is_plugin_active($plugin)) {
                            activate_plugin($plugin);
                        }
                    }
                    break;

                // Boolean stored as 0 or 1.
                case 'blog_public':
                case 'rss_use_excerpt':
                case 'comments_notify':
                case 'moderation_notify':
                    update_option($key, (int)$value);
                    break;

                // Boolean stored as 'open' or 'closed'.
                case 'default_comment_status':
                case 'default_ping_status':
                    if ($value) {
                        update_option($key, 'open');
                    } else {
                        update_option($key, 'closed');
                    }
                    break;

                default:
                    update_option($key, $value);
                    break;
            }
        }

        return true;
    }

    /**
     * Get the differences between the state file and WordPress
     *
     * @return array
     */
    public function getDifferences(): array
    {
        $result = [
            'dictated' => $this->getImposedData(),
            'current'  => $this->getCurrentData(),
        ];

        if (Utils::arrayDiffRecursive($result['dictated'], $result['current'])) {
            return ['option' => $result];
        }

        return [];
    }

    /**
     * Get the value for the setting
     *
     * @param string $name Name to get value for.
     * @return mixed
     */
    public function get(string $name)
    {
        if (array_key_exists($name, $this->optionsMap)) {
            $name = $this->optionsMap[ $name ];
        }

        switch ($name) {
            case 'active_theme':
                $value = get_option('stylesheet');
                break;

            default:
                $value = get_option($name);
                break;
        }

        // Data transformation if we need to.
        switch ($name) {
            case 'default_comment_status':
            case 'default_ping_status':
                $value = 'open' === $value;
                break;

        }

        return $value;
    }
}
