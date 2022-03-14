<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Region;

abstract class Users extends Region
{
    private const GENERATED_PASSWORD_LENGTH = 24;

    /**
     * Users schema.
     *
     * @var array
     */
    protected array $schema = [
        '_type' => 'prototype',
        '_get_callback' => 'getUsers',
        '_prototype' => [
            '_type' => 'array',
            '_children' => [
                'display_name' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getUserValue',
                ],
                'first_name' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getUserValue',
                ],
                'last_name' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getUserValue',
                ],
                'email' => [
                    '_type' => 'email',
                    '_required' => false,
                    '_get_callback' => 'getUserValue',
                ],
                'role' => [
                    '_type' => 'text',
                    '_required' => false,
                    '_get_callback' => 'getUserValue',
                ],
            ],
        ],
    ];

    /**
     * Object-level cache for user data
     *
     * @var $users
     */
    protected $users;

    /**
     * Get the difference between the state file and WordPress
     *
     * @return array
     */
    public function getDifferences(): array
    {
        $this->differences = [];
        // Check each declared user in state data against WordPress.
        foreach ($this->getImposedData() as $userLogin => $userData) {
            $result = $this->getUserDifference($userLogin, $userData);

            if (! empty($result)) {
                $this->differences[ $userLogin ] = $result;
            }
        }

        return $this->differences;
    }

    /**
     * Get the users on the network on the site
     *
     * @return array
     */
    protected function getUsers(): array
    {
        $args = [];

        if ('network' === $this->getContext()) {
            $args['blog_id'] = 0; // all users.
        } else {
            $args['blog_id'] = get_current_blog_id();
        }

        $this->users = get_users($args);
        return wp_list_pluck($this->users, 'user_login');
    }

    /**
     * Get the value from a user object
     *
     * @param string $key Key to retrieve data for.
     * @return mixed
     */
    protected function getUserValue(string $key)
    {
        $userLogin = $this->currentSchemaAttributeParents[0];
        foreach ($this->users as $user) {
            if ($user->user_login === $userLogin) {
                break;
            }
        }

        switch ($key) {

            case 'email':
                $value = $user->user_email;
                break;

            case 'role':
                if ('site' === $this->getContext()) {
                    $value = array_shift($user->roles);
                } else {
                    $value = '';
                }
                break;

            default:
                $value = $user->$key;
                break;
        }

        return $value;
    }

    /**
     * Impose some state data onto a region
     *
     * @param string $key User login.
     * @param array $value User's data.
     *
     * @throws CouldNotImposeRegionException If the region could not be imposed.
     */
    public function impose(string $key, $value): void
    {

        // We'll need to create the user if they don't exist.
        $user = get_user_by('login', $key);
        if (! $user) {
            $userObj = [
                'user_login' => $key,
                'user_email' => $value['email'], // 'email' is required.
                'user_pass' => wp_generate_password(self::GENERATED_PASSWORD_LENGTH),
            ];
            $userId = wp_insert_user($userObj);
            if (is_wp_error($userId)) {
                throw new CouldNotImposeRegionException($userId->get_error_message());
            }

            // Network users should default to no roles / capabilities.
            if ('network' === $this->getContext()) {
                delete_user_option($userId, 'capabilities');
                delete_user_option($userId, 'user_level');
            }

            $user = get_user_by('id', $userId);
        }

        // Update any values needing to be updated.
        foreach ($value as $ymlField => $singleValue) {

            // Users have no role in the network context.
            // @todo needs a better abstraction.
            if ('role' === $ymlField && 'network' === $this->getContext()) {
                continue;
            }

            switch ($ymlField) {
                case 'email':
                    $modelField = 'user_email';
                    break;

                default:
                    $modelField = $ymlField;
                    break;
            }

            if ($user->$modelField !== $singleValue) {
                wp_update_user(
                    [
                        'ID' => $user->ID,
                        $modelField => $singleValue,
                    ]
                );
            }
        }
    }

    /**
     * Get the difference between the declared user and the actual user
     *
     * @param string $userLogin User login.
     * @param array  $userData User's data.
     * @return array
     */
    protected function getUserDifference(string $userLogin, array $userData): array
    {
        $result = [
            'dictated' => $userData,
            'current' => [],
        ];

        $users = $this->getCurrentData();
        if (! isset($users[ $userLogin ])) {
            return $result;
        }

        $result['current'] = $users[ $userLogin ];

        if (array_diff_assoc($result['dictated'], $result['current'])) {
            return $result;
        }

        return [];
    }

    /**
     * Get the context in which this class was called
     *
     * @return string
     */
    protected function getContext(): string
    {
        $className = get_class($this);
        if (NetworkUsers::class === $className) {
            return 'network';
        }

        if (SiteUsers::class === $className) {
            return 'site';
        }

        return '';
    }
}
