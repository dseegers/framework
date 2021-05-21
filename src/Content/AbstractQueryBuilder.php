<?php

namespace OffbeatWP\Content;

abstract class AbstractQueryBuilder {
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    protected $queryVars = [];

    /**
     * @param string|string[] $orderBy
     * @param string|null $order 'ASC'|'DESC'
     * @return $this
     */
    protected function order($orderBy = null, $order = null): AbstractQueryBuilder {
        if (preg_match('/^(meta(_num)?):(.+)$/', $orderBy, $match)) {
            $this->queryVars['meta_key'] = $match[3];
            $this->queryVars['orderby'] = 'meta_value';

            if (isset($match[1]) && $match[1] == 'meta_num') {
                $this->queryVars['orderby'] = 'meta_value_num';
            }

        } elseif (!is_null($orderBy)) {
            $this->queryVars['orderby'] = $orderBy;
        }

        if (!is_null($order)) {
            $this->queryVars['order'] = $order;
        }

        return $this;
    }

    public function where(?array $parameters): AbstractQueryBuilder
    {
        $this->queryVars = array_merge($this->queryVars, $parameters);

        return $this;
    }
}