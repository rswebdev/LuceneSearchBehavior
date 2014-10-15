<?php
namespace RSWebDev\Propel\Generator\Behavior\LuceneSearchBehavior;

use RSWebDev\Propel\Generator\Behavior\LuceneSearchBehavior;

class LuceneSearchBehaviorQueryBuilderModifier {

    protected $behavior;
    protected $builder;
    protected $tableClassName;
    protected $objectClassName;
    protected $queryClassName;

    public function __construct(LuceneSearchBehavior $behavior)
    {
        $this->behavior = $behavior;
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassName = trim($builder->getObjectClassName(true),'\\');
        $this->queryClassName = $builder->getQueryClassName(true);
    }

    public function queryMethods($builder)
    {
        $builder->declareClasses('\Propel\Runtime\Propel');

        $this->setBuilder($builder);

        $script = "";

        $this->addGetLuceneIndex($script);
        $this->addGetLuceneIndexFile($script);
        $this->addFindByLuceneSearch($script);
        $this->addFilterByLuceneSearch($script);

        return $script;
    }

    public function addGetLuceneIndex(&$script)
    {
        $script .= "

    /**
     * Opens or creates a Lucene SearchIndexInterface and returns it
     *
     * @return \\ZendSearch\\Lucene\\SearchIndexInterface
     */
    static public function getLuceneIndex()
    {
        if (file_exists(\$index = self::getLuceneIndexFile())) {
            \$lucy = \\ZendSearch\\Lucene\\Lucene::open(\$index);
            return \$lucy;
        }

        return \\ZendSearch\\Lucene\\Lucene::create(\$index);
    }";
    }

    public function addGetLuceneIndexFile(&$script) {
        $script .= "

    /**
     * Returns the relative path to the Lucene index file for {$this->objectClassName}
     *
     * @return String The relative path the Lucene index file
     */
    static public function getLuceneIndexFile()
    {
        return 'index/{$this->objectClassName}.index';
    }
";
    }

    public function addFindByLuceneSearch(&$script) {
        $script .= "

    /**
     * Return {$this->objectClassName} objects based on Lucene Search
     *
     * @param string \$query The query to find objects in Lucene index
     * @param null \$index The used index for further queries
     * @param null \$query_parser The used QueryParser for further usage
     * @param null \$hits The retrieved QueryHits for further usage
     *
     * @return {$this->objectClassName}[]|ObjectCollection
     *
     */
    public function findByLuceneSearch(\$query, &\$index = null, &\$query_parser = null, &\$hits = null) {

        \$query_parser = \\ZendSearch\\Lucene\\Search\\QueryParser::parse(\$query);

        \$index = new \\ZendSearch\\Lucene\\MultiSearcher();

        \$index->addIndex(self::getLuceneIndex());

        \$hits = \$index->find(\$query_parser);

        \$activeQueryIds = array();

        foreach (\$hits as \$hit) {
            if (\$hit instanceof \\ZendSearch\\Lucene\\Search\\QueryHit) {
                \$_elem_id = \$hit->__isset('elementid') ? \$hit->__get('elementid') : null;
                if (null !== \$_elem_id) {
                    array_push(\$activeQueryIds, (int) substr(\$_elem_id, strlen('{$this->objectClassName}-')));
                }
            }
        }

        return \$this->filterById(\$activeQueryIds)->find();
    }
";
    }

    public function addFilterByLuceneSearch(&$script) {
        $script .= "

    /***
     * Filter the query by Ids returned from Lucene Search
     *
     * @param string \$query The query to find objects in Lucene index
     * @param null \$index The used index for further queries
     * @param null \$query_parser The used QueryParser for further usage
     * @param null \$hits The retrieved QueryHits for further usage
     *
     * @return {$this->objectClassName}Query The current query, for fluid interface
     *
     */
    public function filterByLuceneSearch(\$query, &\$index = null, &\$query_parser = null, &\$hits = null) {

        \$query_parser = \\ZendSearch\\Lucene\\Search\\QueryParser::parse(\$query);

        \$index = new \\ZendSearch\\Lucene\\MultiSearcher();

        \$index->addIndex(self::getLuceneIndex());

        \$hits = \$index->find(\$query_parser);

        \$activeQueryIds = array();

        foreach (\$hits as \$hit) {
            if (\$hit instanceof \\ZendSearch\\Lucene\\Search\\QueryHit) {
                \$_elem_id = \$hit->__isset('elementid') ? \$hit->__get('elementid') : null;
                if (null !== \$_elem_id) {
                    array_push(\$activeQueryIds, (int) substr(\$_elem_id, strlen('{$this->objectClassName}-')));
                }
            }
        }

        return \$this->filterById(\$activeQueryIds);
    }
";
    }
}
