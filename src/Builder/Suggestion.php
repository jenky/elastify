<?php

namespace Jenky\Elastify\Builder;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Suggest\Suggest;

class Suggestion extends AbstractBuilder
{
    /**
     * Add a completion suggestion.
     *
     * @param  $name
     * @param  $text
     * @param  $field
     * @param  array $parameters
     * @return $this
     */
    public function completion($name, $text, $field = 'suggest', array $parameters = [])
    {
        $suggestion = new Suggest($name, 'completion', $text, $field, $parameters);

        return $this->append($suggestion);
    }

    /**
     * Add a term suggestion.
     *
     * @param  string $name
     * @param  string $text
     * @param  $field
     * @param  array $parameters
     * @return $this
     */
    public function term($name, $text, $field = '_all', array $parameters = [])
    {
        $suggestion = new Suggest($name, 'term', $text, $field, $parameters);

        return $this->append($suggestion);
    }

    /**
     * Append a suggestion to query.
     *
     * @param  \ONGR\ElasticsearchDSL\BuilderInterface $suggestion
     * @return $this
     */
    public function append(BuilderInterface $suggestion)
    {
        $this->query->addSuggest($suggestion);

        return $this;
    }
}
