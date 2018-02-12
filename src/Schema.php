<?php

/**
 * Class Schema | src/Schema.php
 * 
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * 
 */

namespace Queryflatfile;

use Queryflatfile\TableBuilder,
    Queryflatfile\DriverInterface;

/**
 * Pattern fluent pour la gestion d'un schéma de données.
 */
class Schema
{
    /**
     * Le format de la base de données
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Le répertoire de stockage
     * @var string
     */
    protected $path;

    /**
     * Le nom du schéma
     * @var string
     */
    protected $name;

    /**
     * Le chemin, nom et extension du schéma
     * @var string
     */
    protected $file;

    /**
     * Construis l'objet avec une configuration.
     * 
     * @param string $host le répertoire de stockage des données
     * @param string $name le nom du fichier contenant le schéma de base
     * @param DriverInterface $driver l'interface de manipulation de données
     */
    public function __construct( $host = null, $name = 'schema',
        DriverInterface $driver = null )
    {
        if( !is_null($host) )
        {
            $this->setConfig($host, $name, $driver);
        }
    }

    /**
     * Enregistre la configuration.
     * 
     * @param string $host le répertoire de stockage des données
     * @param string $name le nom du fichier contenant le schéma de base
     * @param DriverInterface $driver l'interface de manipulation de données
     */
    public function setConfig( $host, $name = 'schema',
        DriverInterface $driver = null )
    {
        $this->driver = $driver;
        if( is_null($driver) )
        {
            $this->driver = new DriverJson();
        }

        $this->path = $host;
        $this->name = $name;
        $this->file = $host . DIRECTORY_SEPARATOR . $name . '.' . $this->driver->getExtension();

        return $this;
    }

    /**
     * Modifie les valeurs incrémentales d'une table.
     * 
     * @param string $table nom de la table
     * @param array $increments les valeurs incrémentales 
     * [ field_1=>value:int, ... ]
     * 
     * @return boolean si le schéma d'incrémentaion est bien enregistré
     */
    public function setIncrements( $table, array $increments )
    {
        $schema                           = $this->getSchema();
        $schema[ $table ][ 'increments' ] = $increments;

        return $this->save($this->path, $this->name, $schema);
    }

    /**
     * Génère le schéma s'il n'existe pas en fonction du fichier de configuration.
     * 
     * @return array le schema de la base de données
     */
    public function getSchema()
    {
        $schema = $this->file;

        if( !file_exists($schema) )
        {
            $this->create($this->path, $this->name);
        }

        return $this->read($this->path, $this->name);
    }

    /**
     * Cherche le schéma de la table passée en paramètre.
     * 
     * @param string $table nom de la table
     * 
     * @return array le schema de la table
     * 
     * @throws Exception\Query\TableNotFoundException
     */
    public function getSchemaTable( $table )
    {
        $schema = $this->getSchema();

        if( !isset($schema[ $table ]) )
        {
            throw new Exception\Query\TableNotFoundException("The " . $table . " table is missing in the schema.");
        }

        return $schema[ $table ];
    }

    /**
     * Supprime le schéma courant des données.
     * 
     * @return $this
     */
    public function dropSchema()
    {
        $schema = $this->getSchema();

        /* Supprime les fichiers des tables */
        foreach( $schema as $table )
        {
            $this->delete($table[ 'path' ], $table[ 'table' ]);
        }

        /* Supprime le fichier de schéma */
        unlink($this->file);

        /**
         * Dans le cas ou le répertoire utilisé contient d'autre fichier
         * (Si le répertoire contient que les 2 élements '.' et '..')
         * alors nous le supprimons.
         */
        if( count(scandir($this->path)) == 2 )
        {
            rmdir($this->path);
        }

        return $this;
    }

    /**
     * Créer une référence dans le schéma et le fichier de la table.
     * 
     * @param string $table le nom de la table
     * @param callable $callback fonction(TableBuilder $table) pour créer les champs
     */
    public function createTable( $table, callable $callback = null )
    {
        $schema = $this->getSchema();

        if( isset($schema[ $table ]) )
        {
            throw new \Exception("Table " . htmlspecialchars($table) . " exist.");
        }

        $tableBuilder = null;
        $increments   = [];

        if( !is_null($callback) )
        {
            $builder      = new TableBuilder();
            call_user_func_array($callback, [ &$builder ]);
            $tableBuilder = $builder->build();
            $increments   = $builder->getIncrement();
        }

        $schema[ $table ] = [
            'table'      => $table,
            'path'       => $this->path,
            'fields'     => $tableBuilder,
            'increments' => $increments
        ];

        $this->save($this->path, $this->name, $schema);
        $this->create($this->path, $table);

        return $this;
    }

