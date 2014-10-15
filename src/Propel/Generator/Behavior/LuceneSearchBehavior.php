<?php
namespace RSWebDev\Propel\Generator\Behavior;

use Propel\Generator\Behavior\I18n\I18nBehavior;
use RSWebDev\Propel\Generator\Behavior\LuceneSearchBehavior\LuceneSearchBehaviorObjectBuilderModifier;
use RSWebDev\Propel\Generator\Behavior\LuceneSearchBehavior\LuceneSearchBehaviorQueryBuilderModifier;
use Propel\Generator\Model\Behavior;

class LuceneSearchBehavior extends Behavior {

    protected $objectBuilderModifier;
    protected $queryBuilderModifier;
    public $i18n;

    protected $parameters = array(
        'columns'      => '',
    );

    public function replaceTokens($string)
    {
        $table = $this->getTable();

        return strtr($string, array(
            '%TABLE%'   => $table->getOriginCommonName(),
            '%PHPNAME%' => $table->getPhpName(),
        ));
    }

    public function getSearchColumns()
    {
        $columns = array();
        $table = $this->getTable();
        if ($columnNames = $this->getSearchColumnNamesFromConfig()) {
            foreach ($columnNames as $columnName) {
                $_column = $table->getColumn($columnName);
                if ($_column != null)
                    $columns []= $_column;
            }
        }
        $this->i18n = false;
        if (empty($columns)) {
            $other_behaviors = $table->getBehaviors();
            foreach ($other_behaviors as $other_behavior) {
                if ($other_behavior instanceof I18nBehavior) {
                    $this->i18n = true;
                    $database = $this->getTable()->getDatabase();
                    $i18n_table = $database->getTable($this->replaceTokens($other_behavior->getParameter('i18n_table')));
                    foreach ($columnNames as $columnName) {
                        $_column = $i18n_table->getColumn($columnName);
                        if ($_column != null)
                            $columns []= $_column;
                    }
                }
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
