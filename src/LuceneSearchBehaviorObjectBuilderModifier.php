<?php
namespace Propel\Generator\Behavior\LuceneSearchBehavior;

use Propel\Generator\Behavior\LuceneSearchBehavior;
use Propel\Generator\Model\Column;

class LuceneSearchBehaviorObjectBuilderModifier {

    protected $behavior;
    protected $builder;
    protected $objectClassName;
    protected $queryClassName;
    protected $columns;

    public function __construct(LuceneSearchBehavior $behavior)
    {
        $this->behavior = $behavior;
        $this->columns = $behavior->getSearchColumns();
    }

    protected function setBuilder($builder)
    {
        $this->builder         = $builder;
        $this->objectClassName = $builder->getObjectClassName(true);
        $this->queryClassName  = $builder->getQueryClassName(true);
    }

    public function objectMethods($builder)
    {
        $script = "";

        $this->setBuilder($builder);

        $this->addUpdateLuceneIndex($script);

        return $script;
    }
    public function postSave($builder)
    {
        $this->setBuilder($builder);

        return "
    \$this->updateLuceneIndex();

    \$index = {$this->queryClassName}::getLuceneIndex();
    \$index->optimize();
    ";
    }

    public function postDelete($builder)
    {
        $this->setBuilder($builder);

        return "
    \$index = {$this->queryClassName}::getLuceneIndex();

    foreach (\$index->find(self::TABLE_MAP.':'.\$this->getId()) as \$hit)
    {
        \$index->delete(\$hit->id);
        \$index->optimize();
    }
    ";
    }

    public function addUpdateLuceneIndex(&$script) {
        $script .= "
public function updateLuceneIndex() {

    \$index = {$this->queryClassName}::getLuceneIndex();

    // remove existing entries
    foreach (\$index->find(self::TABLE_MAP . ':' . \$this->getId()) as \$hit) {
        \$index->delete(\$hit->id);
        \$index->commit();
    }

    // don't index expired and non-activated jobs
    if (\$this->getIsDeleted()) {
        return \$this;
    }

    \$doc = new \\ZendSearch\\Lucene\\Document();

    // store job primary key to identify it in the search results
    \$doc->addField(\\ZendSearch\\Lucene\\Document\\Field::keyword(self::TABLE_MAP, \$this->getId()));
    \$doc->addField(\\ZendSearch\\Lucene\\Document\\Field::text('elementid', '{$this->objectClassName}-' . \$this->getId()));

    // index job fields
    ";

        foreach ($this->columns as $column) {
            if ($column instanceof Column)
                if ($column->getPhpNative() == "string")
                    $script .= "
    \$doc->addField(\\ZendSearch\\Lucene\\Document\\Field::text('{$column->getName()}', \$this->get{$column->getPhpName()}(), 'utf-8'));
    ";
        }

        $script .= "
    // add job to the index
    \$index->addDocument(\$doc);
    \$index->commit();

    return \$this;
}
";
    }
}
