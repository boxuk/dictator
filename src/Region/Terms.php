<?php

declare(strict_types=1);

namespace BoxUk\Dictator\Region;

use BoxUk\Dictator\Utils;

class Terms extends Region
{
    /**
     * Schema config.
     *
     * @var array $schema
     */
    protected array $schema = [
        '_type' => 'prototype',
        '_get_callback' => 'getTaxonomies',
        '_prototype' => [
            '_type' => 'prototype',
            '_get_callback' => 'getTerms',
            '_prototype' => [
                '_type' => 'array',
                '_children' => [
                    'name' => [
                        '_type' => 'text',
                        '_required' => false,
                        '_get_callback' => 'getTermValue',
                        '_update_callback' => '',
                    ],
                    'description' => [
                        '_type' => 'text',
                        '_required' => false,
                        '_get_callback' => 'getTermValue',
                        '_update_callback' => '',
                    ],
                    'parent' => [
                        '_type' => 'text',
                        '_required' => false,
                        '_get_callback' => 'getTermValue',
                        '_update_callback' => '',
                    ],
                ],
            ],
        ],
    ];

    /**
     * Object-level cache of the term data
     *
     * @var array $terms
     */
    protected array $terms = [];

    /**
     * Impose some data onto the region
     * How the data is interpreted depends
     * on the region
     *
     * @param string $key Key of the data to impose.
     * @param mixed $value Value to impose.
     *
     * @throws CouldNotImposeRegionException If the region could not be imposed.
     */
    public function impose(string $key, $value): void
    {
        if (! taxonomy_exists($key)) {
            throw new CouldNotImposeRegionException(sprintf('Invalid taxonomy "%s"', $key));
        }

        foreach ($value as $slug => $termValues) {
            $term = get_term_by('slug', $slug, $key);
            if (! $term) {
                $ret = wp_insert_term($slug, $key);
                if (is_wp_error($ret)) {
                    throw new CouldNotImposeRegionException($ret->get_error_message());
                }
                $term = get_term_by('id', $ret['term_id'], $key);
            }

            foreach ($termValues as $ymlField => $termValue) {
                switch ($ymlField) {
                    case 'name':
                    case 'description':
                        if ($termValue === $term->$ymlField) {
                            break;
                        }

                        wp_update_term($term->term_id, $key, [ $ymlField => $termValue ]);

                        break;

                    case 'parent':
                        if ($termValue) {
                            $parent_term = get_term_by('slug', $termValue, $key);
                            if (! $parent_term) {
                                throw new CouldNotImposeRegionException(sprintf('Parent is invalid for term "%s"', $slug));
                            }

                            if ($parent_term->term_id === $term->parent) {
                                break;
                            }

                            wp_update_term($term->term_id, $key, [ 'parent' => $parent_term->term_id ]);
                        } elseif ($term->parent) {
                            wp_update_term($term->term_id, $key, [ 'parent' => 0 ]);
                        }

                        break;
                }
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
        $this->differences = [];
        // Check each declared term in state data against WordPress.
        foreach ($this->getImposedData() as $taxonomy => $taxonomyData) {
            $result = $this->getTaxonomyDifference($taxonomy, $taxonomyData);

            if (! empty($result)) {
                $this->differences[ $taxonomy ] = $result;
            }
        }

        return $this->differences;
    }

    /**
     * Get the taxonomies on this site
     *
     * @return array
     */
    public function getTaxonomies(): array
    {
        return get_taxonomies(['public' => true]);
    }

    /**
     * Get the terms associated with a taxonomy on the site
     *
     * @param string $taxonomy Taxonomy to get terms for.
     *
     * @return array
     */
    public function getTerms(string $taxonomy): array
    {
        $terms = get_terms([$taxonomy], ['hide_empty' => 0]);
        if (is_wp_error($terms)) {
            $terms = [];
        }

        $this->terms[ $taxonomy ] = $terms;

        return wp_list_pluck($terms, 'slug');
    }

    /**
     * Get the value associated with a given term
     *
     * @param string $key Key to get term value for.
     * @return string
     */
    public function getTermValue(string $key): string
    {
        [$taxonomy, $termSlug] = $this->currentSchemaAttributeParents;

        foreach ($this->terms[ $taxonomy ] as $term) {
            if ($term->slug === $termSlug) {
                break;
            }
        }

        switch ($key) {
            case 'parent':
                $parent = false;
                foreach ($this->terms[ $taxonomy ] as $maybeParentTerm) {
                    if ($maybeParentTerm->term_id === $term->parent) {
                        $parent = $maybeParentTerm;
                    }
                }
                $value = $parent->slug ?? '';
                break;

            default:
                $value = $term->$key;
                break;

        }

        return $value;
    }

    /**
     * Get the difference between the declared taxonomy state and
     * the actual taxonomy state
     *
     * @param string $taxonomy Taxonomy to get difference for.
     * @param array  $taxonomyData Taxonomy data.
     * @return array
     */
    protected function getTaxonomyDifference(string $taxonomy, array $taxonomyData): array
    {
        $result = [
            'dictated' => $taxonomyData,
            'current' => [],
        ];

        $currentData = $this->getCurrentData();
        if (! isset($currentData[ $taxonomy ])) {
            return $result;
        }

        $result['current'] = $currentData[ $taxonomy ];

        if (Utils::arrayDiffRecursive($result['dictated'], $result['current'])) {
            return $result;
        }

        return [];
    }
}
