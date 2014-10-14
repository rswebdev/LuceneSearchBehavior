<?php
namespace Propel\Generator\Behavior;

use Propel\Generator\Behavior\LuceneSearchBehavior\LuceneSearchBehaviorObjectBuilderModifier;
use Propel\Generator\Behavior\LuceneSearchBehavior\LuceneSearchBehaviorQueryBuilderModifier;
use Propel\Generator\Model\Behavior;

class LuceneSearchBehavior extends Behavior {

    protected $objectBuilderModifier;
    protected $queryBuilderModifier;

    protected $parameters = array(
        'columns'      => '',
    );

    public function getSearchColumns()
    {
        $columns = array();
        $table = $this->getTable();
        if ($columnNames = $this->getSearchColumnNamesFromConfig()) {
            foreach ($columnNames as $columnName) {
                $columns []= $table->getColumn($columnName);
            }
        }
        return $columns;
    }

    protected function getSearchColumnNamesFromConfig()
    {
        $columnNames = explode(',', $this->getParameter('columns'));
        foreach ($columnNames as $key => $columnName) {
            if ($columnName = trim($columnName)) {
                $columnNames[$key] = $columnName;
            } else {
                unset($columnNames[$key]);
            }
        }
        return $columnNames;
    }

    public function getObjectBuilderModifier()
    {
        if (is_null($this->objectBuilderModifier)) {
            $this->objectBuilderModifier = new LuceneSearchBehaviorObjectBuilderModifier($this);
        }
        return $this->objectBuilderModifier;
    }

    public function getQueryBuilderModifier()
    {
        if (is_null($this->queryBuilderModifier)) {
            $this->queryBuilderModifier = new LuceneSearchBehaviorQueryBuilderModifier($this);
        }
        return $this->queryBuilderModifier;
    }
}
