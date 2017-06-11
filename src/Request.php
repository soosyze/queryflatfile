<?php

namespace Queryjson;

use Queryjson\Where;
use Queryjson\Schema;

class Request
{

    /**
     * Le nombre de résulat de la requête
     * @var integer
     */
    private $limit = null;

    /**
     * Le décalage des résulata de la requête
     * @var integer
     */
    private $offset = 0;

    /**
     * Les colonnes à trier
     * @var array
     */
    private $orderBy = [];

    /**
     * La requête
     * @var type
     */
    private $request;

    /**
     * Les données de la table
     * @var array 
     */
    private $table = [];

    /**
     * Les conditions de selection
     * @var Queryjson\Where
     */
    private $where;

    /**
     * Les données relatives aux tables
     * @var Queryjson\Schema
     */
    private $schema = null;

    /**
     * Le type d'execution (delete/update/insert)
     * @var string
     */
    private $execute = null;

    /**
     * Ajouter le schema pour réaliser des requêtes dessus.
     *
     * @param Queryjson\Schema $sch
     */
    public function setSchema( Schema $sch )
    {
        $this->schema = $sch;
    }

    /**
     * Retourne les données de la table sous forme de tableau associatif.
     *
     * @param string $name de la table
     *
     * @return array les données de la table
     */
    public function getTable( $name )
    {
        $file = $this->getTablePath( $name );
        return $this->schema->getJson( $file . DS . $name . '.json' );
    }

    /**
     * Retourne le chemin complet de la table.
     *
     * @param string $name de la table
     *
     * @return string le chemin de la table
     */
    public function getTablePath( $name )
    {
        $schema = $this->schema->getSchemaTable( $name );
        return $schema[ 'path' ];
    }

    /**
     * Selectionne les colonnes passés en paramêtre.
     *
     * @param array $columns tableau des noms des colonnes
     *
     * @return $this
     */
    public function select( array $columns = null )
    {
        $this->request[ 'columns' ] = $columns;
        return $this;
    }

    /**
     * Choisi la table d'ou proviennent les données.
     *
     * @param string $table
     *
     * @return $this
     */
    public function from( $table )
    {
        $this->table = $this->getTable( $table );

        $this->request[ 'pathTable' ] = $this->getTablePath( $table );
        $this->request[ 'nameTable' ] = $table;
        return $this;
    }

    /**
     * Réalise une joiture gauche avec une autre table selon une ou plusieurs conditions.
     *
     * @param string $table
     * @param Queryjson\Where $w
     *
     * @return $this
     */
    public function leftJoin( $table, Where $w )
    {
        $this->request[ 'leftJoin' ][ $table ][ 'table' ] = $table;
        $this->request[ 'leftJoin' ][ $table ][ 'where' ] = $w;
        return $this;
    }

    /**
     * Ajoute une condition de selection des données.
     *
     * @param Queryjson\Where $w
     *
     * @return $this
     */
    public function where( Where $w )
    {
        $this->where = $w;
        return $this;
    }

    /**
     * Retourne une instance de la class Queryjson\Where.
     *
     * @return Queryjson\Where
     */
    public function expr()
    {
        return new Where();
    }

