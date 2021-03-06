<?php
namespace Maxposter\DacBundle\Annotations\Mapping\Service;

use Doctrine\ORM\EntityManager;
use Maxposter\DacBundle\Annotations\Mapping\Driver\Annotations as Driver;

class Annotations
{
    /** @var \Doctrine\ORM\EntityManager  */
    private $em;
    /** @var \Maxposter\DacBundle\Annotations\Mapping\Driver\Annotations  */
    private $driver;
    /** @var string  */
    private $cacheDir;

    /** @var array */
    private $map;

    /**
     * @param  EntityManager $em
     * @param  Driver        $driver
     * @param  string        $cacheDir
     */
    public function __construct(EntityManager $em, Driver $driver, $cacheDir)
    {
        $this->em     = $em;
        $this->driver = $driver;
        $this->cacheDir = $cacheDir;
    }


    /**
     * Загрузить в карту
     *
     * @return array
     */
    private function load()
    {
        if (!$this->map && (null !== $this->cacheDir) && is_file($filePath = sprintf('%s/maxposter.dac.annotations.cache', $this->cacheDir))) {
            $this->map = (array) unserialize(file_get_contents($filePath));
        } elseif (!$this->map) {
            $this->map = array('fields' => array(), 'roles' => array());
            foreach ($this->em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames() as $className) {
                // Кешируем только энтити с аннотациями
                if ($data = $this->driver->getData($className)) {
                    $this->map['fields'][$className] = $data['columns'];
                    $this->map['roles'][$className] = $data['roles'];
                }
            }
        }

        return $this->map;
    }


    /**
     * @return array
     */
    public function getAnnotationsMap()
    {
        return $this->load();
    }


    /**
     * @param $className
     * @return bool
     */
    public function hasDacFields($className)
    {
        $this->load();

        return array_key_exists($className, $this->map['fields']) && $this->map['fields'][$className];
    }


    /**
     * @param $className
     * @return array
     */
    public function getDacFields($className)
    {
        $this->load();

        return $this->hasDacFields($className) ? $this->map['fields'][$className] : array();
    }


    public function getDacRolesFor($className, $property)
    {
        return
            (
                array_key_exists($className, $this->map['roles'])
                && is_array($this->map['roles'][$className])
                && array_key_exists($property, $this->map['roles'][$className])
            )
            ? $this->map['roles'][$className][$property]
            : array()
        ;
    }
}