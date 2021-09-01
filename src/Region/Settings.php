<?php

namespace BoxUk\Dictator\Region;

use BoxUk\Dictator\Region;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Settings extends Region
{
    public const KEY = 'settings';

    protected const OPTION_MAP = [
        'title' => 'blogname',
        'description' => 'blogdescription',
        'timezone' => 'timezone_string',
        'public' => 'blog_public',
        'posts_per_feed' => 'posts_per_rss',
        'feed_uses_excerpt' => 'rss_use_excerpt',
        'allow_comments' => 'default_comment_status',
        'allow_pingbacks' => 'default_ping_status',
        'notify_comments' => 'comments_notify',
        'notify_moderation' => 'moderation_notify',
        'active_theme' => 'stylesheet',
    ];

    protected function configureData(OptionsResolver $resolver): void
    {
        $resolver->define('title')
                 ->allowedTypes('string')
                 ->info('Title of your blog');

        $resolver->define('description')
                 ->allowedTypes('string')
                 ->info('Description of your blog');

        $resolver->define('admin_email')
                 ->allowedTypes('string')
                 ->info('Admin email address for your blog');

        $resolver->define('super_admins')
                 ->allowedTypes('array')
                 ->info('Array of admin usernames to set as super admin');
    }
}
