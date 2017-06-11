<?php

namespace Queryjson;

use Queryjson\TableBuilder;

if( !defined( 'DS' ) )
{
    define( 'DS', DIRECTORY_SEPARATOR );
}

class Schema
{

    /**
     * Le format de la base de données (json)
     * @var string
     */
    protected $driver;

    /**
     * Le repertoire de stockage
     * @var string
     */
    protected $host;

    /**
     * Le nom du schema
     * @var string
     */
    protected $name;

    /**
     * Le chemin complet du schema
     * @var string
     */
    protected $path;

    function __construct( $config = null )
    {
        if( !is_null( $config ) )
        {
            $json = $this->getJson( $config );
            $this->setConfig( $json[ 'database_driver' ], $json[ 'database_host' ], $json[ 'database_name' ] );
        }
    }

    /**
     * Ajout la configuration minimum pour le fonctionnement du schema.
     * 
     * @param string $host le chemin ou sera sauvegardé les données
     * @param string $name le nom du fichier du schema
     * @param string $driver le nom du format de fichier dans lequel les données seront manipulées
     */
    public function setConfig( $host, $name = 'schema', $driver = 'json' )
    {
        $this->driver = $driver;
        $this->host   = $host;
        $this->name   = $name;
        $this->path   = $host . DS . $name . '.' . $driver;
    }

    /**
     * Créer le schema avec les données de configuration.
     * 
     * @return boolean si le fichier est bien créé
     */
    protected function createSchema()
    {
        return $this->createJson( $this->host, $this->name );
    }

    /**
     * Génnère le schema si il n'existe pas en fonction des données de configuration.
     * 
     * @return array le schema de la base de données
     */
    public function getSchema()
    {
        $schema = $this->path;

        if( !file_exists( $schema ) )
        {
            $this->createSchema();
        }

        return $this->getJson( $schema );
    }

    /**
     * Cherche le schema de la table passé en paramêtre.
     * 
     * @param string $table nom de la table
     * 
     * @return array le schema de la table
     * @throws \Exception table not found
     */
    public function getSchemaTable( $table )
    {
        $schema = $this->getSchema();

        if( !isset( $schema[ $table ] ) )
        {
            throw new \Exception( "La table " . $table . " est absente !" );
        }

        return $schema[ $table ];
    }

    /**
     * Supprime le schema
     */
    public function dropSchema()
    {
        unlink( $this->path );
    }

    /**
     * Créer une référence de le schema et le fichier de la table.
     * 
     * @param string $table le nom de la table
     */
    public function createTable( $table, $callback = null )
    {
        $schema       = $this->getSchema();
        $tableBuilder = null;
        if( !is_null( $callback ) )
        {
            $tableBuilder = $callback( new TableBuilder() )->build();
        }
        $schema[ $table ] = [
            'path'    => $this->host,
            'table'   => $table,
            'setting' => $tableBuilder
        ];
        $this->saveJson( $this->host, $this->name, $schema );
        $this->createJson( $this->host, $table );
    }

    /**
     * Vide la table.
     * 
     * @param string $table le nom de la table
     */
    public function truncateTable( $table )
    {
        $schema = $this->getSchema();
        $this->saveJson( $schema[ $table ][ 'path' ], $schema[ $table ][ 'table' ], [] );
    }

    /**
     * Supprime du schema la référence de la table et le fichier de la table.
     * 
     * @param string $table le nom de la table
     */
    public function dropTable( $table )
    {
        $schema = $this->getSchema();
        $this->deleteJson( $schema[ $table ][ 'path' ], $schema[ $table ][ 'table' ] );
        unset( $schema[ $table ] );
        $this->saveJson( $this->host, $this->name, $schema );
    }

    /**
     * Retourne les données d'un fichier au format json.
     * 
     * @param string $file le nom du fichier
     * 
     * @return array les données du fichier
     * @throws \Exception
     */
    public function getJson( $file )
    {
        if( !file_exists( $file ) )
        {
            throw new \Exception( 'Error : The ' . $file . ' file is missing' );
        }
        if( !extension_loaded( 'json' ) )
        {
            throw new \Exception( 'Error : The json extension is not loaded' );
        }
        if( strrchr( $file, '.' ) != '.json' )
        {
            throw new \Exception( 'Error : The ' . $file . ' is not in json format' );
        }

        $json   = file_get_contents( $file );
        $return = json_decode( $json, true );
        return $return;
    }

    /**
     * Créer un fichier au format json si celui ci n'existe pas.
     * 
     * @param string $path le chemain du fichier
     * @param string $file le nom du fichier
     * @param array $data les données
     * 
     * @return boolean si le fichier est bien créé.
     * @throws \Exception erreur d'ouverture du fichier
     */
    public function createJson( $path, $file, array $data = [] )
    {
        if( !file_exists( $path ) )
        {
            mkdir( $path, 0775 );
        }
        $fichier = fopen( $path . DS . $file . '.json', 'w+' );
        if( !$fichier )
        {
            throw new Exception( 'File open failed.' );
        }
        fwrite( $fichier, json_encode( $data ) );
        return fclose( $fichier );
    }

    /**
     * Enregistre des données dans un fichier au format json.
     * 
     * @param string $path chemin du fichier
     * @param string $file nom du fichier
     * @param array $data tableau de données
     */
    public function saveJson( $path, $file, array $data )
    {
        return file_put_contents( $path . DS . $file . '.json', json_encode( $data ) );
    }

    /**
     * Supprime un fichier au format json.
     * 
     * @param string $path chemin du fichier
     * @param string $file nom du fichier
     */
    public function deleteJson( $path, $file )
    {
        return unlink( $path . DS . $file . '.json' );
    }

}
