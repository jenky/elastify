<?php

namespace Jenky\Elastify\Builder;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Suggest\Suggest;

class Suggestion extends AbstractBuilder
{
    /**
     * Add a completion suggestion.
     *
     * @param  string $name
     * @param  string $text
     * @param  string $field
     * @param  array $parameters
     * @return $this
     */
    public function completion($name, $text, $field = 'suggest', array $parameters = [])
    {
        return $this->append(
            new Suggest($name, 'completion', $text, $field, $parameters)
        );
    }

    /**
     * Add a term suggestion.
     *
     * @param  string $name
     * @param  string $text
     * @param  string $field
     * @param  array $parameters
     * @return $this
     */
    public function term($name, $text, $field = '_all', array $parameters = [])
    {
        return $this->append(
            new Suggest($name, 'term', $text, $field, $parameters)
        );
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
