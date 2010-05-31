<?php

class DABLGenerator extends BaseGenerator {

    static $action_icons = array('Edit' => 'pencil', 'Show' => 'search', 'Delete' => 'trash');
    static $standard_actions = array('Show', 'Edit', 'Delete');

    function getParams($table_name) {
        $className = $this->getModelName($table_name);
        $instance = new $className;
        return array(
            'table_name' => $table_name,
            'controller_name' => $this->getControllerName($table_name),
            'model_name' => $className,
            'instance' => $instance,
            'plural' => self::getPluralName($table_name),
            'single' => self::getSingularName($table_name),
            'pk' => $instance->getPrimaryKey(),
            'pkMethod' => "get{$instance->getPrimaryKey()}",
            'actions' => $this->getActions($table_name),
            'columns' => $this->getColumns($table_name)
        );
    }

    /**
     * Returns an associative array of file contents for
     * each view generated by this class
     * @param string $table_name
     * @return array
     */
    function getViews($table_name) {
        $params = $this->getParams($table_name);
        return array(
            'edit.php' => $this->getEditView($params),
            'index.php' => $this->getIndexView($params),
            'show.php' => $this->getShowView($params),
            'grid.php' => $this->getGridView($params),
//			'list.php' => $this->getListView($params)
        );
    }

    /**
     * Generates a String with an html/php view for editing view MVC
     * objects in the given table.
     * @param String $table_name
     * @param String $className
     * @return String
     */
    function getEditView($params) {
        foreach ($params as $key => $value)$$key = $value;
        ob_start();
        require dirname(__FILE__) . '/dabl/edit.php';
        return ob_get_clean();
    }

    /**
     * Generates a String with an html/php view showing all of the
     * objects from the given table in a list
     * @param String $table_name
     * @param String $className
     * @return String
     */
    function getListView($params) {
        foreach ($params as $key => $value)$$key = $value;
        ob_start();
        require dirname(__FILE__) . '/dabl/list.php';
        return ob_get_clean();
    }

    /**
     * Generates a String with an html/php view showing all of the
     * objects from the given table in a grid
     * @param String $table_name
     * @param String $className
     * @return String
     */
    function getGridView($params) {
        foreach ($params as $key => $value)$$key = $value;
        ob_start();
        require dirname(__FILE__) . '/dabl/grid.php';
        return ob_get_clean();
    }

    /**
     * Generates a String with an html/php view showing all of the
     * objects from the given table in a grid
     * @param String $table_name
     * @param String $className
     * @return String
     */
    function getIndexView($params) {
        foreach ($params as $key => $value)$$key = $value;
        ob_start();
        require dirname(__FILE__) . '/dabl/index.php';
        return ob_get_clean();
    }

    /**
     * Generates a String with an html/php view for show view MVC
     * objects in the given table.
     * @param String $table_name
     * @param String $className
     * @return String
     */
    function getShowView($params) {
        foreach ($params as $key => $value)$$key = $value;
        unset($actions['Show']);
        ob_start();
        require dirname(__FILE__) . '/dabl/show.php';
        return ob_get_clean();
    }

    function getActions($table_name) {
        $controller_name = $this->getControllerName($table_name);
        $className = $this->getModelName($table_name);
        $plural = self::getPluralName($table_name);
        $single = self::getSingularName($table_name);
        $instance = new $className;
        $pk = $instance->getPrimaryKey();
        $pkMethod = "get$pk";
        $actions = array();
        if (!$pk)return $actions;

        foreach (self::$standard_actions as $staction)
            $actions[$staction] = "<?php echo site_url('" . $plural . "/" . strtolower($staction) . "/'.$" . $single . "->" . $pkMethod . "()) ?>";

        $fkeys_to = $this->getForeignKeysToTable($table_name);
        foreach ($fkeys_to as $k => $r) {
            $from_table = $r['from_table'];
            $from_className = $this->getModelName($from_table);
            $from_column = $r['from_column'];
            $to_column = $r['to_column'];
            if (@$used_to[$from_table]) {
                unset($fkeys_to[$k]);
                continue;
            }
            $used_to[$from_table] = $from_column;
            $actions[ucwords(self::spaceTitleCase(self::pluralize($from_table)))] = "<?php echo site_url('" . $this->getViewDirName($from_table) . '/' . $single . "/'.$" . $single . "->" . $pkMethod . "()) ?>";
        }

        return $actions;
    }

    /**
     * Generates a String with Controller class for MVC
     * @param String $table_name
     * @param String $className
     * @return String
     */
    function getController($table_name) {
        foreach ($this->getParams($table_name) as $key => $value)$$key = $value;
        ob_start();
        require dirname(__FILE__) . '/dabl/controller.php';
        return ob_get_clean();
    }

    /**
     * @param string $table_name
     * @return string
     */
    function getControllerName($table_name) {
        $controller_name = str_replace(' ', '', ucwords(str_replace('_', ' ', $table_name)));
        $controller_name = self::pluralize($controller_name);
        $controller_name = $controller_name . 'Controller';
        return $controller_name;
    }

    function getControllerFileName($table_name) {
        return $this->getControllerName($table_name) . ".php";
    }

}
