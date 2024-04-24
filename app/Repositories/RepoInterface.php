<?php


namespace App\Repositories;


interface RepoInterface
{
    public function getObject($object, $key);
    public function addObject($object, $key, $data);

    public function updateObject($object, $key, $data);
    public function deleteObject($object, $key);

    public function getList($object, $key, $start = 0, $end = -1);
    public function pushList($object, $key, $property = null, $value = null);
    public function pushListAutoIncrement($object, $key, $property = null);
    public function removeList($object, $key, $property = null, $value = null);
    public function getLenOfList($object, $key, $property = null);
}
