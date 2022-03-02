<?php

/**
 * Parameter Class
 * Will set or get values from  $_SESSION['projectParameters']
 */
class Parameter extends Base
{
    /**
     * set/update project parameter.
     *
     * @param $name [!string]: name of parameter to set/update
     * @param $value [!variable]: value of parameter to set/update
     *
     * @throws Exception
     *
     * @return void
     */
    public static function set($name, $value)
    {
        if (!$name) {
            throw new \Exception('no name given!');
        }

        $_SESSION['projectParameters'][$name] = $value;
    }

    /**
     * get value of project parameter.
     *
     * @param $name
     * @param $defaultValue - if param was not found, return this
     *
     * @throws Exception
     */
    public static function get($name, $defaultValue = null)
    {
        if (!$name) {
            throw new \Exception('no name given');
        }

        if (isset($_SESSION['projectParameters'][$name])) {
            return $_SESSION['projectParameters'][$name];
        }

        return null;
    }

    /**
     * delete parameter.
     *
     * @param $name
     *
     * @throws Exception
     *
     * @return bool if parameter was found, false elsewise
     */
    public static function delete($name)
    {
        if (!$name) {
            throw new \Exception('no name given');
        }

        if (isset($_SESSION['projectParameters'][$name])) {
            unset($_SESSION['projectParameters'][$name]);

            return true;
        }

        return false;
    }
}
