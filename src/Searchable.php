<?php

namespace ScoutElastic;

use Exception;
use Illuminate\Support\Arr;
use ScoutElastic\Builders\FilterBuilder;
use ScoutElastic\Builders\SearchBuilder;
use Laravel\Scout\Searchable as SourceSearchable;

trait Searchable
{
    use SourceSearchable {
        SourceSearchable::bootSearchable as sourceBootSearchable;
        SourceSearchable::getScoutKeyName as sourceGetScoutKeyName;
    }

    /**
     * The highligths.
     *
     * @var \ScoutElastic\Highlight|null
     */
    private $highlight = null;

    /**
     * Defines if the model is searchable.
     *
     * @var bool
     */
    protected static $isSearchableTraitBooted = false;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootSearchable()
    {
        if (static::$isSearchableTraitBooted) {
            return;
        }

        self::sourceBootSearchable();

        static::$isSearchableTraitBooted = true;
    }

    /**
     * Get the index configurator.
     *
     * @return \ScoutElastic\IndexConfigurator
     * @throws \Exception
     */
    public function getIndexConfigurator()
    {
        static $indexConfigurator;

        if (!$indexConfigurator) {
            if (!isset($this->indexConfigurator) || empty($this->indexConfigurator)) {
                throw new Exception(sprintf(
                    'An index configurator for the %s model is not specified.',
                    __CLASS__
                ));
            }

            $indexConfiguratorClass = $this->indexConfigurator;
            $indexConfigurator = new $indexConfiguratorClass;
        }

        return $indexConfigurator;
    }

    /**
     * Get the mapping.
     *
     * @return array
     */
    public function getMapping()
    {
        $mapping = $this->mapping ?? [];

        if ($this::usesSoftDelete() && config('scout.soft_delete', false)) {
            Arr::set($mapping, 'properties.__soft_deleted', ['type' => 'integer']);
        }

        return $mapping;
    }

    /**
     * Get the search rules.
     *
     * @return array
     */
    public function getSearchRules()
    {
        return isset($this->searchRules) && count($this->searchRules) > 0 ?
            $this->searchRules : [SearchRule::class];
    }

    /**
     * @return array
     */
    public function getAggregateRules()
    {
        return isset($this->aggregateRules) && count($this->aggregateRules) > 0 ? $this->aggregateRules : [AggregateRule::class];
    }

    /**
     * Execute the search.
     *
     * @param string $query
     * @param callable|null $callback
     * @return \ScoutElastic\Builders\FilterBuilder|\ScoutElastic\Builders\SearchBuilder
     */
    public static function search($query, $callback = null)
    {
        $softDelete = static::usesSoftDelete() && config('scout.soft_delete', false);

        if ($query == '*') {
            return new FilterBuilder(new static, $callback, $softDelete);
        } else {
            return new SearchBuilder(new static, $query, $callback, $softDelete);
        }
    }

    /**
     * Execute a raw search.
     *
     * @param array $query
     * @return array
     */
    public static function searchRaw(array $query)
    {
        $model = new static();

        return $model->searchableUsing()
            ->searchRaw($model, $query);
    }

    /**
     * Execute a raw search with scroll.
     *
     * @param $query
     * @param $size
     * @param $scroll
     * @return array
     */
    public static function searchRawWithScroll(array $query, int $size = 10000, string $scroll = '1m')
    {
        $model = new static();

        return $model->searchableUsing()
            ->searchRawWithScroll($model, $query, $size, $scroll);
    }

    /**
     * Execute a scroll search.
     *
     * @param $scroll_id
     * @param $scroll
     * @return array
     */
    public static function scroll(string $scroll_id, string $scroll = '1m')
    {
        $model = new static();

        return $model->searchableUsing()
            ->scroll($scroll_id, $scroll);
    }

    /**
     * Set the highlight attribute.
     *
     * @param \ScoutElastic\Highlight $value
     * @return void
     */
    public function setHighlightAttribute(Highlight $value)
    {
        $this->highlight = $value;
    }

    /**
     * Get the highlight attribute.
     *
     * @return \ScoutElastic\Highlight|null
     */
    public function getHighlightAttribute()
    {
        return $this->highlight;
    }

    /**
     * Get the key name used to index the model.
     *
     * @return mixed
     */
    public function getScoutKeyName()
    {
        return $this->getKeyName();
    }
}
