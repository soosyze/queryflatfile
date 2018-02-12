<?php

/**
 * Class TableBuilder | src/TableBuilder.php
 * 
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * 
 */

namespace Queryflatfile;

use Queryflatfile\Exception\TableBuilder\ColumnsNotFoundException,
    Queryflatfile\Exception\TableBuilder\ColumnsValueException;

/**
 * Pattern fluent pour la création et configuration des types de données.
 */
class TableBuilder
{
    /**
     * Les champs et leurs paramètres
     * @var array 
     */
    private $builder = [];

    /**
     * La valeur des champs incrémentaux
     * @var array 
     */
    private $increment = [];

    /**
     * Le dernier champ appelé pour renforcer le pattern fluent
     * @var string 
     */
    private $previousBuild = '';

    /**
     * Enregistre un champ de type `boolean`, true ou false.
     * http://php.net/manual/fr/language.types.boolean.php
     * 
     * @param string $name nom du champ
     * 
     * @return $this
     */
    public function boolean( $name )
    {
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'boolean' ];
        return $this;
    }

    /**
     * Enregistre un champ de type `char` avec une limite de taille par défaut de un caractère.
     * http://php.net/language.types.string
     * 
     * @param string $name nom du champ
     * @param numeric|int $length longueur maximum de la chaine
     * 
     * @return $this
     */
    public function char( $name, $length = 1 )
    {
        if( is_numeric($length) )
        {
            $length = ( int ) $length;
        }
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'char', 'length' => $length ];
        return $this;
    }

    /**
     * Enregistre un champ de type `date` sous le format Y-m-d.
     * 
     * @param string $name nom du champ
     * 
     * @return $this
     */
    public function date( $name )
    {
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'date' ];
        return $this;
    }

    /**
     * Enregistre un champ de type `datetime`, sous le format Y-m-d H:i:s.
     * 
     * @param string $name nom du champ
     * 
     * @return $this
     */
    public function datetime( $name )
    {
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'datetime' ];
        return $this;
    }

    /**
     * Enregistre un champ de type `float`, nombre à virgule flottant.
     * http://php.net/manual/fr/language.types.float.php
     * Valeur d'insertion autorisé : 1, '1', 1.0, '1.0' (enregistrera 1.0)
     * 
     * @param string $name nom du champ
     * 
     * @return $this
     */
    public function float( $name )
    {
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'float' ];
        return $this;
    }

    /**
     * Enregistre un champ de type `increments`, entier positif qui s'incrémente automatiquement.
     * http://php.net/manual/fr/language.types.integer.php
     * 
     * @param string $name nom du champ
     * 
     * @return $this
     */
    public function increments( $name )
    {
        $this->previousBuild    = $name;
        $this->builder[ $name ]   = [ 'name' => $name, 'type' => 'increments' ];
        $this->increment[ $name ] = 0;
        return $this;
    }

    /**
     * Enregistre un champ de type `integer`, entier signié.
     * http://php.net/manual/fr/language.types.integer.php
     * Valeur d'insertion autorisé : 1, '1', 1.1, '1.1' (enregistrera 1)
     * 
     * @param string $name nom du champ
     * 
     * @return $this
     */
    public function integer( $name )
    {
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'integer' ];
        return $this;
    }

    /**
     * Enregistre un champ de type `string` avec une limite de taille  par défaut de 255 caractères.
     * http://php.net/language.types.string
     * 
     * @param string $name nom du champ
     * @param numeric|int $length longueur maximum de la chaine
     * 
     * @return $this
     */
    public function string( $name, $length = 255 )
    {
        if( is_numeric($length) )
        {
            $length = ( int ) $length;
        }
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'string', 'length' => $length ];
        return $this;
    }

    /**
     * Enregistre un champ de type `text` sans limite de taille.
     * http://php.net/language.types.string
     * 
     * @param string $name nom du champ
     * 
     * @return $this
     */
    public function text( $name )
    {
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'text' ];
        return $this;
    }

    /**
     * Enregistre un commentaire sur le dernier champ appelé.
     * 
     * @param string $comment commentaire du champ précédent
     * 
     * @return $this
     * 
     * @throws ColumnsNotFoundException
     */
    public function comment( $comment )
    {
        if( !isset($this->builder[ $this->previousBuild ]) )
        {
            throw new ColumnsNotFoundException("No column selected for nullable.");
        }

        $this->builder[ $this->previousBuild ][ 'comment' ] = $comment;
        return $this;
    }

    /**
     * Enregistre le champ précédent comme acceptant la valeur NULL.
     * 
     * @return $this
     * 
     * @throws ColumnsNotFoundException
     */
    public function nullable()
    {
        if( !isset($this->builder[ $this->previousBuild ]) )
        {
            throw new ColumnsNotFoundException("No column selected for nullable.");
        }

        $this->builder[ $this->previousBuild ][ 'nullable' ] = true;
        return $this;
    }

    /**
     * Enregistre le champ précédent (uniquement de type integer) comme étant non signié.
     * 
     * @return $this
     * 
     * @throws ColumnsNotFoundException
     * @throws ColumnsValueException
     */
    public function unsigned()
    {
        if( !isset($this->builder[ $this->previousBuild ]) )
        {
            throw new ColumnsNotFoundException("No column selected for unsigned.");
        }

        $current = $this->builder[ $this->previousBuild ];
        if( $current[ 'type' ] === 'integer' )
        {
            $this->builder[ $current[ 'name' ] ][ 'unsigned' ] = true;
        }
        else
        {
            throw new ColumnsValueException("Impossiblie of unsigned type '" . htmlspecialchars($current[ 'type' ]) . "' (only integer).");
        }
        return $this;
    }

    /**
     * Enregistre une valeur par défaut au champ précédent.
     * Lève une exception si la valeur par défaut ne correspond pas au type de valeur passée en paramètre.
     * 
     * @param mixed $value valeur à tester
     * 
     * @return $this
     * 
     * @throws ColumnsNotFoundException
     * @throws ColumnsValueException
     */
    public function valueDefault( $value )
    {
        if( !isset($this->builder[ $this->previousBuild ]) )
        {
            throw new ColumnsNotFoundException("No column selected for value default.");
        }

        $current = $this->builder[ $this->previousBuild ];
        $name    = $current[ 'name' ];
        $type    = $current[ 'type' ];
        $error = htmlspecialchars('The default value (' . $value . ') for column ' . $name . ' does not correspond to type ' . $type . '.');

        if( $type === 'string' && !is_string($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( $type === 'string' && is_string($value) )
        {
            if( strlen($value) > $this->builder[ $name ][ 'length' ] )
            {
                throw new ColumnsValueException("The default value is larger than the specified size.");
            }
        }
        else if( $type === 'text' && !is_string($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( ($type === 'integer' || $type === 'increments') && !is_int($value) )
        {
            if( !is_numeric($value) && !is_int($value) )
            {
                throw new ColumnsValueException($error);
            }
            return ( int ) $value;
        }
        else if( ($type === 'float') && !is_float($value) )
        {
            if( !is_numeric($value) && !is_float($value) )
            {
                throw new ColumnsValueException($error);
            }
            return ( float ) $value;
        }
        else if( ($type === 'boolean') && !is_bool($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( ($type === 'char') && !is_string($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( ($type === 'char') && is_string($value) )
        {
            if( strlen($value) > $this->builder[ $name ][ 'length' ] )
            {
                throw new ColumnsValueException($error);
            }
        }
        else if( $type === 'date' )
        {
            if( strtolower($value) === 'current_date' )
            {
                $this->builder[ $current[ 'name' ] ][ 'default' ] = 'current_date';
                return $this;
            }
            else if( ($timestamp = strtotime($value)) === false )
            {
                throw new ColumnsValueException($error);
            }

            $this->builder[ $current[ 'name' ] ][ 'default' ] = date('Y-m-d', $timestamp);
            return $this;
        }
        else if( $type === 'datetime' )
        {

            if( strtolower($value) === 'current_datetime' )
            {
                $this->builder[ $current[ 'name' ] ][ 'default' ] = 'current_datetime';
                return $this;
            }
            $date   = new \DateTime($value);
            if( ($format = date_format($date, 'Y-m-d H:i:s')) === false )
            {
                throw new ColumnsValueException($error);
            }

            $this->builder[ $current[ 'name' ] ][ 'default' ] = $format;
            return $this;
        }

        $this->builder[ $current[ 'name' ] ][ 'default' ] = $value;
        return $this;
    }

    /**
     * Retourne le tableau contenant toutes les configurations.
     * 
     * @return array les configurations
     */
    public function build()
    {
        return $this->builder;
    }

    /**
     * Retourne la liste des champs incrémentaux.
     * 
     * @return array
     */
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * Enregistre la suppression d'une colonne.
     * 
     * @param string $name le nom de la colonne
     * 
     * @return $this
     */
    public function dropColumn( $name )
    {
        $this->previousBuild  = $name;
        $this->builder[ $name ] = [ 'name' => $name, 'type' => 'text' ];
        return $this;
    }
}