<?php
namespace Ludo\Support;

class Factory
{
    /**
     * @param string $name
     * @param string $connectionName
     * @return \Ludo\Database\Dao
     */
    public static function dao($name, $connectionName = null)
    {
        $daoName = trim($name, '/').'Dao';
        $daoName = ucfirst($daoName);
        $file = LD_DAO_PATH.DIRECTORY_SEPARATOR.$daoName.php;
        if (file_exists($file)) {
            return new $daoName;
        } else {
            return new \BaseDao($name, $connectionName);
        }
    }
}
