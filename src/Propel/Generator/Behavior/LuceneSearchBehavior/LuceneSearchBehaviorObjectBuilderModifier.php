<?php
namespace RSWebDev\Propel\Generator\Behavior\LuceneSearchBehavior;

use Propel\Generator\Util\PhpParser;
use RSWebDev\Propel\Generator\Behavior\LuceneSearchBehavior;
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
        $this->objectClassName = trim($builder->getObjectClassName(true),'\\');
        $this->queryClassName  = $builder->getQueryClassName(true);
    }

    public function objectFilter(&$script) {
        if ($this->behavior->i18n) {
            foreach ($this->columns as $column) {
                $this->replaceGetColumn($column, $script);
            }
        }
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

foreach (\$index->find(self::TABLE_MAP.':'.\$this->getId()) as \$hit) {
    \$index->delete(\$hit->id);
    \$index->optimize();
}
";
    }

    public function addUpdateLuceneIndex(&$script) {
        $script .= "

/**
 * Updates the Lucene index with current values
 *
 * @return \$this The current object (for fluent API support)
 */
public function updateLuceneIndex() {

    \$index = {$this->queryClassName}::getLuceneIndex();

    // remove existing entries
    foreach (\$index->find(self::TABLE_MAP . ':' . \$this->getId()) as \$hit) {
        \$index->delete(\$hit->id);
        \$index->commit();
    }

    // don't index expired and non-activated jobs
    //if (\$this->getIsDeleted()) {
    //    return \$this;
    //}

    \$doc = new \\ZendSearch\\Lucene\\Document();

    // store job primary key to identify it in the search results
    \$doc->addField(\\ZendSearch\\Lucene\\Document\\Field::keyword(self::TABLE_MAP, \$this->getId()));
    \$doc->addField(\\ZendSearch\\Lucene\\Document\\Field::text('elementid', '{$this->objectClassName}-' . \$this->getId()));

    // index job fields
";

        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                if ($column->getPhpNative() == "string") {
                    if (!$this->behavior->i18n) {
                        $script .= "
    \$doc->addField(\\ZendSearch\\Lucene\\Document\\Field::text('{$column->getName()}', \$this->get{$column->getPhpName()}(), 'utf-8'));
";
                    } else {
                        $script .= "
    \$doc->addField(\\ZendSearch\\Lucene\\Document\\Field::text('{$column->getName()}', \$this->get{$column->getPhpName()}(true), 'utf-8'));
";
                    }
                }
            }
        }

        $script .= "
    // add job to the index
    \$index->addDocument(\$doc);
    \$index->commit();

    return \$this;
}
";
    }

    public function replaceGetColumn(Column $column, &$script) {
        $newGetColumnMethod = "

/**
 * Get the [%s] column value.
 *
 * @param bool \$allTranslations
 * @return string
 */
public function get%s(\$allTranslations = false) {
    if (!\$allTranslations) {
        return \$this->getCurrentTranslation()->get%s();
    } else {
        \$return = '';
        foreach (\$this->get{$this->objectClassName}I18ns() as \$record_i18n) {
            if (\$record_i18n instanceof {$this->objectClassName}I18n) {
                \$return .= \$record_i18n->get%s() . \"\\n\\n\";
            }
        }
        return \$return;
    }
}
";
        $newGetColumnMethod = sprintf(
            $newGetColumnMethod,
            $column->getName(),
            $column->getPhpName(),
            $column->getPhpName(),
            $column->getPhpName()
        );

        $parser = new PhpParser($script, true);
        $parser->replaceMethod(sprintf('get%s', $column->getPhpName()), $newGetColumnMethod);
        $script = $parser->getCode();
    }
}