    /**
     * Limite le nombre de resultats de la requête
     * et de quel enregistrement la requête débute.
     *
     * @param integer $limit du nombre de resultats
     * @param integer $offset commencement de la requête
     *
     * @return $this
     */
    public function limit( $limit, $offset = 0 )
    {
        $this->limit  = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Trie les données dans l'odre ascendant ou déscendant de la colonne passé en paramêtre.
     *
     * @param string $columns nom de la colonne à trier
     * @param string $order type de trie (asc|desc)
     *
     * @return $this
     */
    public function orderBy( $columns, $order = 'asc' )
    {
        $this->orderBy[ $columns ] = $order;
        return $this;
    }

    /**
     * Insert des données dans une table.
     *
     * @param string $table nom de la table
     * @param array $columns noms de colonnes pour l'enregistrement
     *
     * @return $this
     */
    public function insertInto( $table, array $columns = null )
    {
        $this->execute = 'insert';
        $this->from( $table );
        $this->select( $columns );
        return $this;
    }

    /**
     * Les valeurs d'insertion de données.
     *
     * @param array $columns données à inserer
     *
     * @return $this
     */
    public function values( array $columns )
    {
        $this->request[ 'values' ][] = $columns;
        return $this;
    }

    /**
     * Met à jour les données d'une table
     *
     * @param type $table nom de la table
     * @param array $columns noms des colonnes pour la mise à jour
     *
     * @return $this
     */
    public function update( $table, array $columns = null )
    {
        $this->execute = 'update';
        $this->from( $table );
        $this->select( $columns );
        return $this;
    }

    /**
     * Supprime les données d'une table.
     *
     * @return $this
     */
    public function delete()
    {
        $this->execute = 'delete';
        return $this;
    }

    /**
     * Execute les opérations d'insertions, de modification
     * et de suppression de données d'une table
     *
     * @throws \Exception n'a pas reçut la bonne instruction d'execution
     */
    public function execute()
    {
        switch( $this->execute )
        {
            case 'update':
                $this->executeUpdate();
                break;
            case 'insert':
                $this->executeInsert();
                break;
            case 'delete':
                $this->executeDelete();
                break;
            default:
                throw new \Exception( "Seul l'insertion, la mise à jour et la suppression des données peuvent être executé !" );
        }

        $output = $this->schema->saveJson( $this->request[ 'pathTable' ], $this->request[ 'nameTable' ], $this->table );
        $this->init();
        return $output;
    }

    /**
     * Execute une jointure gauche avec un seconde table avec une condition de selection.
     *
     * @param type $table nom de la table
     * @param Where $w condition de selection
     *
     * @return $this
     */
    protected function executeLeftJoin( $table, Where $w )
    {
        $result    = [];
        $tableJoin = $this->getTable( $table );
        $testEval  = $w->executeJoin();

        foreach( $this->table as $rowTable )
        {
            //join les tables
            foreach( $tableJoin as $rowTableJoin )
            {
                //vérifie les conditions
                $test = eval( 'return ' . $testEval . ';' );
                if( $test )
                {
                    //join les ligne si la condition est bonne
                    $result[] = array_merge( $rowTableJoin, $rowTable );
                }
            }
        }
        $this->table = $result;
        return $this;
    }

    /**
     * Execute l'insertion de données.
     * 
     * @throws \Exception le nombre de colonnes à inserer ne sont pas au même nombre que les valeurs à inserer
     */
    protected function executeInsert()
    {
        foreach( $this->request[ 'values' ] as $column )
        {
            if( count( $this->request[ 'columns' ] ) != count( $column ) )
            {
                throw new \Exception( 'keys : ' . implode( ',', $this->request[ 'columns' ] ) . ' != ' . implode( ',', $column ) );
            }
            // $this->request['columns'] sont les clée 
            // $column sont les valeurs
            // array_combine créer un tableau associatif clé=>valeur avec 2 array
            $this->table[] = array_combine( $this->request[ 'columns' ], $column );
        }
    }

    /**
     * Execute la mise à jour des données.
     */
    protected function executeUpdate()
    {
        if( isset( $this->where ) )
        {
            $testEval = $this->where->execute();
        }
        foreach( $this->table as $key => $row )
        {
            if( isset( $testEval ) )
            {
                $test = eval( 'return ' . $testEval . ';' );
                if( $test )
                {
                    $this->table[ $key ] = array_merge( $this->table[ $key ], $this->request[ 'columns' ] );
                }
            }
            else
            {
                $this->table[ $key ] = array_merge( $this->table[ $key ], $this->request[ 'columns' ] );
            }
        }
    }

    /**
     * Execute la suppression de données.
     */
    protected function executeDelete()
    {
        if( isset( $this->where ) )
        {
            $testEval = $this->where->execute();
        }
        foreach( $this->table as $key => $row )
        {
            if( isset( $testEval ) )
            {
                $test = eval( 'return ' . $testEval . ';' );
                if( $test )
                {
                    unset( $this->table[ $key ] );
                }
            }
            else
            {
                unset( $this->table[ $key ] );
            }
        }
        $this->table = array_values( $this->table );
    }

    /**
     * Trie le tableau en fonction des clées paramêtrés.
     * 
     * @param array $data
     * @param array $keys
     * @return array
     */
    protected function executeOrderBy( array $data, array $keys )
    {
        $keyLength = count( $keys );

        if( empty( $keys ) )
        {
            return $data;
        }

        foreach( $keys as $k => $value )
        {
            $keys[ $k ] = $keys[ $k ] == 'desc' || $keys[ $k ] == -1
                ? -1
                : ($keys[ $k ] == 'skip' || $keys[ $k ] === 0
                    ? 0
                    : 1);
        }

        usort( $data, function($a, $b) use ($keys, $keyLength)
        {
            $sorted = 0;
            $ix     = 0;

            while( $sorted === 0 && $ix < $keyLength )
            {
                $k = $this->obIx( $keys, $ix );
                if( $k )
                {
                    $dir    = $keys[ $k ];
                    $sorted = $this->keySort( $a[ $k ], $b[ $k ], $dir );
                    $ix++;
                }
            }
            return $sorted;
        } );
        return $data;
    }

    /**
     * Fonction du support à orderByExecute
     * 
     * @param type $obj
     * @param type $ix
     * @return boolean
     */
    private function obIx( $obj, $ix )
    {
        $size = 0;
        foreach( $obj as $key => $value )
        {
            if( $size == $ix )
            {
                return $key;
            }
            $size++;
        }
        return false;
    }

    /**
     * Fonction de trie de orderByExecute
     * 
     * @param type $a
     * @param type $b
     * @param type $d
     * @return int
     */
    private function keySort( $a, $b, $d = null )
    {
        $d = $d !== null
            ? $d
            : 1;
        if( $a == $b )
        {
            return 0;
        }
        return ($a > $b)
            ? 1 * $d
            : -1 * $d;
    }

    /**
     * Retourne le nom des colonnes selectionnées.
     * 
     * @return Array
     */
    public function getSelect()
    {
        return isset( $this->request[ 'columns' ] )
            ? $this->request[ 'columns' ]
            : [];
    }

    /**
     * Retourne l'objet Where executé.
     * 
     * @return type
     */
    public function getWhere()
    {
        return $this->where->execute();
    }

    /**
     * Retourne le resultat de la requête.
     * 
     * @return Array
     */
    public function fetchAll()
    {
        $this->fetchPrepareFrom();
        $this->loadAllColumnsSchema();
        $this->fetchPrepareSelect();
        $this->fetchPrepareWhere();
        $this->fetchPrepareOrderBy();

        // valeur(s) de retours
        $return = [];
        // Le pointeur en cas de limite de resultat
        $i      = 0;

        // Execution des jointures
        if( isset( $this->request[ 'leftJoin' ] ) )
        {
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $this->executeLeftJoin( $value[ 'table' ], $value[ 'where' ] );
            }
        }

        // Execution des conditions
        if( !empty( $this->where ) )
        {
            $testEval = $this->where->execute();
        }

        foreach( $this->table as $row )
        {
            //LIMITE
            if( !empty( $this->limit ) )
            {
                if( $i++ < $this->offset )
                {
                    continue;
                }
                if( ($i > $this->offset + $this->limit) && ($this->limit <= count( $return )) )
                {
                    break;
                }
            }

            // WHERE
            if( isset( $testEval ) )
            {
                $test = eval( 'return ' . $testEval . ';' );
                if( $test )
                {
                    $rowEval = $row;
                }
            }
            else
            {
                $rowEval = $row;
            }

            // SELECT
            if( isset( $rowEval ) && !empty( $this->request[ 'columns' ] ) )
            {
                $column   = array_flip( $this->request[ 'columns' ] );
                $return[] = array_intersect_key( $rowEval, $column );
                unset( $rowEval );
            }
            else if( isset( $rowEval ) )
            {
                $return[] = $rowEval;
                unset( $rowEval );
            }
        }

        // ORDER BY
        if( !empty( $this->orderBy ) )
        {
            $return = $this->executeOrderBy( $return, $this->orderBy );
        }

        $this->init();
        return $return;
    }

