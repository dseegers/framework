<?php

namespace OffbeatWP\Content\Taxonomy;

class TaxonomyBuilder
{
    private $taxonomy = null;
    private $postTypes = null;
    private $args = [];
    private $modelClass = null;

    public function make($taxonomy, $postTypes, $pluralName, $singularLabel): TaxonomyBuilder
    {
        $this->taxonomy = $taxonomy;
        $this->postTypes = $postTypes;
        $this->args = [
            'labels' => [
                'name' => $pluralName,
                'singular_name' => $singularLabel,
            ],
        ];

        return $this;
    }

    public function rewrite($rewrite): TaxonomyBuilder
    {
        if (!isset($this->args['rewrite'])) {
            $this->args['rewrite'] = [];
        }

        if ($rewrite === false) {
            $this->args['rewrite'] = false;
        } elseif (is_array($rewrite)) {
            array_push($this->args['rewrite'], $rewrite);
        }

        return $this;
    }

    public function labels($labels): TaxonomyBuilder
    {
        if (!isset($this->args['labels'])) {
            $this->args['labels'] = [];
        }

        $this->args['labels'] = array_merge($this->args['labels'], $labels);

        return $this;
    }

    public function hierarchical($hierarchical = false): TaxonomyBuilder
    {
        $this->args['hierarchical'] = $hierarchical;

        return $this;
    }

    public function model($modelClass): TaxonomyBuilder
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function notPubliclyQueryable(): TaxonomyBuilder
    {
        $this->args['publicly_queryable'] = false;

        return $this;
    }

    public function public($public = true): TaxonomyBuilder
    {
        $this->args['public'] = $public;

        return $this;
    }

    public function showUI($showUi = true): TaxonomyBuilder
    {
        $this->args['show_ui'] = $showUi;

        return $this;
    }

    public function inMenu($menu): TaxonomyBuilder
    {
        $this->args['show_in_menu'] = $menu;

        return $this;
    }

    public function inRest($rest = true): TaxonomyBuilder
    {
        $this->args['show_in_rest'] = $rest;

        return $this;
    }

    public function showAdminColumn($showAdminColumn = true): TaxonomyBuilder
    {
        $this->args['show_admin_column'] = $showAdminColumn;

        return $this;
    }

    public function metaBox($metaBoxCallback): TaxonomyBuilder
    {
        $this->args['meta_box_cb'] = $metaBoxCallback;

        return $this;
    }

    public function set()
    {
        register_taxonomy($this->taxonomy, $this->postTypes, $this->args);

        if (!is_null($this->modelClass)) {
            offbeat('taxonomy')->registerTermModel($this->taxonomy, $this->modelClass);
        }

    }
}