    /**
     * Créer une référence dans le schéma et un fichier de données si ceux si n'existe pas.
     * 
     * @param string $table le nom de la table
     * @param callable $callback fonction(TableBuilder $table) pour créer les champs
     * 
     * @return $this
     */
    public function createTableIfNotExists( $table, callable $callback = null )
    {
        $sch = $this->getSchema();

        /* Créer la table si elle n'existe pas dans le schéma */
        if( !isset($sch[ $table ]) )
        {
            $this->createTable($table, $callback);
            return $this;
        }
        if( !$this->driver->has($sch[ $table ][ 'path' ], $sch[ $table ][ 'table' ]) )
        {
            /* Si elle existe dans le schéma et que le fichier est absent alors on le créer */
            $this->create($this->path, $table);
        }

        return $this;
    }

    /**
     * Modifie les champs du schéma de données.
     * 
     * @param string $table
     * @param callable $callback
     * 
     * @return $this
     */
    public function alterTable( $table, callable $callback = null )
    {
        $sch = $this->getSchema();
        return $this;
    }

    /**
     * Détermine une table existe.
     * 
     * @param string $table nom de la table
     * 
     * @return boolean si le schéma de référence et le fichier de données existent
     */
    public function hasTable( $table )
    {
        $sch = $this->getSchema();

        return isset($sch[ $table ]) && $this->driver->has($sch[ $table ][ 'path' ], $sch[ $table ][ 'table' ]);
    }

    /**
     * Détermine si une colonne existe.
     * 
     * @param string $table nom de la table
     * @param string $column nom de la colonne
     * 
     * @return boolean si le shéma de référence et le fichier de données existent
     */
    public function hasColumn( $table, $column )
    {
        $sch = $this->getSchema();

        return isset($sch[ $table ][ $column ]) && $this->driver->has($sch[ $table ][ 'path' ], $sch[ $table ][ 'table' ]);
    }

    /**
     * Vide la table et initialise les champs incrémentaux.
     * 
     * @param String $table le nom de la table
     * 
     * @return boolean
     */
    public function truncateTable( $table )
    {
        $schema = $this->getSchema();

        if( !isset($schema[ $table ]) )
        {
            throw new \Exception("Table " . htmlspecialchars($table) . " is not exist.");
        }

        $this->save($schema[ $table ][ 'path' ], $schema[ $table ][ 'table' ], [
        ]);

        foreach( $schema[ $table ][ 'increments' ] as $key => $value )
        {
            $schema[ $table ][ 'increments' ][ $key ] = 0;
        }

        return $this->save($this->path, $this->name, $schema);
    }

    /**
     * Supprime du schéma la référence de la table et son fichier de données.
     * 
     * @param string $table le nom de la table
     * 
     * @return boolean si la suppression du schema et des données se son bien passé
     */
    public function dropTable( $table )
    {
        $schema = $this->getSchema();

        if( !isset($schema[ $table ]) )
        {
            throw new \Exception("Table " . htmlspecialchars($table) . " is not exist.");
        }

        $deleteSchema = $this->delete($schema[ $table ][ 'path' ], $schema[ $table ][ 'table' ]);
        unset($schema[ $table ]);
        $deleteData   = $this->save($this->path, $this->name, $schema);

        return $deleteSchema && $deleteData;
    }

    /**
     * Supprime une table si elle existe.
     * 
     * @param string $table nom de la table
     * 
     * @return boolean si la table n'existe plus
     */
    public function dropTableIfExists( $table )
    {
        if( $this->hasTable($table) )
        {
            return $this->dropTable($table);
        }
    }

    /**
     * Utilisation du driver pour connaître l'extension de fichier utilisé.
     * 
     * @return string l'extension de fichier sans le '.'
     */
    public function getExtension()
    {
        return $this->driver->getExtension();
    }

    /**
     * Utilisation du driver pour lire un fichier.
     * 
     * @param string $path
     * @param string $file
     * 
     * @return array le contenu du fichier
     */
    public function read( $path, $file )
    {
        return $this->driver->read($path, $file);
    }

    /**
     * Utilisation du driver pour enregistrer des données dans un fichier.
     * 
     * @param string $path
     * @param string $file
     * @param array $data
     * 
     * @return boolean
     */
    public function save( $path, $file, array $data )
    {
        return $this->driver->save($path, $file, $data);
    }

    /**
     * Utilisation du driver pour créer un fichier.
     * 
     * @param string $path
     * @param string $file
     * @param array $data
     * 
     * @return boolean
     */
    protected function create( $path, $file, array $data = [] )
    {
        return $this->driver->create($path, $file, $data);
    }

    /**
     * Utilisation du driver pour supprimer un fichier.
     * 
     * @param string $path
     * @param string $file
     * 
     * @return boolean
     */
    protected function delete( $path, $file )
    {
        return $this->driver->delete($path, $file);
    }
}