    /**
     * Retourne le premier resulat de la requête.
     * 
     * @return Array resultat de la requête ou null
     */
    public function fetch()
    {
        $this->limit = 1;
        $fetch       = $this->fetchAll();
        return !empty( $fetch[ 0 ] )
            ? $fetch[ 0 ]
            : null;
    }

    /**
     * Verifie l'existance des table à partir du schema.
     * 
     * @throws \Exception la table n'existe pas dans le schema
     */
    private function fetchPrepareFrom()
    {
        if( !isset( $this->request[ 'nameTable' ] ) )
        {
            throw new \Exception( "La table est absente : " . $this );
        }
    }

    /**
     * Charge les colonnes du schema des tables utilisés pour la requête
     */
    private function loadAllColumnsSchema()
    {
        $schemaTable = $this->schema->getSchemaTable( $this->request[ 'nameTable' ] );
        $schema      = $schemaTable[ 'setting' ];
        if( isset( $this->request[ 'leftJoin' ] ) )
        {
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $schemaTable = $this->schema->getSchemaTable( $value[ 'table' ] );
                $schema      = array_merge( $schema, $schemaTable[ 'setting' ] );
            }
        }

        $this->request[ 'allColumnsSchema' ] = $schema;
    }

    /**
     * Verifie l'existance des colonnes selectionné à partir du schema.
     * 
     * @throws \Exception une ou plusieurs colonnes sont absentes
     */
    private function fetchPrepareSelect()
    {
        // Si aucunes colonnes selectionnées alors *
        if( !isset( $this->request[ 'columns' ] ) )
        {
            $this->request[ 'columns' ] = [];
        }
        //verifie la différence entre les colonnes du schema et celle selectionnées
        $diff = array_diff_key( array_flip( $this->request[ 'columns' ] ), $this->request[ 'allColumnsSchema' ] );
        if( !empty( $diff ) )
        {
            $columnsDiff = array_flip( $diff );
            throw new \Exception( "La colonne " . implode( ',', $columnsDiff ) . " est absente : " . $this );
        }
    }

    /**
     * Verifie pour l'ensemble des joitures et des conditions l'existance des
     * colonnes à partir du schema.
     * 
     * @throws \Exception une ou plusieurs colonnes sont absentes
     */
    private function fetchPrepareWhere()
    {
        $columns = [];
        if( isset( $this->request[ 'leftJoin' ] ) )
        {
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $columns = array_merge( $columns, $value[ 'where' ]->getColumns() );
            }
        }
        if( isset( $this->where ) )
        {
            $columns = array_merge( $columns, $this->where->getColumns() );
        }

        $diff = array_diff_key( array_flip( $columns ), $this->request[ 'allColumnsSchema' ] );
        if( !empty( $diff ) )
        {
            $columnsDiff = array_flip( $diff );
            throw new \Exception( "La colonne " . implode( ',', $columnsDiff ) . " est absente : " . $this );
        }
    }

    /**
     * Verifie l'existance des colonnes du order by dans le schema des tables.
     * 
     * @throws \Exception une ou plusieurs colonnes sont absentes
     */
    private function fetchPrepareOrderBy()
    {
        $columns = [];
        if( isset( $this->orderBy ) )
        {
            $columns = array_keys( $this->orderBy );
        }
        //verifie la différence entre les colonnes du schema et celle selectionnées
        $diff = array_diff_key( array_flip( $columns ), $this->request[ 'allColumnsSchema' ] );
        if( !empty( $diff ) )
        {
            $columnsDiff = array_flip( $diff );
            throw new \Exception( "La colonne " . implode( ',', $columnsDiff ) . " est absente : " . $this );
        }
    }

    /**
     * Initialise les variables de class
     */
    public function init()
    {
        $this->where   = null;
        $this->table   = null;
        $this->request = null;
        $this->limit   = null;
        $this->offset  = 0;
        $this->delete  = false;
    }

    /**
     * Retourne les paramêtre de request en format pseudo SQL
     * 
     * @return string
     */
    public function __toString()
    {
        $output = '';
        if( isset( $this->request[ 'columns' ] ) )
        {
            $output .= 'SELECT [' . implode( ', ', $this->request[ 'columns' ] ) . '] ';
        }
        else
        {
            $output .= 'SELECT * ';
        }
        if( isset( $this->request[ 'nameTable' ] ) )
        {
            $output .= 'FROM ' . $this->request[ 'nameTable' ] . ' ';
        }
        if( isset( $this->request[ 'leftJoin' ] ) )
        {
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $output .= 'LEFT JOIN ' . $value[ 'table' ] . ' ON ' . $value[ 'where' ]->executeJoin() . ' ';
            }
        }
        if( !empty( $this->where ) )
        {
            $output .= 'WHERE ' . $this->where->execute() . ' ';
        }
        if( !empty( $this->orderBy ) )
        {
            foreach( $this->orderBy as $table => $order )
            {
                $output .= 'ORDER BY ' . $table . ' ' . $order . ' ';
            }
        }
        if( isset( $this->limit ) )
        {
            $output .= 'LIMIT ' . $this->limit . ' ';
        }
        if( $this->offset != 0 )
        {
            $output .= 'OFFSET ' . $this->offset . ' ';
        }
        return $output;
    }

}
