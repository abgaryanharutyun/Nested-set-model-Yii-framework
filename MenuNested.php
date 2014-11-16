<?php

/**
 * Class MenuNested Component for yii framework
 * @author Harutyun Abgaryan harutyunabgaryan@gmail.com
 *
 * @category TThe nested set model is a particular technique for representing nested sets (also known as trees or hierarchies) in relational databases. The term was apparently introduced by Joe Celko; others describe the same technique without naming it [1] or using different terms http://en.wikipedia.org/wiki/Nested_set_model
 * Connect in your yii framework
 *
 * @deprecated 0.1v
 *
 * @property $tableName string public for you main Table Name by default name menu
 * @property $leftField string public for tableName left field name default name lft
 * @property $rightField string public for tableName right field name default name rgt
 * @property $autoIncrement your table primary  field Name
 * @property $modelClassName for active record
 */
class MenuNested
{


    /**
     * your table Name
     * @var string
     */
    public $tableName = 'menu';


    /**
     * your left filed name
     * @var string
     */

    public $leftField = 'lft';


    /**
     * your right field name
     * @var string
     */
    public $rightField = 'rgt';


    /**
     * title of menu
     * @var string
     */
    public $titleName = 'name';


    /**
     * your table primary key field
     * @var string
     */

    public $autoIncrement = "id";


    /**
     * @var str model class name for active record
     */
    public $modelClassName = 'Menu';

    /**
     * Method insertMenu for insert new menu
     * @param null $parentId (optional ) if parentId == null if record parent record and left = 1 right =2
     * @param array $data (optional) your inserted data
     * @param bool $child (optional) if child true then your added menu child menu
     */
    public function insertMenu($parentId = null, array $data = array(), $child = false, $obj = false)
    {
        $tableName = $this->tableName;
        $modelClassName = $this->modelClassName;
        $lft = $this->leftField;
        $rgt = $this->rightField;
        $parentMenu = $this->currentMenu($parentId);

        if ($parentMenu == null || $parentId == null) {
            $parentRight = 0;
        } else {
            if ($child) {
                $parentRight = $parentMenu->$lft;
            } else {
                $parentRight = $parentMenu->$rgt;
            }
        }
        $sqlRgt = "UPDATE $tableName set $rgt =$rgt+2 WHERE $rgt>$parentRight";
        $sqlLft = "UPDATE $tableName set $lft =$lft+2 WHERE $lft>$parentRight";
        $commandRgt = $this->createCommand($sqlRgt);
        $commandRgt->execute(); // execute the non-query SQL
        $commandLft = $this->createCommand($sqlLft);
        $commandLft->execute();
        $model = new $modelClassName;
        $model->attributes = $data;
        $model->$lft = $parentRight + 1;
        $model->$rgt = $parentRight + 2;

        if ($model->save()) {
            if ($obj) {
                return $model;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method createTree for return your menu tree view
     * @param $arrData you can set your data for write tree view
     * @return array
     */

    public function createTree()
    {
        $rgt = $this->rightField;
        $lft = $this->leftField;
        $tableName = $this->tableName;
        $title = $this->titleName;
        $autoincrement = $this->autoIncrement;
        $sql = "SELECT node.$autoincrement, CONCAT(REPEAT('-----', COUNT(parent.$title)-1), node.$title) as name
                FROM $tableName as node, $tableName as parent WHERE  node.$lft BETWEEN parent.$lft and parent.$rgt GROUP BY node.$title ORDER BY node.$lft";
        $tree = $this->createCommand($sql);
        $tree->execute();
        $treeResult = $tree->queryAll();
        return $treeResult;
    }

    /**
     * method getAllLeafNodes get ala leaf rows
     * @param array $data if set data not selected in your datebase
     * @return array
     */
    public function getAllLeafNodes($data = array())
    {
        $rgt = $this->rightField;
        $lft = $this->leftField;
        $tableName = $this->tableName;
        $result = array();
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $key => $value) {
                $leftVal = $value[$lft] + 1;
                if ($leftVal == $value[$rgt]) {
                    $result[] = $value;
                }
            }

        } else {
            $sql = "SELECT * from $tableName WHERE $rgt=$lft+1";
            $leafNodes = $this->createCommand($sql);
            $leafNodes->execute();
            $result = $leafNodes->queryAll();

        }
        return $result;
    }

    /**
     * method getMenuPath Written your current menu path
     * @param $id
     * @return mixed
     */
    public function getMenuPath($id)
    {
        $tableName = $this->tableName;
        $lft = $this->leftField;
        $rgt = $this->rightField;
        $name = $this->titleName;
        $au_id = $this->autoIncrement;
        $sql = "SELECT *
                FROM $tableName AS node,
                      $tableName AS parent
                WHERE node.$lft BETWEEN parent.$lft AND parent.$rgt
                      AND node.$au_id = $id
                ORDER BY node.$lft;";
        $path = $this->createCommand($sql);
        $path->execute();
        $result = $path->queryAll();
        return $result;
    }

    /**
     * method getDepthOfMenu Written Finding the Depth of the Nodes
     * @return mixed
     */
    public function getDepthOfMenu()
    {
        $tableName = $this->tableName;
        $lft = $this->leftField;
        $rgt = $this->rightField;
        $name = $this->titleName;
        $au_id = $this->autoIncrement;
        $sql = "SELECT node.$name, (COUNT(parent.$name) - 1) AS depth
              FROM $tableName AS node,
                   $tableName AS parent
              WHERE node.$lft BETWEEN parent.$lft AND parent.$rgt
              GROUP BY node.$name
              ORDER BY node.$lft";
        $dept = $this->createCommand($sql);
        $dept->execute();
        $result = $dept->queryAll();
        return $result;
    }

    /**
     * method for Deleting Nodes
     *
     * @param $id
     */
    public function deleteNodes($id)
    {
        $tableName = $this->tableName;
        $left = $this->leftField;
        $right = $this->rightField;
        $currentMenu = $this->currentMenu($id);
        $lft = $currentMenu->$left;
        $rgt = $currentMenu->$right;
        $width = $currentMenu->$right - $currentMenu->$left + 1;
        $sqlDelete = "DELETE FROM $tableName WHERE $left BETWEEN  $lft and $rgt ";
        $delete = $this->createCommand($sqlDelete);
        $delete->execute();
        $sqlRgt = "UPDATE $tableName SET $right = $right-$width WHERE  $right>$rgt";
        $updateRgt = $this->createCommand($sqlRgt);
        $updateRgt->execute();
        $updateSQL = "UPDATE $tableName SET $left = $left - $width WHERE  $left>$rgt";
        $updateLft = $this->createCommand($updateSQL);
        $updateLft->execute();
    }

    /**
     * method getAllTableList returned all table data
     * @return mixed
     */
    public function getAllTableList()
    {
        $tableName = $this->tableName;
        return $tableName::model()->findAll();
    }

    /**
     * @param $sql is string
     * @return mixed
     */
    private function  createCommand($sql)
    {
        return Yii::app()->db->createCommand($sql);
    }

    /**
     * your current menu
     * @param $id
     * @return mixed
     */
    public function  currentMenu($id)
    {
        $modelClass = $this->modelClassName;
        return $modelClass::model()->findByPk($id);
    }


} 