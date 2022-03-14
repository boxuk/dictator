<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Region;

use BoxUk\Dictator\Utils;

class NetworkSettings extends Region
{
    /**
     * Schema config.
     *
     * @var array $schema
     */
    protected array $schema = [
        '_type' => 'array',
        '_children' => [
            'title' => [
                '_type' => 'text',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'admin_email' => [
                '_type' => 'email',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'super_admins' => [
                '_type' => 'array',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'registration' => [
                '_type' => 'text',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'notify_registration' => [
                '_type' => 'bool',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'upload_filetypes' => [
                '_type' => 'text',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'site_unlimited_upload' => [
                '_type' => 'bool',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'site_upload_space' => [
                '_type' => 'numeric',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'site_max_upload' => [
                '_type' => 'numeric',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'enabled_themes' => [
                '_type' => 'array',
                '_required' => false,
                '_get_callback' => 'get',
            ],
            'active_plugins' => [
                '_type' => 'array',
                '_required' => false,
                '_get_callback' => 'get',
            ],
        ],
    ];

    /**
     * Correct core's confusing option names
     *
     * @var array $optionsMap
     */
    protected array $optionsMap = [
        'title' => 'site_name',
        'super_admins' => 'site_admins',
        'notify_registration' => 'registrationnotification',
        'site_unlimited_upload' => 'upload_space_check_disabled',
        'site_upload_space' => 'blog_upload_space',
        'site_max_upload' => 'fileupload_maxk',
        'enabled_themes' => 'allowedthemes',
        'active_plugins' => 'active_sitewide_plugins',
    ];

    /**
     * Impose some data onto the region
     * How the data is interpreted depends
     * on the region
     *
     * @param string  $_ Unused.
     * @param array $options Options to impose.
     */
    public function impose(string $_, $options): void
    {
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->optionsMap)) {
                $key = $this->optionsMap[ $key ];
            }

            switch ($key) {
                case 'allowedthemes':
                    $allowedThemes = [];
                    foreach ($value as $theme) {
                        $allowedThemes[ $theme ] = true;
                    }
                    update_site_option('allowedthemes', $allowedThemes);
                    break;

                case 'active_sitewide_plugins':
                    foreach ($value as $plugin) {
                        activate_plugin($plugin, '', true);
                    }
                    break;

                case 'registrationnotification':
                    if ($value) {
                        update_site_option($key, 'yes');
                    } else {
                        update_site_option($key, 'no');
                    }
                    break;

                case 'upload_space_check_disabled':
                case 'blog_upload_space':
                case 'fileupload_maxk':
                    update_site_option($key, (int)$value);
                    break;

                default:
                    update_site_option($key, $value);
                    break;
            }
        }
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

        // Data transformation if we need to.
        switch ($name) {
            case 'allowedthemes':
            case 'active_sitewide_plugins':
                // Coerce to array of names.
                return array_keys(get_site_option($name, []));

            case 'registrationnotification':
                // Coerce to boolean.
                return 'yes' === get_site_option($name);
            default:
                return get_site_option($name);
        }
    }
}
