<?php

namespace Queryjson;

class TableBuilder
{

    /**
     * le schema de données des tables
     * @var array 
     */
    private $builder = [];

    /**
     * Déclare la colonne comme valeur de type incrémentale.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function increments( $name )
    {
        $this->builder[ $name ][ 'type' ] = 'increments';
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type caractère.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function char( $name )
    {
        $this->builder[ $name ][ 'type' ] = 'char';
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type text.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function text( $name )
    {
        $this->builder[ $name ][ 'type' ] = 'text';
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type varchar.
     * 
     * @param string $name nom de la colonne
     * @param type $length longeur de la chaine de caractère
     * 
     * @return \Queryjson\TableBuilder
     */
    public function varchar( $name, $length = 255 )
    {
        $this->builder[ $name ][ 'type' ]   = 'varchar';
        $this->builder[ $name ][ 'length' ] = $length;
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type nombre entier.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function integer( $name )
    {
        $this->builder[ $name ][ 'type' ] = 'integer';
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type nombre flottant.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function float( $name )
    {
        $this->builder[ $name ][ 'type' ] = 'integer';
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type boolean.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function boolean( $name )
    {
        $this->builder[ $name ][ 'type' ] = 'boolean';
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type date et heure.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function dateTime( $name, $format = 'Y-m-d H:i:s' )
    {
        $this->builder[ $name ][ 'type' ]   = 'dateTime';
        $this->builder[ $name ][ 'format' ] = $format;
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme valeur de type timestamps.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function timestamps( $name )
    {
        $this->builder[ $name ][ 'type' ] = 'timestamps';
        $this->isNull( $name );
        return $this;
    }

    /**
     * Déclare la colonne comme acceptant une valeur null.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function isNull( $name )
    {
        $this->builder[ $name ][ 'null' ] = true;
        return $this;
    }

    /**
     * Déclare la colonne comme refusant une valeur null.
     * 
     * @param string $name nom de la colonne
     * 
     * @return \Queryjson\TableBuilder
     */
    public function isNotNull( $name )
    {
        $this->builder[ $name ][ 'type' ] = false;
        return $this;
    }

    /**
     * Déclare la colonne comme référence d'une table.
     * 
     * @param string $name nom de la colonne
     * @param string $table nom de la table référencé
     * 
     * @return \Queryjson\TableBuilder
     */
    public function reference( $name, $table )
    {
        $this->builder[ $name ][ 'type' ]  = 'reference';
        $this->builder[ $name ][ 'table' ] = $table;
        return $this;
    }

    /**
     * Retourne le schema de données.
     * 
     * @return array le schema de base
     */
    public function build()
    {
        return $this->builder;
    }

}
