<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Region;

use BoxUk\Dictator\Utils;

/**
 * Control the sites on the network
 */
class NetworkSites extends Region
{
    private const SITES_LOOKUP_BATCH_SIZE = 200;

    /**
     * Schema config.
     *
     * @var array $schema
     */
    protected array $schema = [
        '_type' => 'prototype',
        '_get_callback' => 'getSites',
        '_prototype' => [
            '_type' => 'array',
            '_children' => [
                'custom_domain' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'title' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'description' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'active_theme' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'active_plugins' => [
                    '_type' => 'array',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'terms' => [
                    '_type' => 'array',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'users' => [
                    '_type' => 'array',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'timezone_string' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
                'WPLANG' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getSiteValue',
                ],
            ],
        ],
    ];

    /**
     * Correct core's confusing option names.
     *
     * @var array $optionsMap
     */
    protected array $optionsMap = [
        'title' => 'blogname',
        'description' => 'blogdescription',
    ];

    /**
     * Object-level cache.
     *
     * @var $sites
     */
    protected $sites;

    /**
     * Get the differences between declared sites and sites on network
     *
     * @return array
     */
    public function getDifferences(): array
    {
        if (isset($this->differences)) {
            return $this->differences;
        }

        $this->differences = [];
        // Check each declared site in state data against WordPress.
        foreach ($this->getImposedData() as $siteLabel => $siteData) {
            $customDomain = $siteData['custom_domain'] ?? '';
            $siteSlug = $this->getSiteSlug(get_current_site(), $siteLabel, $customDomain);
            $siteResult = $this->getSiteDifference($siteSlug, $siteData);

            if (! empty($siteResult)) {
                $this->differences[$siteLabel] = $siteResult;
            }
        }

        return $this->differences;
    }

    /**
     * Impose some state data onto a region
     *
     * @param string $key Site slug.
     * @param array $value Site data.
     *
     * @throws CouldNotImposeRegionException If the region could not be imposed.
     */
    public function impose(string $key, $value): void
    {
        $customDomain = $value['custom_domain'] ?? '';
        $siteSlug = $this->getSiteSlug(get_current_site(), $key, $customDomain);

        $site = $this->getSite($siteSlug);
        if (! $site) {
            $site = $this->createSite($key, $value);
            if (is_wp_error($site)) {
                throw new CouldNotImposeRegionException($site->get_error_message());
            }
        }

        switch_to_blog($site->blog_id);
        foreach ($value as $field => $singleValue) {
            if (array_key_exists($field, $this->optionsMap)) {
                $field = $this->optionsMap[$field];
            }

            switch ($field) {
                case 'active_theme':
                    if ($singleValue !== get_option('stylesheet')) {
                        switch_theme($singleValue);
                    }

                    break;

                case 'active_plugins':
                    foreach ($singleValue as $plugin) {
                        if (! is_plugin_active($plugin)) {
                            activate_plugin($plugin);
                        }
                    }

                    break;

                case 'users':
                    foreach ($singleValue as $userLogin => $role) {
                        $user = get_user_by('login', $userLogin);
                        if (! $user) {
                            continue;
                        }

                        add_user_to_blog($site->blog_id, $user->ID, $role);
                    }

                    break;

                case 'terms' :
                    foreach ($singleValue as $taxName => $taxTerms) {
                        foreach ( $taxTerms as $termSlug => $termArgs ) {
                            $termArgs['slug'] = $termSlug;

                            if (! array_key_exists('name', $termArgs)) {
                                continue;
                            }
                            $termName = $termArgs['name'];
                            wp_insert_term($termName, $taxName, $termArgs);
                        }
                    }
                    break;

                case 'WPLANG':
                    add_network_option($site->blog_id, $field, $singleValue);
                    break;

                default:
                    update_option($field, $singleValue);

                    break;

            }
        }
        restore_current_blog();
    }

    /**
     * Get a list of all the sites on the network
     *
     * @return array
     */
    protected function getSites(): array
    {
        if (isset($this->sites) && is_array($this->sites)) {
            return array_keys($this->sites);
        }

        $args  = [
            'limit' => self::SITES_LOOKUP_BATCH_SIZE,
            'offset' => 0,
        ];
        $sites = [];
        if (! is_multisite()) {
            return $this->sites;
        }
        do {
            $sitesResults = get_sites($args);
            $sites += $sitesResults;

            $args['offset'] += $args['limit'];
        } while ($sitesResults);

        $this->sites = [];
        foreach ($sites as $site) {
            $siteSlug = $this->getSiteSlug($site);
            $this->sites[$siteSlug] = $site;
        }

        return array_keys($this->sites);
    }

    /**
     * Get the value on a given site
     *
     * @param string $key Key to get value for.
     * @return mixed
     */
    protected function getSiteValue(string $key)
    {
        $siteSlug = $this->currentSchemaAttributeParents[0];
        $site = $this->getSite($siteSlug);

        switch_to_blog($site->blog_id);

        if (array_key_exists($key, $this->optionsMap)) {
            $key = $this->optionsMap[$key];
        }

        switch ($key) {

            case 'custom_domain':
                $value = $site->domain ?? '';
                break;

            case 'active_theme':
                $value = get_option('stylesheet');
                break;

            case 'active_plugins':
                $value = get_option($key, []);
                break;

            case 'users':
                $value = [];

                $siteUsers = get_users();
                foreach ($siteUsers as $siteUser) {
                    $value[ $siteUser->user_login ] = array_shift($siteUser->roles);
                }
                break;

            case 'terms':
                $value = [];
                $site_taxonomies = get_taxonomies();

                foreach ($site_taxonomies as $site_taxonomy) {
                    $taxonomy_values = [];
                    $taxonomy_terms = get_terms([$site_taxonomy], ['hide_empty' => 0]);
                    if (is_wp_error($taxonomy_terms)) {
                        $value[$site_taxonomy] = $taxonomy_values;
                        continue;
                    }

                    foreach($taxonomy_terms as $taxonomy_term) {
                        // Skip over 'Uncategorized' as this gets added by default.
                        if ('uncategorized' === $taxonomy_term->slug) {
                            continue;
                        }

                        $taxonomy_values[$taxonomy_term->slug] = [
                            'name' => wp_specialchars_decode($taxonomy_term->name),
                            'parent' => $taxonomy_term->parent,
                            'description' => $taxonomy_term->description,
                        ];
                    }

                    $value[$site_taxonomy] = $taxonomy_values;
                }
                break;

            case 'WPLANG':
                $value = get_network_option($site->blog_id, $key);
                break;

            default:
                $value = get_option($key);
                break;

        }
        restore_current_blog();

        return $value;
    }

    /**
     * Get the difference of the site data to the site on the network
     *
     * @param string $siteSlug Site slug.
     * @param array  $siteData Site data.
     * @return array
     */
    protected function getSiteDifference(string $siteSlug, array $siteData): array
    {
        $siteResult = [
            'dictated' => $siteData,
            'current' => [],
        ];

        $sites = $this->getCurrentData();

        // If there wasn't a matched site, the site must not exist.
        if (empty($sites[$siteSlug])) {
            return $siteResult;
        }

        $siteResult['current'] = $sites[$siteSlug];

        if (Utils::arrayDiffRecursive($siteResult['dictated'], $siteResult['current'])) {
            return $siteResult;
        }

        return [];
    }

    /**
     * Get a site by its slug
     *
     * @param string $siteSlug Site slug.
     * @return \WP_Site|null
     */
    protected function getSite(string $siteSlug): ?\WP_Site
    {
        // Maybe prime the cache.
        $this->getSites();
        return ! empty($this->sites[$siteSlug]) ? $this->sites[$siteSlug] : null;
    }

    /**
     * Create a new site
     *
     * @param string $key Key of site.
     * @param mixed  $value Value.
     * @return \WP_Site|\WP_Error
     */
    protected function createSite(string $key, $value)
    {
        global $wpdb, $current_site;

        $base = $key;
        $title = ucfirst($base);
        $network = $current_site;
        $meta = $value;
        if (! $network) {
            $networks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->site WHERE id = %d", 1)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if (! empty($networks)) {
                $network = $networks[0];
            }
        }

        // Sanitize.
        if (preg_match('|^([a-zA-Z0-9-])+$|', $base)) {
            $base = strtolower($base);
        }

        // If not a subdomain install, make sure the domain isn't a reserved word.
        if (! is_subdomain_install()) {
            $subdirectoryReservedNames = apply_filters('subdirectory_reserved_names', [ 'page', 'comments', 'blog', 'files', 'feed' ]);
            if (in_array($base, $subdirectoryReservedNames, true)) {
                return new \WP_Error('reserved-word', 'The following words are reserved and cannot be used as blog names: ' . implode(', ', $subdirectoryReservedNames));
            }
        }

        if (is_subdomain_install()) {
            $path = '/';
            $prefix = '';
            if ($base !== '') {
                $prefix = $base . '.';
            }
            $newDomain = $prefix . preg_replace('|^www\.|', '', $network->domain);
        } else {
            $newDomain = $network->domain;
            $path = '/' . trim($base, '/') . '/';
        }

        // Custom domain trumps all.
        if (! empty($value['custom_domain'])) {
            $newDomain = $value['custom_domain'];
            $path = '/';
            unset($value['custom_domain']);
        }

        $userId = 0;
        $superAdmins = get_super_admins();
        if (! empty($superAdmins) && is_array($superAdmins)) {
            // Just get the first one.
            $superLogin = $superAdmins[0];
            $superUser = get_user_by('login', $superLogin);
            if ($superUser) {
                $userId = $superUser->ID;
            }
        }

        $wpdb->hide_errors();
        $id = wpmu_create_blog($newDomain, $path, $title, $userId, $meta, $network->id);
        $wpdb->show_errors();

        if (is_wp_error($id)) {
            return $id;
        }

        // Reset our internal cache.
        unset($this->sites);

        return $this->getSite($this->getSiteSlug(get_site($id))) ?? new \WP_Error('site-not-found', 'Site not found.');
    }

    /**
     * Use the domain plus path for the slug of or sites array. We can pass a key to overwrite path,
     * we can pass a custom domain which overwrites the domain and 'resets' the path.
     *
     * @param \WP_Site | \WP_Network $siteOrNetwork A site or network object.
     * @param string $key A key to overwrite path if not using a custom domain.
     * @param string $customDomain A custom domain to overwrite the domain and reset the path.
     */
    protected function getSiteSlug($siteOrNetwork, string $key = '', string $customDomain = ''): string
    {
        $domain = $siteOrNetwork->domain;
        $path = $key !== '' ? '/' . $key : $siteOrNetwork->path;

        if (! empty($customDomain) && $domain !== $customDomain) {
            $domain = $customDomain;
            $path = '/';
        }

        if ($path !== '/' && is_subdomain_install()) {
            return trim($path . '.' . $domain, '/');
        }

        return trim($domain . $path, '/');
    }
}
